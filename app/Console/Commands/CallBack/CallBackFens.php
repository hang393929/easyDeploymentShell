<?php
/**
 * @deprecated 临时使用，下次前端发版更换新域名，使用接口队列
 */
namespace App\Console\Commands\CallBack;

use App\Constants\RedisKey;
use Illuminate\Console\Command;
use App\Http\Services\UserService;
use App\Http\Helper\Log as LogHelper;
use Illuminate\Support\Facades\Redis;
use App\Http\Services\CallBackService;
use App\Exceptions\OutPlatformException;
use App\Http\Factory\OutPlatform\OutPlatformFactory;

class CallBackFens extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'callback:fens';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '粉丝数据解析';

    protected static $base = RedisKey::REDIS_DATABASES_FOUR; // redis库
    protected static $mode = 'order'; // 执行顺序
    protected static $key  = 'callBackFens'; // redis解析队列key

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
    public function handle(CallBackService $callBackService)
    {
        Redis::select(self::$base);
        while (true) {
            if(self::$mode == 'cross') {
                // 首尾交替处理 每次处理一条数据
                $redisData = time() & 1 ? Redis::lpop(self::$key) : Redis::rpop(self::$key);
            } else {
                // 顺序
                $redisData = Redis::LPOP(self::$key);
                LogHelper::add('data/', '当前数据:' . $redisData);
                //$redisData = '{"data":[{"code":0,"msg":"","data":{"fans_count":"21","lines":[{"name":"u_bdb_dis_gender_age","labels":["30-34"],"values":[{"name":"\u7537","value":[1],"ratio":[1]}]}],"pies":[{"name":"u_bdb_dis_province","values":[{"name":"\u4e91\u5357\u7701","value":1,"ratio":1}]},{"name":"u_bdb_dis_grade","values":[{"name":"\u521d\u4e2d","value":1,"ratio":1}]}]}}],"user_id":4565,"unique":"9006735211"}';
            }
            if (empty($redisData)) {
                sleep(1);
                continue;
            }

            $data = is_array($redisData) ? $redisData : json_decode($redisData, true);
            if(empty($data['unique']) || !$user = UserService::getUserByUnique($data['unique'])) {
                LogHelper::add('fensJobParamError', '当前数据:' . json_encode($data));
                continue;
            }

            // 过滤重复数据
//            $key = 'FensRepeatFilter_' . $user->id . '_' . $user->platform;
//            if(!$callBackService->repeatFilter($data, $key, RedisKey::REDIS_DATABASES_FOUR)) {
//                continue;
//            }

            try {
                // 解析
                $data = OutPlatformFactory::formatFanDrawData($user->platform, $user, $data);
                // 入库
                $callBackService->haddleCallBackFens($data);
            } catch (OutPlatformException $e) {
                LogHelper::add('fensJobParamError/', '当前数据:' . json_encode($data));
            } catch (\ErrorException $e) {
                LogHelper::add('fensJobFormatError/',
                    '当前数据:' . json_encode($data) . '当前错误信息:' . $e->getMessage() .
                    '当前文件:' . $e->getFile() . '当前错误所在行:'. $e->getLine()
                );
                wxNotice('粉丝解析Format类型异常', getErrorTemplateMessage($e));
            } catch (\Throwable $e) {
                LogHelper::add('fensJobHaddleError/', getErrorTemplateMessage($e));
                wxNotice('粉丝解析全局异常', getErrorTemplateMessage($e));
            }
            // dispatch(new CallBackFensJob($redisData))->onConnection('redis')->onQueue('callVideoFens');
        }
    }

}
