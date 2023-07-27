<?php
namespace App\Http\Repository\User;

use App\Models\UserLog;
use App\Http\Repository\BaseRepository;

class UserLogRepository extends BaseRepository
{

    /**
     * @var UserLog $model
     */
    protected $model;
    public function __construct(UserLog $model)
    {
        parent::__construct($model);
    }

    /**
     * 通过日期及用户ID获取日志信息
     *
     * @param string $date
     * @param int $userId
     * @return UserLog|\Illuminate\Database\Eloquent\Model|object|null
     */
    public function getByDateAndUserId(string $date, int $userId)
    {
        return $this->model->where('date', $date)->where('user_id', $userId)->first();
    }

    /**
     * 基于数据对象更新数据
     *
     * @param UserLog $model
     * @param $data
     * @return bool
     */
    public function updateByModel(UserLog $model, $data) {
        return $model->update($data);
    }

    /**
     * 新增
     *
     * @param array $info
     * @return bool
     */
    public function insert(array $info)
    {
        $info['create_time'] = time();
        return $this->model->insert($info);
    }

}
