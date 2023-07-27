<?php
namespace App\Http\Repository\Video;

use Carbon\Carbon;
use App\Models\VideoData;
use App\Http\Repository\BaseRepository;

class VideoDataRepository extends BaseRepository
{

    /**
     * @var VideoData $model
     */
    protected $model;
    public function __construct(VideoData $model)
    {
        parent::__construct($model);
    }

    /**
     * 添加data数据
     *
     * @param array $data
     * @param bool $getId
     * @return bool|int
     */
    public function insertData(array $data, bool $getId = false)
    {
        return $getId ? $this->model->insertGetId($data) : $this->model->insert($data);
    }
}
