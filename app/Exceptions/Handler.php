<?php
namespace App\Exceptions;

use Throwable;
use RuntimeException;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $environment = App::environment();
        $this->reportable(function (Throwable $e) use($environment) {
            // 本地环境记录日志，测试/线上由render接管
            if ($environment == 'local') {
                $message = getErrorTemplateMessage($e);
                Log::channel('daily')->info('Throwable_ERROR', [$message]);
            }
            return false;
        });
    }

    public function render($request, Throwable $exception)
    {
        $environment = App::environment();
        $message = getErrorTemplateMessage($exception);
        // 获取异常类型
        switch ($exception) {
            // 解析异常
            case $exception instanceof OutPlatformException:
                $response = $this->codeOrMsg($exception, $exception->getCode(), $exception->getMessage());
                break;
            // 参数效验异常
            case $exception instanceof ParameterException:
                $response = $this->codeOrMsg($exception, $exception->getCode(), $exception->getMessage());
                break;
            // 用户认证相关异常
            case $exception instanceof AuthorityException:
                $response = $this->codeOrMsg($exception, ResponseCode::LOGIN_FAILED, '未登录或登录超时');
                break;
            // 表单验证抛出的异常
            case $exception instanceof ValidationException:
                $response = $this->codeOrMsg($exception, Response::HTTP_INTERNAL_SERVER_ERROR, $exception->getMessage());
                break;
            // 当请求的路由不存在时抛出的异常
            case $exception instanceof NotFoundHttpException:
                $response = $this->codeOrMsg($exception, Response::HTTP_NOT_FOUND, '404 Not Found');
                break;
            // 运行时异常，请求超时等
            case $exception instanceof RuntimeException:
                $response = $this->codeOrMsg($exception, Response::HTTP_INTERNAL_SERVER_ERROR, '请求超时');
                break;
            // HTTP 相关的异常直接输出报错信息
            case $exception instanceof HttpException:
                Log::channel('daily')->info('Http request:', [$message]);
                $response = $this->codeOrMsg(
                    $exception,
                    $exception->getCode() != 0 ? $exception->getCode() : Response::HTTP_INTERNAL_SERVER_ERROR,
                    $exception->getMessage() ?: "time out"
                );
                break;
            // 队列执行超时时抛出的异常
            case $exception instanceof ProcessTimedOutException:
                wxNotice('队列/程序执行超时捕获', $message);
                $response = $this->codeOrMsg(
                    $exception,
                    $exception->getCode() != 0 ? $exception->getCode() : Response::HTTP_INTERNAL_SERVER_ERROR,
                    $exception->getMessage() ?: $message
                );
                break;
            default:
                wxNotice('系统级别异常捕获', $message);
                $response = $this->codeOrMsg(
                    $exception,
                    $exception->getCode() != 0 ? $exception->getCode() : Response::HTTP_INTERNAL_SERVER_ERROR,
                    $exception->getMessage() ?: $message
                );
                break;
        }

        //如果是本地调试输出全部错误内容
        if ($environment == 'local') {
            return parent::render($request, $exception);
        }

        return response($response);
    }

    /**
     * 输出错误信息与格式
     *
     * @param  int  $code
     * @param  string  $msg
     * @param  Throwable  $exception
     *
     * @return array
     */
    public function codeOrMsg(Throwable $exception, int $code = 0, string $msg = '', array $errors=[]): array
    {
        $msg = $msg ?:ResponseCode::getCodeMsg($code);
        return [
            'code'    => $code   ?: $exception->getCode(),
            'message' => $msg    ?: $exception->getMessage(),
            'errors'  => $errors ?: "error",
        ];
    }
}
