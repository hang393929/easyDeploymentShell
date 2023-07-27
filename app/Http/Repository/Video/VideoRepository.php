<?php
namespace App\Http\Repository\Video;

use App\Models\Video;
use Illuminate\Support\Facades\DB;
use App\Http\Repository\BaseRepository;

class VideoRepository extends BaseRepository
{

    /**
     * @var Video $model
     */
    protected $model;
    public function __construct(Video $model)
    {
        parent::__construct($model);
    }

    /**
     * 通过unique获取视频
     *
     * @param $unique
     * @return Video|\Illuminate\Database\Eloquent\Model|object|null
     */
    public function getByUnique($unique)
    {
        $unique = is_string($unique) ? $unique : (string)$unique;
        $unique = trim($unique);
        return $this->model->where('unique', $unique)->first();
    }

    /**
     * 添加月份日志
     *
     * @param $video
     * @return void
     */
    public function addVideoLog($video)
    {
        $table                = 'video_log_' . date('Ym');
        $video['update_time'] = time();
        $sql                  = "select * from $table where `date`='" . date('Y-m-d') . "' and `unique`='" . $video['unique'] . "'";
        $log                  = DB::select($sql);
        if (!$log || count($log) == 0) {
            $video['create_time'] = time();
            $video['date']        = date('Y-m-d');
            DB::connection('mysql')->table($table)->insert($video);
        } else {
            $date = is_string(date('Y-m-d')) ? date('Y-m-d') : (string)date('Y-m-d');
            if ($video['unique']) {
                $unique = is_string($video['unique']) ? $video['unique'] : (string)$video['unique'];
                unset($video['unique']);
                DB::connection('mysql')->table($table)->where('unique', $unique)->where('date', $date)->update($video);
            }
        }
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
    public function insert(array $video) {
        return $this->model->insert($video);
    }

    public function upByUnique($unique,$data)
    {
        $unique = is_string($unique) ? $unique : (string)$unique;
        $unique = trim($unique);
        return $this->model->where('unique', $unique)->update($data);
    }
}
