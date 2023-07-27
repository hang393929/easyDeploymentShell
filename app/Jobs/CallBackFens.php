<?php
namespace App\Jobs;

use App\Models\User;
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

class CallBackFens implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $data;
    private $user;

    /**
     * Create a new job instance.
     *
     * @param array $data
     */
    public function __construct(array $data, User $user)
    {
        $this->data = $data;
        $this->user = $user;
    }

    /**
     * Execute the job
     *
     * @param CallBackService $callBackService
     * @param UserService $userService
     * @return void
     */
    public function handle(CallBackService $callBackService)
    {
        LogHelper::add('data/callBackFens', '当前数据:' . json_encode($this->data));

        try {
            // 解析
            $data = OutPlatformFactory::formatFanDrawData($this->user->platform, $this->user, $this->data);
            // 入库
            $callBackService::haddleCallBackFens($data);
        } catch (OutPlatformException $e) {
            LogHelper::add('fensJobParamError/', '当前数据:' . json_encode($this->data));
        } catch (\ErrorException $e) {
            LogHelper::add('fensJobFormatError/',
                '当前数据:' . json_encode($data) . '当前错误信息:' . $e->getMessage()
                . '当前文件:' . $e->getFile() . '当前错误所在行:'. $e->getLine()
            );
            wxNotice('粉丝解析Format类型异常', getErrorTemplateMessage($e));
        } catch (\Throwable $e) {
            LogHelper::add('fensJobHaddleError/', getErrorTemplateMessage($e) . '当前数据:' . json_encode($this->data));
            wxNotice('粉丝解析异常', getErrorTemplateMessage($e) . '当前数据:' . json_encode($this->data));
        }
    }
}
