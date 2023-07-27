<?php
namespace App\Exceptions;

use Exception;
use Throwable;

class ParameterException extends Exception
{

    /**
     * 异常调用
     *
     * @param  string  $message
     * @param  int  $code
     * @param  \Throwable|null  $previous
     */
    public function __construct(string $message, $code = ResponseCode::PARAMETER_EXCEPTION, Throwable $previous = null)
    {
        $message = empty($message) ? ResponseCode::message($code) : $message;
        $code    = isset(ResponseCode::$codeMsg[$code]) ? $code : ResponseCode::ERROR;
        parent::__construct($message, $code, $previous);
    }
}
