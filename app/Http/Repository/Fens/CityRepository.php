<?php
namespace App\Http\Repository\Fens;

use App\Models\FensCity;
use App\Http\Repository\BaseRepository;

class CityRepository extends BaseRepository
{

    /**
     * @var FensCity $model
     */
    protected $model;
    public function __construct(FensCity $model)
    {
        parent::__construct($model);
    }

    /**
     * 获取城市信息
     *
     * @param $drawId
     * @param array $select
     * @return \Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection
     */
    public function getCityByDrawId($drawId, array $select = ['*'])
    {
        if (is_array($drawId)) {
            return $this->model->select($select)->whereIn('draw_id', $drawId)->get();
        }

        return $this->model->select($select)->where('draw_id', $drawId)->get();
    }

    /**
     * 批量新增
     *
     * @param array $data
     * @param int $fensDrawId
     * @return void
     */
    public function batchInsert(array $data, int $fensDrawId)
    {
        $insert = [];
        foreach ($data as $value) {
            $insert[] = [
                'draw_id'    => $fensDrawId,
                'area_id'    => 0, //暂不关联地域表
                'city_name'  => $value['name'],
                'number'     => $value['number'] ?? 0,
                'ratio'      => $value['ratio'] ?? '0.00',
                'created_at' => date('Y-m-d H:i:s', time()),
                'updated_at' => date('Y-m-d H:i:s', time())
            ];
        }

        $this->model->insert($insert);
    }

    /**
     * 批量更新
     *
     * @param array $data
     * @param int $fensDrawId
     * @return void
     */
    public function batchUpdate(array $data, int $fensDrawId)
    {
        foreach ($data as $value) {
            $update = [];
            empty($value['number']) ?: $update['number'] = $value['number'];
            empty($value['ratio'])  ?: $update['ratio']  = $value['ratio'];
            $update['updated_at'] = date('Y-m-d H:i:s', time());

            $this->model->where('draw_id', $fensDrawId)->where('city_name', $value['name'])->update($update);
        }
    }
}
