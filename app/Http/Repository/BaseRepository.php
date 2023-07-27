<?php
namespace App\Http\Repository;

use Illuminate\Database\Eloquent\Model;

abstract class BaseRepository implements RepositoryInterface
{

    /**
     * Model 类
     *
     * @var Model
     */
    protected $model;

    public function __construct(Model $model) {
        $this->model = $model;
    }

    public function getModel() {
        return $this->model;
    }

    public function search(array $params, array $columns = ['*'], array $with = []) {
        $query = $this->model::select($columns);
        if ($with) {
            foreach ($with as $key=>$item) {
                switch ($key) {
                    default:
                        break;
                }
            }
        }

        if(!empty($params['orderBy'])) {
            $query->orderBy($params['orderBy'], $params['orderByValue'] ?? 'desc');
        }

        if(!empty($params['page'])) {
            return $query->paginate($params['pageSize'] ?? 20, $params['page']);
        }

        return $query->get();
    }

    /**
     * 单条键值查询
     *
     * @param string $whereKey
     * @param $whereValue
     * @param array $filed
     * @param $func
     * @return mixed
     */
    public function selectByWhereOnly(string $whereKey, $whereValue, array $filed = ['*'], $func = 'first') {
        return $this->model->select($filed)->where($whereKey, $whereValue)->$func();
    }

    /**
     * 自定义where条件
     *
     *         $where[] = [
     *           function ($query) {
     *             $start = date('Y-m-d 00:00:00');
     *             $end = date('Y-m-d 23:59:59');
     *             $query->WhereBetween('BeginTime', [$start, $end]);
     *            },
     *          ];
     *
     * @param array $where
     * @param string ...$fields
     * @return mixed
     */
    public function getDataByWhere(array $where, string ...$fields)
    {
        return $this->model->where($where)->select($fields)->get();
    }
}
