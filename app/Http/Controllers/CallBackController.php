<?php
namespace App\Http\Controllers;

use App\Constants\RedisKey;
use Illuminate\Http\Request;
use App\Exceptions\ResponseCode;
use App\Http\Services\UserService;
use App\Http\Helper\Log as LogHelper;
use App\Http\Requests\CallBackRequest;
use App\Http\Services\CallBackService;
use App\Exceptions\OutPlatformException;
use App\Jobs\CallBackUser as CallBackUser;
use App\Jobs\CallBackFens as CallBackFensJob;
use App\Jobs\CallBackData as CallBackDataJob;
use App\Jobs\CallBackVideo as CallBackVideoJob;
use App\Jobs\CallBackIncome as CallBackIncomeJob;
use App\Jobs\CallBackVideoDetail as CallBackVideoDetailJob;

class CallBackController extends ApiController
{
    /**
     * @var UserService
     * @var CallBackService
     */
    public $callBackService;
    public $userService;

    public function __construct(CallBackService $callBackService, UserService $userService) {
        $this->callBackService = $callBackService;
        $this->userService     = $userService;
    }


    /**
     * 用户(redis队列)
     *
     * @param CallBackRequest $request
     * @return mixed
     */
    public function user(Request $request) {
        $userId = $request->input('user_id', 0);
        $data   = $request->input('data');
        if(!$data) {
            return $this->error([], ResponseCode::USER_NOT_FOUND);
        }

        $data = is_array($data) ? $data : json_decode($data, true);

        if ($userId) {
            $data['user_id'] = $userId;
        } else {
            $userId = $data['user_id'];
        }

        // 过滤重复数据
        $key = 'CallbackUser_' . $userId;
        if(!$this->callBackService->repeatFilter($data, $key, RedisKey::REDIS_DATABASES_THREE)) {
            return $this->success();
        }

        // 用户是否存在于平台
        if (!$this->userService->checkUserIsExistById($userId)) {
            return $this->error([], ResponseCode::USER_NOT_FOUND);
        }

        // 压入队列 配置3号库
        dispatch(new CallBackUser($data))
            ->onConnection(RedisKey::$conn[RedisKey::REDIS_DATABASES_THREE])
            ->onQueue('callBackUser');

        return $this->success();
    }


    /**
     * 视频(redis队列)
     *
     * @param Request $request
     * @return mixed
     * @throws \Exception
     */
    public function video(Request $request) {
        $data = $request->input('data');
        if (!$data) {
            return $this->error([], ResponseCode::INVALID_PARAMETER);
        }

        $data = is_array($data) ? $data : json_decode($data, true);
        if(empty($data['user_id']) || !$this->userService->checkUserIsExistById($data['user_id'])) {
            LogHelper::add('videoJobParamError', '当前数据:' . json_encode($data));
            return $this->error([], ResponseCode::USER_NOT_FOUND);
        }

        // 一小时内的前五分钟可以上报
        if($this->callBackService->lockForCallBackVideo($data['user_id'])) {
            return $this->success();
        }

        // 压入队列 配置2号库
        dispatch(new CallBackVideoJob($data))
            ->onConnection(RedisKey::$conn[RedisKey::REDIS_DATABASES_TWO])
            ->onQueue('callBackVideo');

        // 写videoData
        $this->callBackService->addVideoData([
            'create_time' => time(),
            'date'        => date('Y-m-d'),
            'user_id'     => $data['user_id'],
            'content'     => is_array($data) ? json_encode($data) : $data
        ]);

        // 刷新锁
        $this->callBackService->lockForCallBackVideo($data['user_id'], false);

        return $this->success();
    }


    /**
     * 粉丝(redis队列)
     *
     * @param Request $request
     * @return mixed
     */
    public function fens(Request $request) {
        $data = $request->input('data');
        if (!$data) {
            return $this->error([], ResponseCode::INVALID_PARAMETER);
        }

        $data = is_array($data) ? $data : json_decode($data, true);
        if(empty($data['unique']) || !$user = $this->userService->getUserByUnique($data['unique'])) {
            LogHelper::add('fensJobParamError', '当前数据:' . json_encode($data));
            return $this->error([], ResponseCode::INCOMPLETE_SYNC_DATA);
        }

        // 过滤重复数据
        $key = 'FensRepeatFilter_' . $user->id . '_' . $user->platform;
        if(!$this->callBackService->repeatFilter($data, $key, RedisKey::REDIS_DATABASES_FOUR)) {
            return $this->success();
        }

        // 压入队列 配置4号库
        dispatch(new CallBackFensJob($data, $user))
            ->onConnection(RedisKey::$conn[RedisKey::REDIS_DATABASES_FOUR])
            ->onQueue('callBackFens');

        return $this->success();
    }


    /**
     * 粉丝（旧）
     *
     * @param Request $request
     * @return mixed
     * @throws \Exception
     */
    public function income(Request $request) {
        $data = $request->input('data');
        if(!$data) {
            return $this->error([], ResponseCode::USER_NOT_FOUND);
        }

        $data = is_array($data) ? $data : json_decode($data, true);
        $key = 'CallbackIncome_' . $data['user_id'];
        if(!$this->callBackService->repeatFilter($data, $key, RedisKey::REDIS_DATABASES_ONE)) {
            return $this->success();
        }

        // 压入队列 配置1号库
        dispatch(new CallBackIncomeJob($data))
            ->onConnection(RedisKey::$conn[RedisKey::REDIS_DATABASES_ONE])
            ->onQueue('callBackIncome');

        return $this->success();
    }

    /**
     * 数据回调
     *
     * @param Request $request
     * @return mixed
     */
    public function callBackData(Request $request) {
        $data = $request->input('data');
        if (!$data) {
            return $this->error([], ResponseCode::INVALID_PARAMETER);
        }

        $data = is_array($data) ? $data : json_decode($data, true);
        if(empty($data['user_id'])) {
            return $this->error([], ResponseCode::DATA_EXCEPTION);
        }

        $key = 'CallbackData_' . $data['user_id'];
        if(!$this->callBackService->repeatFilter($data, $key, RedisKey::REDIS_DATABASES_ONE)) {
            return $this->success();
        }

        // 压入队列 配置1号库
        dispatch(new CallBackDataJob($data))
            ->onConnection(RedisKey::$conn[RedisKey::REDIS_DATABASES_ONE])
            ->onQueue('callBackData');

        return $this->success();
    }

    /**
     * 视频详情接口
     *
     * @param Request $request
     * @return mixed
     */
    public function videoDetail(Request $request){
        $data = $request->input('data');
        if (!$data) {
            return $this->error([], ResponseCode::INVALID_PARAMETER);
        }

        $data = is_array($data) ? $data : json_decode($data, true);
        if(empty($data['user_id'])) {
            return $this->error([], ResponseCode::DATA_EXCEPTION);
        }
        $key = 'CallBackVideoDetail_' . $data['user_id'];
        if(!$this->callBackService->repeatFilter($data, $key, RedisKey::REDIS_DATABASES_SEVEN)) {
            return $this->success();
        }
        // 视频详情压入队列 配置5号库
        dispatch(new CallBackVideoDetailJob($data))
            ->onConnection(RedisKey::$conn[RedisKey::REDIS_DATABASES_SEVEN])
            ->onQueue('callBackVideoDetail');

        return $this->success();
    }
}
