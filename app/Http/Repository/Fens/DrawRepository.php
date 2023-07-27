<?php
namespace App\Http\Repository\Fens;

use App\Models\FensDraw;
use App\Http\Repository\BaseRepository;
use Carbon\Carbon;


class DrawRepository extends BaseRepository
{

    /**
     * @var FensDraw $model
     */
    protected $model;
    public function __construct(FensDraw $model)
    {
        parent::__construct($model);
    }


    /**
     * 获取唯一粉丝数据
     *
     * @param string $unique
     * @param int $platformId
     * @param int $projectId
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|object|null
     */
    public function getFensDrowOnly(string $unique, int $platformId, int $projectId)
    {
        return $this->model->where('user_unique', $unique)
            ->where('platform_id', $platformId)
            ->where('project_id', $projectId)
            ->first();
    }

    /**
     * 根据当前对象更新粉丝数
     *
     * @param FensDraw $model
     * @param int $fansCnt
     * @return bool
     */
    public function updateFansCntByModel(FensDraw $model, int $fansCnt = 0)
    {
        return $model->update(['fans_cnt' => $fansCnt]);
    }

    /**
     * 新增粉丝数据
     *
     * @param string $userUnique
     * @param int $platformId
     * @param int $projectId
     * @param int $fansCnt
     * @return int
     */
    public function insertFensDraw(string $userUnique, int $platformId, int $projectId, int $fansCnt = 0)
    {
        $time = Carbon::now()->toDateTimeString();
        return $this->model->insertGetId([
            'user_unique' => $userUnique,
            'platform_id' => $platformId,
            'project_id'  => $projectId,
            'fans_cnt'    => $fansCnt,
            'created_at'  => $time,
            'updated_at'  => $time
        ]);
    }
}
