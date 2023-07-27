<?php
namespace App\Exceptions;

use Exception;
use Throwable;

class OutPlatformException extends Exception
{
    /**
     * 解析异常
     *
     * @param  string  $message
     * @param  int  $code
     * @param  \Throwable|null  $previous
     */
    public function __construct(string $message, $code = ResponseCode::DATA_EXCEPTION, Throwable $previous = null) {
        $message = empty($message) ? ResponseCode::message($code) : $message;
        $code    = isset(ResponseCode::$codeMsg[$code]) ? $code : ResponseCode::DATA_EXCEPTION;
        parent::__construct($message, $code, $previous);
    }
}
