<?php

namespace App\Http\Helper;

class NoticeErrorType
{
    const RUNNING_EXCEPTION         = "程序运行异常";
    const COMMUNICATION_EXCEPTION   = "系统间通信异常";
    const DATABASE_ACCESS_EXCEPTION = "数据库操作异常";
    const LIMIT_EXCEEDED            = "服务超限提醒";
}
