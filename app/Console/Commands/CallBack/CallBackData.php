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

class CallBackData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'callback:data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '粉丝数据解析';

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
    public function handle(UserService $userService)
    {
        $redisData = '{"data":[{"data_4":{"tendency":[{"date_key":1687363200,"total_inc":0,"sub_total_inc":0},{"date_key":1687449600,"total_inc":0,"sub_total_inc":0},{"date_key":1687536000,"total_inc":0,"sub_total_inc":0},{"date_key":1687622400,"total_inc":0,"sub_total_inc":0},{"date_key":1687708800,"total_inc":0,"sub_total_inc":0},{"date_key":1687795200,"total_inc":1,"sub_total_inc":0},{"date_key":1687881600,"total_inc":0,"sub_total_inc":0}]},"data_5":{"tendency":[{"date_key":1685376000,"total_inc":0,"sub_total_inc":0},{"date_key":1685462400,"total_inc":0,"sub_total_inc":0},{"date_key":1685548800,"total_inc":0,"sub_total_inc":0},{"date_key":1685635200,"total_inc":0,"sub_total_inc":0},{"date_key":1685721600,"total_inc":0,"sub_total_inc":0},{"date_key":1685808000,"total_inc":0,"sub_total_inc":0},{"date_key":1685894400,"total_inc":0,"sub_total_inc":0},{"date_key":1685980800,"total_inc":0,"sub_total_inc":0},{"date_key":1686067200,"total_inc":0,"sub_total_inc":0},{"date_key":1686153600,"total_inc":0,"sub_total_inc":0},{"date_key":1686240000,"total_inc":0,"sub_total_inc":0},{"date_key":1686326400,"total_inc":0,"sub_total_inc":0},{"date_key":1686412800,"total_inc":0,"sub_total_inc":0},{"date_key":1686499200,"total_inc":0,"sub_total_inc":0},{"date_key":1686585600,"total_inc":0,"sub_total_inc":0},{"date_key":1686672000,"total_inc":0,"sub_total_inc":0},{"date_key":1686758400,"total_inc":0,"sub_total_inc":0},{"date_key":1686844800,"total_inc":0,"sub_total_inc":0},{"date_key":1686931200,"total_inc":0,"sub_total_inc":0},{"date_key":1687017600,"total_inc":0,"sub_total_inc":0},{"date_key":1687104000,"total_inc":0,"sub_total_inc":0},{"date_key":1687190400,"total_inc":0,"sub_total_inc":0},{"date_key":1687276800,"total_inc":0,"sub_total_inc":0},{"date_key":1687363200,"total_inc":0,"sub_total_inc":0},{"date_key":1687449600,"total_inc":0,"sub_total_inc":0},{"date_key":1687536000,"total_inc":0,"sub_total_inc":0},{"date_key":1687622400,"total_inc":0,"sub_total_inc":0},{"date_key":1687708800,"total_inc":0,"sub_total_inc":0},{"date_key":1687795200,"total_inc":1,"sub_total_inc":0},{"date_key":1687881600,"total_inc":0,"sub_total_inc":0}]},"data_9":{"tendency":[{"date_key":1685376000,"total_inc":0,"sub_total_inc":0},{"date_key":1685462400,"total_inc":0,"sub_total_inc":0},{"date_key":1685548800,"total_inc":0,"sub_total_inc":0},{"date_key":1685635200,"total_inc":0,"sub_total_inc":0},{"date_key":1685721600,"total_inc":0,"sub_total_inc":0},{"date_key":1685808000,"total_inc":0,"sub_total_inc":0},{"date_key":1685894400,"total_inc":0,"sub_total_inc":0},{"date_key":1685980800,"total_inc":0,"sub_total_inc":0},{"date_key":1686067200,"total_inc":0,"sub_total_inc":0},{"date_key":1686153600,"total_inc":0,"sub_total_inc":0},{"date_key":1686240000,"total_inc":0,"sub_total_inc":0},{"date_key":1686326400,"total_inc":0,"sub_total_inc":0},{"date_key":1686412800,"total_inc":0,"sub_total_inc":0},{"date_key":1686499200,"total_inc":0,"sub_total_inc":0},{"date_key":1686585600,"total_inc":0,"sub_total_inc":0},{"date_key":1686672000,"total_inc":0,"sub_total_inc":0},{"date_key":1686758400,"total_inc":0,"sub_total_inc":0},{"date_key":1686844800,"total_inc":0,"sub_total_inc":0},{"date_key":1686931200,"total_inc":0,"sub_total_inc":0},{"date_key":1687017600,"total_inc":0,"sub_total_inc":0},{"date_key":1687104000,"total_inc":0,"sub_total_inc":0},{"date_key":1687190400,"total_inc":0,"sub_total_inc":0},{"date_key":1687276800,"total_inc":0,"sub_total_inc":0},{"date_key":1687363200,"total_inc":0,"sub_total_inc":0},{"date_key":1687449600,"total_inc":0,"sub_total_inc":0},{"date_key":1687536000,"total_inc":0,"sub_total_inc":0},{"date_key":1687622400,"total_inc":0,"sub_total_inc":0},{"date_key":1687708800,"total_inc":0,"sub_total_inc":0},{"date_key":1687795200,"total_inc":0,"sub_total_inc":0},{"date_key":1687881600,"total_inc":0,"sub_total_inc":0}]},"data_10":{"tendency":[{"date_key":1685376000,"total_inc":0,"sub_total_inc":0},{"date_key":1685462400,"total_inc":0,"sub_total_inc":0},{"date_key":1685548800,"total_inc":0,"sub_total_inc":0},{"date_key":1685635200,"total_inc":0,"sub_total_inc":0},{"date_key":1685721600,"total_inc":0,"sub_total_inc":0},{"date_key":1685808000,"total_inc":0,"sub_total_inc":0},{"date_key":1685894400,"total_inc":0,"sub_total_inc":0},{"date_key":1685980800,"total_inc":0,"sub_total_inc":0},{"date_key":1686067200,"total_inc":0,"sub_total_inc":0},{"date_key":1686153600,"total_inc":0,"sub_total_inc":0},{"date_key":1686240000,"total_inc":0,"sub_total_inc":0},{"date_key":1686326400,"total_inc":0,"sub_total_inc":0},{"date_key":1686412800,"total_inc":0,"sub_total_inc":0},{"date_key":1686499200,"total_inc":0,"sub_total_inc":0},{"date_key":1686585600,"total_inc":0,"sub_total_inc":0},{"date_key":1686672000,"total_inc":0,"sub_total_inc":0},{"date_key":1686758400,"total_inc":0,"sub_total_inc":0},{"date_key":1686844800,"total_inc":0,"sub_total_inc":0},{"date_key":1686931200,"total_inc":0,"sub_total_inc":0},{"date_key":1687017600,"total_inc":0,"sub_total_inc":0},{"date_key":1687104000,"total_inc":0,"sub_total_inc":0},{"date_key":1687190400,"total_inc":0,"sub_total_inc":0},{"date_key":1687276800,"total_inc":0,"sub_total_inc":0},{"date_key":1687363200,"total_inc":0,"sub_total_inc":0},{"date_key":1687449600,"total_inc":0,"sub_total_inc":0},{"date_key":1687536000,"total_inc":0,"sub_total_inc":0},{"date_key":1687622400,"total_inc":0,"sub_total_inc":0},{"date_key":1687708800,"total_inc":0,"sub_total_inc":0},{"date_key":1687795200,"total_inc":0,"sub_total_inc":0},{"date_key":1687881600,"total_inc":0,"sub_total_inc":0}]},"data_11":{"tendency":[{"date_key":1685376000,"total_inc":0,"sub_total_inc":0},{"date_key":1685462400,"total_inc":0,"sub_total_inc":0},{"date_key":1685548800,"total_inc":0,"sub_total_inc":0},{"date_key":1685635200,"total_inc":0,"sub_total_inc":0},{"date_key":1685721600,"total_inc":0,"sub_total_inc":0},{"date_key":1685808000,"total_inc":0,"sub_total_inc":0},{"date_key":1685894400,"total_inc":0,"sub_total_inc":0},{"date_key":1685980800,"total_inc":0,"sub_total_inc":0},{"date_key":1686067200,"total_inc":0,"sub_total_inc":0},{"date_key":1686153600,"total_inc":0,"sub_total_inc":0},{"date_key":1686240000,"total_inc":0,"sub_total_inc":0},{"date_key":1686326400,"total_inc":0,"sub_total_inc":0},{"date_key":1686412800,"total_inc":0,"sub_total_inc":0},{"date_key":1686499200,"total_inc":0,"sub_total_inc":0},{"date_key":1686585600,"total_inc":0,"sub_total_inc":0},{"date_key":1686672000,"total_inc":0,"sub_total_inc":0},{"date_key":1686758400,"total_inc":0,"sub_total_inc":0},{"date_key":1686844800,"total_inc":0,"sub_total_inc":0},{"date_key":1686931200,"total_inc":0,"sub_total_inc":0},{"date_key":1687017600,"total_inc":0,"sub_total_inc":0},{"date_key":1687104000,"total_inc":0,"sub_total_inc":0},{"date_key":1687190400,"total_inc":0,"sub_total_inc":0},{"date_key":1687276800,"total_inc":0,"sub_total_inc":0},{"date_key":1687363200,"total_inc":0,"sub_total_inc":0},{"date_key":1687449600,"total_inc":0,"sub_total_inc":0},{"date_key":1687536000,"total_inc":0,"sub_total_inc":0},{"date_key":1687622400,"total_inc":0,"sub_total_inc":0},{"date_key":1687708800,"total_inc":0,"sub_total_inc":0},{"date_key":1687795200,"total_inc":0,"sub_total_inc":0},{"date_key":1687881600,"total_inc":0,"sub_total_inc":0}]},"data_12":{"tendency":[{"date_key":1685376000,"total_inc":0,"sub_total_inc":0},{"date_key":1685462400,"total_inc":0,"sub_total_inc":0},{"date_key":1685548800,"total_inc":0,"sub_total_inc":0},{"date_key":1685635200,"total_inc":0,"sub_total_inc":0},{"date_key":1685721600,"total_inc":0,"sub_total_inc":0},{"date_key":1685808000,"total_inc":0,"sub_total_inc":0},{"date_key":1685894400,"total_inc":0,"sub_total_inc":0},{"date_key":1685980800,"total_inc":0,"sub_total_inc":0},{"date_key":1686067200,"total_inc":0,"sub_total_inc":0},{"date_key":1686153600,"total_inc":0,"sub_total_inc":0},{"date_key":1686240000,"total_inc":0,"sub_total_inc":0},{"date_key":1686326400,"total_inc":0,"sub_total_inc":0},{"date_key":1686412800,"total_inc":0,"sub_total_inc":0},{"date_key":1686499200,"total_inc":0,"sub_total_inc":0},{"date_key":1686585600,"total_inc":0,"sub_total_inc":0},{"date_key":1686672000,"total_inc":0,"sub_total_inc":0},{"date_key":1686758400,"total_inc":0,"sub_total_inc":0},{"date_key":1686844800,"total_inc":0,"sub_total_inc":0},{"date_key":1686931200,"total_inc":0,"sub_total_inc":0},{"date_key":1687017600,"total_inc":0,"sub_total_inc":0},{"date_key":1687104000,"total_inc":0,"sub_total_inc":0},{"date_key":1687190400,"total_inc":0,"sub_total_inc":0},{"date_key":1687276800,"total_inc":0,"sub_total_inc":0},{"date_key":1687363200,"total_inc":0,"sub_total_inc":0},{"date_key":1687449600,"total_inc":0,"sub_total_inc":0},{"date_key":1687536000,"total_inc":0,"sub_total_inc":0},{"date_key":1687622400,"total_inc":0,"sub_total_inc":0},{"date_key":1687708800,"total_inc":0,"sub_total_inc":0},{"date_key":1687795200,"total_inc":0,"sub_total_inc":0},{"date_key":1687881600,"total_inc":0,"sub_total_inc":0}]},"data_6":{"tendency":[{"date_key":1687363200,"total_inc":0,"sub_total_inc":0},{"date_key":1687449600,"total_inc":0,"sub_total_inc":0},{"date_key":1687536000,"total_inc":0,"sub_total_inc":0},{"date_key":1687622400,"total_inc":0,"sub_total_inc":0},{"date_key":1687708800,"total_inc":0,"sub_total_inc":0},{"date_key":1687795200,"total_inc":1,"sub_total_inc":0},{"date_key":1687881600,"total_inc":0,"sub_total_inc":0}]},"data_8":{"tendency":[{"date_key":1687363200,"total_inc":0},{"date_key":1687449600,"total_inc":0},{"date_key":1687536000,"total_inc":0},{"date_key":1687622400,"total_inc":0},{"date_key":1687708800,"total_inc":0},{"date_key":1687795200,"total_inc":0},{"date_key":1687881600,"total_inc":0}]},"data_7":{"tendency":[{"date_key":1685376000,"total_inc":0},{"date_key":1685462400,"total_inc":0},{"date_key":1685548800,"total_inc":0},{"date_key":1685635200,"total_inc":0},{"date_key":1685721600,"total_inc":0},{"date_key":1685808000,"total_inc":0},{"date_key":1685894400,"total_inc":0},{"date_key":1685980800,"total_inc":0},{"date_key":1686067200,"total_inc":0},{"date_key":1686153600,"total_inc":0},{"date_key":1686240000,"total_inc":0},{"date_key":1686326400,"total_inc":0},{"date_key":1686412800,"total_inc":0},{"date_key":1686499200,"total_inc":0},{"date_key":1686585600,"total_inc":0},{"date_key":1686672000,"total_inc":0},{"date_key":1686758400,"total_inc":0},{"date_key":1686844800,"total_inc":0},{"date_key":1686931200,"total_inc":0},{"date_key":1687017600,"total_inc":0},{"date_key":1687104000,"total_inc":0},{"date_key":1687190400,"total_inc":0},{"date_key":1687276800,"total_inc":0},{"date_key":1687363200,"total_inc":0},{"date_key":1687449600,"total_inc":0},{"date_key":1687536000,"total_inc":0},{"date_key":1687622400,"total_inc":0},{"date_key":1687708800,"total_inc":0},{"date_key":1687795200,"total_inc":0},{"date_key":1687881600,"total_inc":0}]}}],"user_id":127518,"unique":"3494363059456357"} ';
        $data = is_array($redisData) ? $redisData : json_decode($redisData, true);

        try {
            if (!$user = $userService->getUserByIds($data['user_id'])) {
                throw new OutPlatformException('不存在该用户');
            }
            $sync = $userService->getSyncUserByIds($user['user_id']);
            if(!$sync) {
                throw new OutPlatformException('不存在mcn用户');
            }

            // 解析 + 入库 (无数据支撑，只能沿用老写法)
            OutPlatformFactory::register($user->platform)->formatDataData($user, $data, $sync);
        } catch (OutPlatformException $e) {
            LogHelper::add('dataJobParamError/', '当前数据:' . json_encode($data));
        } catch (\ErrorException $e) {
            LogHelper::add('dataJobFormatError/',
                '当前数据:' . json_encode($data) . '当前错误信息:' . $e->getMessage()
                . '当前文件:' . $e->getFile() . '当前错误所在行:'. $e->getLine()
            );
            wxNotice('callbackdata解析Format类型异常', getErrorTemplateMessage($e));
        } catch (\Throwable $e) {
            LogHelper::add('dataJobHaddleError/', getErrorTemplateMessage($e));
            wxNotice('callbackdata解析异常', getErrorTemplateMessage($e));
        }
    }

}
