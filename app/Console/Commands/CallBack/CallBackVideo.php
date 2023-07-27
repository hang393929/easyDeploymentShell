<?php
/**
 * @deprecated 临时使用，视频检测video数据
 */
namespace App\Console\Commands\CallBack;

use App\Constants\RedisKey;
use App\Exceptions\OutPlatformException;
use App\Exceptions\ResponseCode;
use App\Http\Factory\OutPlatform\OutPlatformFactory;
use App\Jobs\CallBackFens as CallBackFensJob;
use Illuminate\Console\Command;
use App\Http\Services\UserService;
use App\Http\Helper\Log as LogHelper;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Redis;
use App\Http\Services\CallBackService;

class CallBackVideo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'callback:videos';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '视频数据解析';

    protected static $base = RedisKey::REDIS_DATABASES_TWO; // redis库
    protected static $mode = 'order'; // 执行顺序
    protected static $key  = 'callbackVideoListNewList'; // redis解析队列key

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @deprecated 不用定时任务，直接压队列
     * @return int
     */
    public function handle(CallBackService $callBackService, UserService $userService)
    {
//        Redis::select(self::$base);
//        while (true) {
//            $redisData = Redis::LPOP(self::$key);
//            $data = is_array($redisData) ? $redisData : json_decode($redisData, true);
//
//            $userId = $data['user_id'];
//
//            if (!$userService->checkUserIsExistById($userId)) {
//                continue;
//            }
//
//            // 压入队列 配置4号库
//            dispatch(new \App\Jobs\CallBackUser($data))->onConnection('redis_queue_three')->onQueue('callBackUser');
//
//        }
//        exit;

        $i = 0;
        Redis::select(self::$base);
        while (true) {
            if (self::$mode == 'cross') {
                // 首尾交替处理 每次处理一条数据
                $redisData = time() & 1 ? Redis::lpop(self::$key) : Redis::rpop(self::$key);
            } else {
                // 顺序
                //$redisData = Redis::LPOP(self::$key);
                $i++;
                //LogHelper::add('data/', '当前数据:' . $redisData);
                $redisData = '{"data":[{"HttpCode":200,"Code":0,"Message":"success","Contents":[{"ArticleAttr":{"Gid":"7252909114831929402","Type":4,"TypeDesc":"小视频","Title":"【拒绝标签】撕掉标签，你可以成为你想成为","RichTitle":"【拒绝标签】撕掉标签，你可以成为你想成为","Abstract":"【拒绝标签】撕掉标签，你可以成为你想成为","CreateTime":1688699497,"PopScore":1688699497,"ModifyTime":1688699497,"Status":2,"StatusDesc":"已发布","CoverImage":{"ImageURL":"https://p0-image-private.ixigua.com/tos-cn-i-0004/oYbPITCRAAb42CneAAgeWpmQEbgkkmDCXAJHnA~noop.jpeg?policy=eyJ2bSI6MywidWlkIjoiMzc4Mjc3Mzc1NDMxMjU0NCJ9&x-orig-authkey=f32326d3454f2ac7e96d3d06cdbb035152127018&x-orig-expires=2004590143&x-orig-sign=gxfT%2Ff3jknts16WkFhuMncDJ0K0%3D","Width":720,"Height":1280,"ImageURI":"tos-cn-i-0004/oYbPITCRAAb42CneAAgeWpmQEbgkkmDCXAJHnA"},"MpId":0,"ActionUrls":{},"ShowTime":1688699497,"ItemId":"7252909114831929402","CommentStatus":0,"UploadStatus":0,"StatusList":{"IsStick":false,"IsAnonymous":false,"SyncAwemeStatus":0},"MenuList":{"ShowDelete":true,"ShowModify":false,"ShowHide":true,"ShowStick":true,"ShowShare":true,"ShowAnonymous":false,"ShowComment":true,"ShowBoost":false,"ShowTimerPublish":false,"ShowSyncAweme":false,"ForbidToDetail":false,"ShowCopy":false,"ShowGraphic2Video":false,"ShowAds":false,"ForbidDeleteReason":"","ForbidModifyReason":"","ForbidHideReason":"","ForbidBoostReason":"","ForbidTimerPublishReason":"","ForbidSyncAweme":"","ForbidToDetailReason":"","ForbidChangeAdsReason":""},"IsGallery":false,"IsQuestion":false,"VerifyReason":"","Duration":71,"ImageCnt":0,"VisibilityLevel":40,"LeaderSensitive":false,"IsExclusive":false,"Extra":{"group_source":"21","user_name":"2023漂流瓶先生","impr_id":"202307101435430D3CBE7564F9C12AB546"},"ShortVideoBoundInfo":{},"GroupSource":21,"ArticleType":"shortvideo"},"ArticleStat":{"ImpressionCount":41,"GoDetailCount":0,"CommentCount":0,"PlayCount":0,"AnswerCount":0,"DiggCount":0,"RepinCount":0,"Counters":[{"Name":"播放","Count":0},{"Name":"评论","Count":0}]}}],"TotalCount":9,"StartCursor":1688699497403,"EndCursor":1679634501774,"HasMore":true,"BaseResp":{"StatusMessage":"success","StatusCode":0},"_data_type":"spiderShortVideoList"}],"user_id":1426,"unique":3782773754312544}';
            }
            if($i > 1) {
                exit();
            }
            if (empty($redisData)) {
                sleep(1);
                continue;
            }
            $redisData = is_array($redisData) ? $redisData : json_decode($redisData, true);

            // 二次效验用户是否存在于平台（待执行队列存在写入数据，入队列之前没有消费）
            if (empty($redisData['user_id']) || !$user = $userService->getUserByIds($redisData['user_id'])) {
                continue;
            }
            echo 12345;
            try {
                $callBackService->haddleCallBackVideo($user, $redisData);
            } catch (OutPlatformException $e) {
                LogHelper::add('videoJobParamError/', '当前数据:' . json_encode($redisData));
            } catch (\ErrorException $e) {
                LogHelper::add('videoJobFormatError/',
                    '当前数据:' . json_encode($redisData) . '当前错误信息:' . $e->getMessage() .
                    '当前文件:' . $e->getFile() . '当前错误所在行:'. $e->getLine()
                );
                wxNotice('视频解析Format类型异常', getErrorTemplateMessage($e));
            } catch (QueryException $e) {
                // 捕获Illuminate\Database\QueryException异常
                wxNotice('数据库查询异常', getErrorTemplateMessage($e) . '当前数据:' . json_encode($redisData));
            } catch (\PDOException $e) {
                wxNotice('视频解析log表主键冲突', getErrorTemplateMessage($e) . '当前数据:' . json_encode($redisData));
            } catch (\Throwable $e) {
                LogHelper::add('videoJobHaddleError/', getErrorTemplateMessage($e));
                wxNotice('视频解析异常', getErrorTemplateMessage($e));
            }
        }
    }

}
