<?php
namespace App\Jobs;

use Illuminate\Bus\Queueable;
use App\Http\Services\UserService;
use App\Http\Helper\Log as LogHelper;
use Illuminate\Queue\SerializesModels;
use App\Exceptions\OutPlatformException;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Http\Factory\OutPlatform\OutPlatformFactory;

class CallBackData implements ShouldQueue
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
     * @param UserService $userService
     * @return void
     */
    public function handle(UserService $userService)
    {
        LogHelper::add('data/callBackData', '当前数据:' . json_encode($this->data));

        if (!$user = $userService->getUserByIds($this->data['user_id'])) {
            return;
        }
        $sync = $userService->getSyncUserByIds($user['user_id']);
        if(!$sync) {
            return;
        }

        try {
            // 解析 + 入库 (无数据支撑，只能沿用老写法)
            OutPlatformFactory::register($user->platform)->formatDataData($user, $this->data, $sync);
        } catch (OutPlatformException $e) {
            LogHelper::add('dataJobParamError/', '当前数据:' . json_encode($this->data));
        } catch (\ErrorException $e) {
            LogHelper::add('dataJobFormatError/',
                '当前数据:' . json_encode($this->data) . '当前错误信息:' . $e->getMessage()
                . '当前文件:' . $e->getFile() . '当前错误所在行:'. $e->getLine()
            );
            wxNotice('callbackdata解析Format类型异常', getErrorTemplateMessage($e));
        } catch (\Throwable $e) {
            LogHelper::add('dataJobHaddleError/', getErrorTemplateMessage($e) . '当前数据:' . json_encode($this->data));
            wxNotice('callbackdata解析异常', getErrorTemplateMessage($e) . '当前数据:' . json_encode($this->data));
        }
    }
}
