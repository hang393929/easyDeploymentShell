<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use App\Http\Services\VideoService;
use App\Http\Helper\Log as LogHelper;
use Illuminate\Queue\SerializesModels;
use App\Exceptions\OutPlatformException;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldQueue;

class CallBackVideoAdd implements ShouldQueue
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
        // LogHelper::add('data/callBackVideoUpdate', '当前数据:' . json_encode($this->data));

        $videoInfo = $videoService->getVideoByUnique($this->data['unique']);

        try {

            if ($videoInfo) {
                $videoService->updateVideoById($videoInfo['id'], $this->data);
                throw new OutPlatformException('addJob--更新video success:');
            }

            $videoService->videoAddFromCallBack($this->data);

            LogHelper::info('addvideolog', 'success' . json_encode($this->data));

        } catch (OutPlatformException $e) {
            LogHelper::info('addvideolog', $e->getMessage() . json_encode($this->data));
        } catch (\Throwable $e) {
            LogHelper::info('addvideolog', 'error 当前数据:' . json_encode($this->data).'    当前异常原因:'.getErrorTemplateMessage($e));
            wxNotice('视频添加入库异常', getErrorTemplateMessage($e) . '当前数据:' . json_encode($this->data));
        }
    }
}
