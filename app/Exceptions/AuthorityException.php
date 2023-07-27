<?php
namespace App\Exceptions;

use Exception;
use Throwable;

class AuthorityException extends Exception
{

    /**
     * 鉴权异常
     *
     * @param  string  $message
     * @param  int  $code
     * @param  \Throwable|null  $previous
     */
    public function __construct(string $message, $code = ResponseCode::SYSTEM_ERROR, Throwable $previous = null) {
        $message = empty($message) ? ResponseCode::message($code) : $message;
        $code    = isset(ResponseCode::$codeMsg[$code]) ? $code : ResponseCode::SYSTEM_ERROR;
        parent::__construct($message, $code, $previous);
    }
}
