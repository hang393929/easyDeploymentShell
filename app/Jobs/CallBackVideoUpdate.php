<?php
namespace App\Jobs;

use App\Http\Helper\Oss;
use Illuminate\Bus\Queueable;
use App\Http\Services\VideoService;
use App\Http\Helper\Log as LogHelper;
use Illuminate\Queue\SerializesModels;
use App\Exceptions\OutPlatformException;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldQueue;

class CallBackVideoUpdate implements ShouldQueue
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
     * @param VideoService $videoService
     * @return void
     */
    public function handle(VideoService $videoService)
    {
        // LogHelper::add('data/callBackVideoAdd', '当前数据:' . json_encode($this->data));

        try {
            if(empty($this->data['id'])) {
                $videoService->videoAddFromCallBack($this->data);
            }

            if(!empty($this->data['img']) && empty($this->data['oneself_img'])) {
                $this->data['oneself_img'] = '';
                if (Oss::isValidNetworkUrl($this->data['img'])) {
                    if ($path = Oss::getOssImg($this->data['img'])) {
                        $this->data['oneself_img'] = $path;
                    }
                }
            }

            $videoService->updateVideoById($this->data['id'], $this->data);

            LogHelper::info('updatevideolog', 'success' . json_encode($this->data));

        } catch (OutPlatformException $e) {
            LogHelper::info('updatevideolog', $e->getMessage() . json_encode($this->data));
        } catch (\Throwable $e) {
            LogHelper::info('updatevideolog', 'error 当前数据:' . json_encode($this->data).'  当前错误原因：'.getErrorTemplateMessage($e));
            wxNotice('视频更新入库异常', getErrorTemplateMessage($e) . '当前数据:' . json_encode($this->data));
        }
    }
}
