<?php
namespace App\Http\Services;

use App\Http\Helper\Oss;
use Illuminate\Support\Arr;
use App\Http\Services\TaskService;
use App\Exceptions\OutPlatformException;
use App\Http\Repository\Video\VideoRepository;

class VideoService
{
    /**
     * 根据ID更新视频
     *
     * @param int $id
     * @param array $data
     * @return mixed
     */
    public static function updateVideoById(int $id, array $data) {
        $data['update_time'] = time();
        if (Arr::exists($data, 'id')) {
            unset($data['id']);
        }
        if (Arr::exists($data, 'unique')) {
            unset($data['unique']);
        }
        if (Arr::exists($data, 'platform')) {
            unset($data['platform']);
        }
        if (Arr::exists($data, 'sync_id')) {
            unset($data['sync_id']);
        }
        if (Arr::exists($data, 'user_id')) {
            unset($data['user_id']);
        }
        if (Arr::exists($data, 'channel')) {
            unset($data['channel']);
        }
        if (Arr::exists($data,'project')){
            unset($data['project']);
        }
        if (Arr::exists($data,'classify')){
            unset($data['classify']);
        }
        if (Arr::exists($data,'channel_child')){
            unset($data['channel_child']);
        }

        return app(VideoRepository::class)->updateById($id, $data);
    }

    /**
     * 通过Unique获取视频
     *
     * @param $unique
     * @return mixed
     */
    public function getVideoByUnique($unique) {
        return app(VideoRepository::class)->getByUnique($unique);
    }

    /**
     * video新增
     *
     * @param array $video
     * @return mixed
     * @throws OutPlatformException
     */
    public function insertToVideo(array $video) {
        if(!$video) {
            throw new OutPlatformException('参数异常');
        }

        return app(VideoRepository::class)->insert($video);
    }

    /**
     * 回调视频解析新增入库
     *
     * @param $video
     * @return void
     * @throws OutPlatformException
     */
    public function videoAddFromCallBack($video) {
        $pushTask = TaskService::getPushTaskByPidAndPlatform($video['push_all_id'], $video['platform']);
        if(empty($pushTask)) {
            throw new OutPlatformException('匹配不到pushTask:');
        }

        if(!empty($pushTask['unique_new']) && $pushTask['unique_new'] != $video['unique_new']) {
            throw new OutPlatformException('unique_new不一致:');
        }

        if(!empty($video['img']) && empty($video['oneself_img'])) {
            $video['oneself_img'] = '';
            if (Oss::isValidNetworkUrl($video['img'])) {
                if ($path = Oss::getOssImg($video['img'])) {
                    $video['oneself_img'] = $path;
                }
            }
        }

        $video['create_time'] = time();
        $video['update_time'] = time();
        if(empty($pushTask['unique_new'])) {
            TaskService::updatePushTaskUniqueNewById(
                $pushTask['id'], ['unique_new' => $video['unique_new'], 'update_time' => time()]
            );
        }

        $this->insertToVideo($video);
    }

    /**
     * 检查标题去除特殊字符
     * @param string $title
     * @return string
     */
    public static function checkVideoTitle(string $title = '')
    {
        if (!$title) {
            return $title;
        }
        $title = explode('#', trim($title))[0] ?? trim($title);
        $title = trim($title);

        return $title;
    }
}
