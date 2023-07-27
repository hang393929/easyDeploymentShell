<?php
namespace App\Jobs;

use Illuminate\Bus\Queueable;
use App\Http\Helper\Log as LogHelper;
use Illuminate\Queue\SerializesModels;
use App\Http\Services\CallBackService;
use App\Exceptions\OutPlatformException;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldQueue;

class CallBackIncome implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $data;


    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 0;

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
     * @return void
     */
    public function handle(CallBackService $callBackService)
    {
        LogHelper::add('data/callBackIncome', '当前数据:' . json_encode($this->data));

        try{
            $callBackService->haddleCallBackIncome($this->data);
        } catch (OutPlatformException $e) {
            LogHelper::add('incomeHaddleError/', '当前数据:' . json_encode($this->data));
        } catch (\ErrorException $e) {
            LogHelper::add('incomeHaddleError/',
                '当前数据:' . json_encode($this->data) . '当前错误信息:' . $e->getMessage() .
                '当前文件:' . $e->getFile() . '当前错误所在行:'. $e->getLine()
            );
            wxNotice('income解析Format类型异常', getErrorTemplateMessage($e));
        } catch (\Throwable $e) {
            LogHelper::add('incomeHaddleError/', getErrorTemplateMessage($e) . '当前数据:' . json_encode($this->data));
            wxNotice('粉丝解析异常-income', getErrorTemplateMessage($e) . '当前数据:' . json_encode($this->data));

        }

    }
}
