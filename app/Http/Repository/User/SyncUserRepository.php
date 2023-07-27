<?php
namespace App\Http\Repository\User;

use App\Models\SyncUser;
use App\Http\Repository\BaseRepository;

class SyncUserRepository extends BaseRepository
{

    /**
     * @var SyncUser $model
     */
    protected $model;
    public function __construct(SyncUser $model)
    {
        parent::__construct($model);
    }

    /**
     * 根据ID获取用户
     *
     * @param $ids
     * @param bool $toArray
     * @return mixed
     */
    public function getSyncUserByIds($ids, bool $toArray = false) {
        if(is_array($ids)) {
            $model = $this->model->whereIn('id', $ids)->get();
        } else {
            $model =  $this->model->where('id', $ids)->first();
        }

        return $toArray ? $model->toArray() : $model;
    }

    /**
     * 根据ID获取value
     *
     * @param int $id
     * @param string $value
     * @return mixed|null
     */
    public function getValueById(int $id, string $value = 'id') {
        return  $this->model->where('id', $id)->value($value);
    }
}
