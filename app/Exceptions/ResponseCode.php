<?php

namespace App\Exceptions;

class ResponseCode
{

    /**
     * 通用code
     */
    const SUCCESS                        = 200;
    const DATA_NOT_UPDATED               = 10000;
    const USER_NOT_FOUND                 = 10001;
    const SYNC_USER_FAILED               = 10002;
    const INCOMPLETE_SYNC_DATA           = 10003;
    const ACCOUNT_ALREADY_EXISTS         = 10004;
    const DATA_EXCEPTION                 = 10005;
    const DATA_NOT_UPDATED_SECOND        = 10006;
    const LOGIN_FAILED                   = 10007;
    const BINDING_FAILED                 = 10008;
    const WRITE_TASK_FAILED              = 11000;
    const UNBIND_FAILED                  = 10015;
    const REQUEST_FAILED                 = 12000;
    const ACCOUNT_LIMIT_EXCEEDED         = 13000;
    const VIDEO_LIST_TIME_LIMIT_OR_EMPTY = 14000;
    const ACCOUNT_LIMIT_EXCEEDED_SECOND  = 14001;
    const INVALID_PARAMETER              = 15000;
    const PARAMETER_EXCEPTION            = 15001;

    const ERROR                          = 400;
    const VALIDATION_ERROR               = 422;
    const SYSTEM_ERROR                   = 502;

    /**
     * code消息
     *
     * @var string[]
     */
    public static $codeMsg = [
        self::DATA_NOT_UPDATED               => '数据没有更新',
        self::USER_NOT_FOUND                 => '用户不存在',
        self::SYNC_USER_FAILED               => '同步用户失败',
        self::INCOMPLETE_SYNC_DATA           => '同步数据不完整',
        self::ACCOUNT_ALREADY_EXISTS         => '账号已经存在',
        self::DATA_EXCEPTION                 => '数据异常',
        self::DATA_NOT_UPDATED_SECOND        => '数据没有更新',
        self::LOGIN_FAILED                   => '登录失败',
        self::BINDING_FAILED                 => '绑定失败',
        self::WRITE_TASK_FAILED              => '写入任务失败',
        self::UNBIND_FAILED                  => '解绑失败',
        self::REQUEST_FAILED                 => '请求失败',
        self::ACCOUNT_LIMIT_EXCEEDED         => '账号数量超过上线',
        self::VIDEO_LIST_TIME_LIMIT_OR_EMPTY => '视频列表超过时间限制或为空',
        self::ACCOUNT_LIMIT_EXCEEDED_SECOND  => '账号数量超限',
        self::INVALID_PARAMETER              => '参数错误',
        self::PARAMETER_EXCEPTION            => '参数异常',
        self::SUCCESS                        => '提交成功'
    ];

    /**
     * 获取code信息
     *
     * @param int $code
     * @return string
     */
    public static function message(int $code): string
    {
        return self::$codeMsg[$code] ?? '系统异常';
    }
}
