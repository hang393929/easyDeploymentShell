<?php
namespace App\Jobs;

use Illuminate\Bus\Queueable;
use App\Http\Services\UserService;
use App\Http\Helper\Log as LogHelper;
use App\Http\Services\CallBackService;
use Illuminate\Queue\SerializesModels;
use App\Exceptions\OutPlatformException;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Http\Factory\OutPlatform\OutPlatformFactory;

class CallBackUser implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $data;
    private $user;

    /**
     * Create a new job instance.
     *
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Execute the job
     *
     * @param CallBackService $callBackService
     * @param UserService $userService
     * @return void
     */
    public function handle(CallBackService $callBackService, UserService $userService)
    {
        LogHelper::add('data/callBackUser', '当前数据:' . json_encode($this->data));

        // 二次效验用户是否存在于平台（待执行队列存在写入数据，入队列之前没有消费）
        if (!$user = $userService->checkUserIsExistById($this->data['user_id'], true)) {
            return;
        }

        try {
            // 解析
            $data = OutPlatformFactory::register($user->platform)->formatUserData($user, $this->data);
            // 入库
            $callBackService::haddleCallBackUser($user->id, $data);
        } catch (OutPlatformException $e) {
            LogHelper::add('userJobParamError/', '当前数据:' . json_encode($this->data));
        } catch (\ErrorException $e) {
            LogHelper::add('userJobFormatError/',
                '当前数据:' . json_encode($this->data) . '当前错误信息:' . $e->getMessage()
                . '当前文件:' . $e->getFile() . '当前错误所在行:'. $e->getLine()
            );
            wxNotice('用户解析Format类型异常', getErrorTemplateMessage($e));
        } catch (\Throwable $e) {
            LogHelper::add('userJobHaddleError/', getErrorTemplateMessage($e) . '当前数据:' . json_encode($this->data));
            wxNotice('用户解析异常', getErrorTemplateMessage($e) . '当前数据:' . json_encode($this->data));
        }
    }
}
