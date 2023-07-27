<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use App\Exceptions\ResponseCode;

abstract class ApiController extends Controller
{
    /**
     * 默认code码
     *
     * @var int
     */
    protected $code = ResponseCode::SUCCESS;

    /**
     *获取code码
     *
     */
    protected function getCode()
    {
        return $this->code;
    }

    /**
     * 通用错误返回
     *
     * @param array $data
     * @param int $code
     * @param string $message
     * @param int $statusCode
     * @param array $headers
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|Response|\Laravel\Lumen\Http\ResponseFactory
     */
    public function error(
        array  $data = [],
        int    $code = 0,
        string $message = '',
        int    $statusCode = Response::HTTP_OK,
        array  $headers = []
    )
    {
        if ($code == ResponseCode::DATA_NOT_UPDATED || $code == ResponseCode::DATA_NOT_UPDATED_SECOND) {
            return $this->success($data, $message, $headers);
        }

        $ret = [
            'code'    => $code,
            'message' => ResponseCode::message($code) . ($message == '' ? '' : ',' . $message),
            'data'    => $data
        ];

        return response($ret, $statusCode, $headers);
    }

    /**
     * 通用成功返回
     *
     * @param array $data
     * @param string $message
     * @param array $headers
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|Response|\Laravel\Lumen\Http\ResponseFactory
     */
    public function success(array $data = [], string $message = '', array $headers = [])
    {
        $ret = [
            'code'    => $this->getCode(),
            'message' => ResponseCode::message($this->getCode()) . ($message == '' ? '' : ',' . $message),
            'data'    => $data
        ];

        return response($ret, Response::HTTP_OK, $headers);
    }


}
