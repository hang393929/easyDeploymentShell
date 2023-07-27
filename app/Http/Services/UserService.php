<?php
namespace App\Http\Services;

use App\Http\Repository\User\UserRepository;
use App\Http\Repository\User\UserLogRepository;
use App\Http\Repository\User\SyncUserRepository;

class UserService
{
    /**
     * 通过unquie获取用户
     *
     * @param string $unique
     * @return false
     */
    public static function getUserByUnique(string $unique) {
        if(!$unique) {
           return false;
        }

        return app(UserRepository::class)->selectByWhereOnly('unique', $unique);
    }

    /**
     * 通过id获取用户
     *
     * @param $ids
     * @return mixed
     */
    public static function getUserByIds($ids) {
        return app(UserRepository::class)->getUserByIds($ids);
    }

    /**
     * 通过id获取平台用户
     *
     * @param $ids
     * @return mixed
     */
    public static function getSyncUserByIds($ids) {
        return app(SyncUserRepository::class)->getSyncUserByIds($ids);
    }

    /**
     * 效验sync用户是否存在
     *
     * @param int $id
     * @return mixed
     */
    public static function checkSyncUserIsExist(int $id) {
        return app(SyncUserRepository::class)->getValueById($id);
    }

    /**
     * 检测用户是否存在
     *
     * @param int $userId
     * @param bool $return true:返回$user对象,fasle:返回布尔类型的true
     * @return bool|mixed
     */
    public static function checkUserIsExistById(int $userId, bool $return = false) {
        $user = self::getUserByIds($userId);
        if (!$user) {
            return false;
        }

        $syncId = self::checkSyncUserIsExist($user['user_id']);
        if (!$syncId) {
            return false;
        }

        return $return ? $user : true;
    }

    /**
     * 处理userLog粉丝相关老数据
     *
     * @param array $info
     * @return void
     */
    public static function haddleUserLog(array $info) {
        $userLog = app(UserLogRepository::class)->getByDateAndUserId($info['date'], $info['user_id']);
        if($userLog) {
            app(UserLogRepository::class)->updateByModel($userLog, $info);
        } else {
            app(UserLogRepository::class)->insert($info);
        }
    }
}
