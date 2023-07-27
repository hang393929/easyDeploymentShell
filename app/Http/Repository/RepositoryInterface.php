<?php
namespace App\Http\Repository;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

interface RepositoryInterface
{
    /**
     * 查询全部数据
     *
     * @param  array|string[]  $columns
     *
     * @return Collection
     */
     public function search(array $params, array $columns = ['*'], array $with = []);



    /**
     * 查询全部数据
     *
     * @param  array|string[]  $columns
     *
     * @return Collection
     */
    // public function all(array $columns = ['*']);

    /**
     * 分页查询
     *
     * @param  int             $perPage
     * @param  array|string[]  $columns
     *
     * @return LengthAwarePaginator
     * @throws \InvalidArgumentException
     */
    // public function paginate(int $perPage = 15, array $columns = ['*']);

    /**
     * @param  array  $data
     *
     * @return mixed
     */
    // public function create(array $data);

    /**
     * 更新数据
     *
     * @param  array  $data
     * @param  int    $id
     *
     * @return bool|null
     */
    // public function update(array $data, int $id);

    /**
     * 批量更新数据
     *
     * @param  array  $data
     * @param  array    $id
     *
     * @return bool|null|int
     */
     // public function batchUpdate(array $data, array $id);

    /**
     * 删除数据
     *
     * @param  int  $id
     *
     * @return bool|null
     */
    // public function delete(int $id);

    /**
     * 根据ID查找一条数据
     *
     * @param  int             $id
     * @param  array|string[]  $columns
     *
     * @return Model|Collection|static[]|static|null
     */
    // public function find(int $id, array $columns = ['*']);

    /**
     * 单条件查询一条或多条数据
     * @param  string          $field
     * @param  string          $value
     * @param  array|string[]  $columns
     * @param  string          $fun
     *
     * @return mixed
     */
    // public function findBy(string $field, string $value, array $columns = ['*'], string $fun = 'get');


    /**
     * 根据ID自增某一列的值
     *
     * @param $id
     * @param $field
     * @param $num
     * @return mixed
     */
    // public function increment($id, $field, $num = 1);
}
