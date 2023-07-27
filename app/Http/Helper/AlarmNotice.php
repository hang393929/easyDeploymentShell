<?php
namespace App\Http\Helper;

use Exception;
use GuzzleHttp\Client;

class AlarmNotice
{
    /** @var string 用于缓存key的唯一标识 */
    private static $_identifier;

    public static function getIdentifier()
    {
        return static::$_identifier;
    }

    /**
     * 报警信息模板
     *
     * @param string|array $data 内容
     */
    public static function template($data)
    {
        $body         = is_array($data) ? json_encode($data, JSON_UNESCAPED_UNICODE) : $data;
        $argv         = isset($_SERVER['argv']) ? implode(' ', $_SERVER['argv']) : '';
        $occurredTime = date("Y-m-d H:i:s", time());
        $method       = $_SERVER['REQUEST_METHOD'] ?? $argv;
        $serverAddr   = $_SERVER['SERVER_ADDR'] ?? '';
        $protocol     = $_SERVER['SERVER_PROTOCOL'] ?? '';
        $host         = $_SERVER['HTTP_HOST'] ?? gethostname();
        $uri          = $_SERVER['REQUEST_URI'] ?? '';
        if ($serverAddr == '') {
            $ips = static::getServerIps();
            if (!empty($ips)) $serverAddr = $ips[0];
        }

        static::$_identifier = md5($method . $host . parse_url($uri, PHP_URL_PATH));

        return <<<EOF
                发生时间: {$occurredTime}
                Server IP: {$serverAddr}
                Protocol: {$protocol}
                Host: {$host}
                Method: {$method}
                Uri: {$uri}

                详细信息:
                $body
            EOF;
    }

    /**
     * 发送
     *
     * @param array $data 消息体 [0 => $title, 1 => $content]
     * @return bool
     */
    public static function send($data, $key = '')
    {
        $addr = config('interface.alarm.addr');
        $key  = $key ?: config('interface.alarm.key.xzq');
        if (empty($addr) || empty($key)) {
            return false;
        }

        $title    = empty($data[0]) ? NoticeErrorType::RUNNING_EXCEPTION : $data[0];
        $content  = [
            'msgtype'  => 'markdown',
            'markdown' => [
                'content' => $title . PHP_EOL . $data[1]
            ]
        ];
        $client   = new Client();
        $response = $client->post($addr . $key, [
            'verify' => false, // 关闭证书验证，兼容windows
            \GuzzleHttp\RequestOptions::JSON => $content
        ]);
        try {
            if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
                $msg = json_decode($response->getBody()->getContents(), true);
                if ($msg['errcode'] != 0) {
                    Log::error("command", '错误发生的类:' . get_class() . ", " . '请求错误提示:' . $msg['errmsg']);
                }
            } else {
                throw new Exception("请求出错，错误码为：" . $response->getStatusCode() . "错误信息为：" . $response->getBody()->getContents());
            }
        } catch (\Throwable $e) {
            Log::error("command", $e->getMessage());
        }

        return true;
    }

    /**
     * 获取服务器ip
     */
    public static function getServerIps()
    {
        try {
            exec('ifconfig -a|grep inet|grep -v 127.0.0.1|grep -v inet6|awk \'{print $2}\'|tr -d "addr:"', $ips);
            return $ips;
        } catch (\Throwable $e) {
            $msg = sprintf("%s #%s, %s", $e->getFile(), $e->getLine(), $e->getMessage());
            Log::error("command", $msg);
        }

        return false;
    }
}
