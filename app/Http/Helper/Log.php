<?php
/**
 * Email: hjqxlhk393929@gmail.com
 * Desc: [自定义log类：env本地：local,测试：dev,线上：online]
 */
namespace App\Http\Helper;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Request;

/**
 * App\Http\Helper\Log
 *
 * @method static \App\Http\Helper\Log error($name, $arguments)
 * @method static \App\Http\Helper\Log success($name, $arguments)
 * @method static \App\Http\Helper\Log debug($name, $arguments)
 * @method static \App\Http\Helper\Log info($name, $arguments)
 * @de
 */
class Log
{
    const BASE_PATH = '..'.DIRECTORY_SEPARATOR.'xzqLog';
    const CLI_PATH  = '..'.DIRECTORY_SEPARATOR.'xzqLogCli';

    /**
     * 添加
     *
     * @param $path  (例如:fensJobHaddleError/) 会创建fensJobHaddleError文件目录，存放当前时间的log
     * @param $info
     * @param $type
     * @return void
     * @throws \Exception
     * @desc $path以"/"分隔目录，目录下存放日期格式的文件
     * @example Log::add('fensJobHaddleError/', '执行失败，错误信息....');
     */
	public static function add($path = 'other', $info = null, $type = 'error')
	{
		$logger = new Logger('log');
		$logger->pushHandler(
			new StreamHandler(
				storage_path('logs/'.$path.date('Y-m-d').'.log'),
				Logger::INFO
			)
		);

        $logger->info($info. '<br>');
	}

    /**
     * 执行（队列）
     *
     * @param $type
     * @param $path
     * @param $message
     * @param $file
     * @return void
     */
	private static function put($type, $path, $message, $file = 'shellPath')
	{
		if ($path && $message && $type) {
			if (strtolower(php_sapi_name()) == 'cli') {
				$host = 'phpShell';
				$logPath = 'shell=>'.$file;
				$messages = $logPath;
                $ip = '127.0.0.1';
                if (is_array($message)) {
                    $content = json_encode($message, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
                } else {
                    $content = $message;
                }
			} else {
				$host = Request::header('host');
				$logPath = Request::method().'=>'.Request::getUri();
				$messages = $logPath;
				if (is_array($message)) {
				    $content = json_encode($message, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
                } else {
				    $content = $message;
                }
                $ip = @$_SERVER['SERVER_ADDR'];
			}
			self::saveLog((object)[
				'path' => $path,
				'method' =>  Request::method(),
				'message' => $messages ,
				'content' => $content,
				'type' => $type,
				'host' => $host,
                'urlPath' => Request::getUri(),
                'time' => date('Y-m-d H:i:s'),
                'ip' => $ip,
			]);
		} else {
			self::put('error', 'writeLog', 'Log参数不完整');
		}
	}

	/**
	 * @param $name
	 * @param $arguments
	 * @throws \Exception
	 * @desc 将 fatal, error, success, debug, info 影射成写入不同等级的log
	 */
	public static function __callStatic(string $name, $arguments)
	{
		if (in_array($name, ['fatal', 'error', 'success', 'debug', 'info'])) {
            self::put($name, ...$arguments);
		} else {
			self::put('error', 'writeLog', 'Log::'.$name.'不存在');
			throw new \Exception('不存在的方法');
		}
	}


    /**
     * 保存
     *
     * @param $data
     * @return void
     */
	public static function saveLog($data)
	{
	    $model = php_sapi_name() == 'cli' ? 'cli' : 'fpm-fcgi';
	    $dir = self::getLogPath($model);
        //$dir = (strtolower(php_sapi_name()) == 'cli' ? self::CLI_PATH : self::BASE_PATH);
		$errorType = $data->type;

		$host = str_replace(':', '_' ,$data->host);
		$path = trim(trim($data->path, '/'),'\\');
        $message = json_encode([
            'method' => $data->method,
            'type' => $errorType,
            'urlPath' => $data->urlPath,
            'host' => $host,
            'content' => $data->content,
            'ip' => $data->ip,
            'time' => $data->time,
            'message' => $data->message,
            'logPath' => $path
        ], JSON_UNESCAPED_UNICODE);
	    // 转换绝对+相对路径
        //$rootPath = str_replace('public', '', public_path());
        //$dirPath = base_path($dir.'/'.$errorType); 有跨站攻击风险
        //$dirPath = $rootPath.'/'.$dir.'/'.$errorType;
        $dirPath = $dir.'/'.$errorType;
        if (!file_exists($dirPath)) {
			mkdir($dirPath, 0777, true);
		}
		if ($data->type == 'fatal') { //致命错误,通知相关人员
			self::sendMessage($message);
		}
        $savePath = self::getSavePath($dirPath);
		file_put_contents($savePath, $message.PHP_EOL, FILE_APPEND);
	}

    /**
     * 发送消息
     *
     * @param string|array $message
     * @param string $title
     * @param string $noticeType
     * @return void
     */
	public static function sendMessage($message, string $title = NoticeErrorType::RUNNING_EXCEPTION, string $noticeType = 'wx') {
	    return;
        switch ($noticeType) {
            case 'wx':
                wxNotice($title, $message);
                break;
            case 'email':
                //emailNotice($title, $message);
                break;
            case 'sms':
                //smsNotice($title, $message);
                break;
            default:
                break;
        }
	}

    /**
     * 获取日志路径
     *
     * @param string $model
     * @desc 暂统一放在storage/logs下
     * @return string
     */
	public static function getLogPath(string $model) {
        return base_path('storage/logs');

        if(App::environment() == 'local'){
            return base_path('storage/logs');
        }else{
            return ($model == 'cli') ? self::CLI_PATH : self::BASE_PATH;
        }
    }

    /**
     * 获取保存路径
     *
     * @param string $dirPath
     * @return string
     */
    public static function getSavePath(string $dirPath) {
        return $dirPath . '/laravel-'.date('Y-m-d').'.log';
    }

}
