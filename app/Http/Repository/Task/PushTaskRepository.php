<?php
namespace App\Http\Repository\Task;

use App\Models\PushTask;
use App\Http\Repository\BaseRepository;

class PushTaskRepository extends BaseRepository
{

    /**
     * @var PushTask $model
     */
    protected $model;
    public function __construct(PushTask $model)
    {
        parent::__construct($model);
    }

    /**
     * 通过uniqueNew匹配task
     *
     * @param $unique_new
     * @param $platform
     * @param $project
     * @param $toArray
     * @return PushTask[]|array|\Illuminate\Database\Eloquent\Collection|\Illuminate\Support\Collection
     */
    public function getListByUserIdAndAndUniqueNew($unique_new, $platform, $project, $toArray = false)
    {
        $unique_new = is_string($unique_new) ? $unique_new : (string)$unique_new;
        $unique_new = trim($unique_new);
        $return     = $this->model->where('unique_new', $unique_new)
            ->where('platform', $platform)
            ->where('project', $project)
            ->where('delete', 0)
            ->get();

        return $toArray ? $return->toArray() : $return;
    }

    /**
     * 通过视频标题匹配task
     *
     * @param $title
     * @param $platform
     * @param $project
     * @param $toArray
     * @return PushTask[]|array|false|\Illuminate\Database\Eloquent\Collection|\Illuminate\Support\Collection
     */
    public function getListByUserAndTitle($title, $platform, $project, $toArray = false)
    {
        if (!$title) {
            return false;
        }

        $return = $this->model->where('platform', $platform)
            ->where('project', $project)
            ->where('title', $title)
            ->where('delete', 0)
            ->get();

        return $toArray ? $return->toArray() : $return;
    }

    /**
     * 根据parent_id及platform获取task
     *
     * @param int $pushAllId
     * @param int $platform
     * @return PushTask|\Illuminate\Database\Eloquent\Model|object|null
     */
    public function gtByPidAndPlatform(int $pushAllId, int $platform) {
        return $this->model->where('parent_id', $pushAllId)->where('platform', $platform)->first();
    }

    /**
     * 通过Id更新
     *
     * @param int $pushTaskId
     * @param array $data
     * @return bool
     */
    public function updateUniqueNewById(int $pushTaskId, array $data) {
        return $this->model->where('id', $pushTaskId)->update($data);
    }
}
