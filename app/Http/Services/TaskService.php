<?php

namespace App\Http\Services;

use App\Exceptions\OutPlatformException;
use App\Http\Repository\Task\PushTaskRepository;

class TaskService
{

    /**
     * 根据根据parent_id及platform获取task
     *
     * @param int $pushAllId
     * @param int $platform
     * @return array
     */
    public static function getPushTaskByPidAndPlatform(int $pushAllId, int $platform)
    {
        $data = app(PushTaskRepository::class)->gtByPidAndPlatform($pushAllId, $platform);

        return $data ? $data->toArray() : [];
    }

    /**
     * 通过push_task_id更新push_task信息
     *
     * @param int $pushTaskId
     * @param array $data
     * @return mixed
     * @throws OutPlatformException
     */
    public static function updatePushTaskUniqueNewById(int $pushTaskId, array $data) {
        if(!$pushTaskId || !$data) {
            throw new OutPlatformException('参数异常');
        }
        return app(PushTaskRepository::class)->updateUniqueNewById($pushTaskId, $data);
    }
}
