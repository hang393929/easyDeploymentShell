<?php
namespace App\Http\Repository\Video;

use App\Models\VideoDetail;
use App\Http\Repository\BaseRepository;

class VideoDetailRepository extends BaseRepository
{

    /**
     * @var VideoDetail $model
     */
    protected $model;
    public function __construct(VideoDetail $model)
    {
        parent::__construct($model);
    }
    public function addVideoDetail($info)
    {

        $videoRes = $this->getByUniqueAndTime($info['unique'],$info['date']);

        if(!empty($videoRes)) {
            $this->updateById($videoRes['id'], $info);
        } else {
            $this->insert($info);
        }
    }
    public function getByUniqueAndTime($unique, $date)
    {
        $unique = is_string($unique) ? $unique : (string)$unique;
        $unique = trim($unique);
        return $this->model->where('unique', $unique)->where('date', $date)->first();
    }
    /**
     * 根据ID更新
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function updateById(int $id, array $data) {
        return $this->model->where('id', $id)->update($data);
    }

    /**
     * 新增
     *
     * @param array $video
     * @return bool
     */
    public function insert(array $detail) {
//        dd($detail);
        return $this->model->insert($detail);
    }
}
