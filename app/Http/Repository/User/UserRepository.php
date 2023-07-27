<?php
namespace App\Http\Repository\User;

use App\Http\Repository\BaseRepository;
use App\Models\User;

class UserRepository extends BaseRepository
{

    /**
     * @var User $model
     */
    protected $model;
    public function __construct(User $model)
    {
        parent::__construct($model);
    }

    /**
     * 根据ID获取用户信息
     *
     * @param $ids
     * @param bool $toArray
     * @return mixed
     */
    public function getUserByIds($ids, bool $toArray = false) {
        if(is_array($ids)) {
            $model = $this->model->whereIn('id', $ids)->get();
        } else {
            $model =  $this->model->where('id', $ids)->first();
        }

        return $toArray ? $model->toArray() : $model;
    }

    /**
     * 根据ID更新用户信息
     *
     * @param $ids
     * @param array $data
     * @return bool
     */
    public function updateByIds($ids, array $data) {
        if(is_array($ids)) {
            return $this->model->whereIn('id', $ids)->update($data);
        }

        return $this->model->where('id', $ids)->update($data);
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
