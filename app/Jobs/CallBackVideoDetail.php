<?php
namespace App\Jobs;

use App\Http\Services\CallBackService;
use Illuminate\Bus\Queueable;
use App\Http\Services\UserService;
use App\Http\Helper\Log as LogHelper;
use Illuminate\Database\QueryException;
use Illuminate\Queue\SerializesModels;
use App\Exceptions\OutPlatformException;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldQueue;

class CallBackVideoDetail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $data;

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
     * @param UserService $userService
     * @return void
     */
    public function handle(CallBackService $callBackService, UserService $userService)
    {
        LogHelper::add('data/callBackVideoDetail', '当前数据:' . json_encode($this->data));

        // 二次效验用户是否存在于平台（待执行队列存在写入数据，入队列之前没有消费）
        if (empty($this->data['user_id']) || !$user = $userService->getUserByIds($this->data['user_id'])) {
            return;
        }

        // 获取sync_user video表mcn_id填充
        $sync = $userService->getSyncUserByIds($user['user_id']);
        if (empty($sync)){
            return;
        }

        try {
            $callBackService->haddleCallBackVideoDetail($user, $this->data);
        } catch (OutPlatformException $e) {
            LogHelper::add('videoDetailJobParamError/', '当前数据:' . json_encode($this->data));
        } catch (\ErrorException $e) {
            LogHelper::add('videoDetailJobFormatError/',
                '当前数据:' . json_encode($this->data) . '当前错误信息:' . $e->getMessage()
                . '当前文件:' . $e->getFile() . '当前错误所在行:' . $e->getLine()
            );
            wxNotice('视频详情解析Format类型异常', getErrorTemplateMessage($e));
        } catch (QueryException $e) {
            wxNotice('视频详情数据库查询异常', getErrorTemplateMessage($e) . '当前数据:' . json_encode($this->data));
        } catch (\PDOException $e) {
            wxNotice('视频详情解析log表主键冲突', getErrorTemplateMessage($e) . '当前数据:' . json_encode($this->data));
        } catch (\Throwable $e) {
            LogHelper::add('videoJobHaddleError/', getErrorTemplateMessage($e) . '当前数据:' . json_encode($this->data));
            wxNotice('视频详情解析异常', getErrorTemplateMessage($e) . '当前数据:' . json_encode($this->data));
        }
    }
}
