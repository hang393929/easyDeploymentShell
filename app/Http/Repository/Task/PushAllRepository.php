<?php
namespace App\Http\Repository\Task;

use App\Models\PushAll;
use App\Http\Repository\BaseRepository;

class PushAllRepository extends BaseRepository
{

    /**
     * @var PushAll $model
     */
    protected $model;
    public function __construct(PushAll $model)
    {
        parent::__construct($model);
    }

    /**
     * 通过ids获取信息
     *
     * @param $ids
     * @param bool $toArray
     * @return mixed
     */
    public function getByIds($ids, bool $toArray = false) {
        if(is_array($ids)) {
            $model = $this->model->whereIn('id', $ids)->get();
        } else {
            $model =  $this->model->where('id', $ids)->first();
        }

        return $toArray ? $model->toArray() : $model;
    }
}
