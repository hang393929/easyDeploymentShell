<?php
namespace App\Http\Helper;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class Oss
{
    /**
     * 是否有效的网络Url
     *
     * @param $url
     * @return bool
     */
    public static function isValidNetworkUrl($url)
    {
        try {
            stream_context_set_default([
                'ssl' => [
                    'verify_host' => false,
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                ],
            ]);
            $headers = get_headers($url);
            return stripos($headers[0], "200 OK") ? true : false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 获取oss图片并上传
     * Funtion:getOssImg
     * DATE:2023/5/31
     * @param $url
     * @return false|string
     */
    public static function getOssImg($url)
    {
        if (!$url) {
            return false;
        }
        $file = self::download($url, storage_path("app/"));

        $disk = Storage::disk('cosv5');

        $path = '/cover/' . date('Y-m-d') . '/' . md5($url) . '.jpg';//date('Y-m-d')

        try {
            $res = $disk->putFileAs($path, $file, '');
        } catch (\Exception $e) {
            wxNotice('图片上传异常--getOssImg', '当前抓取URL:' . $url . ','.getErrorTemplateMessage($e));
        }

        //删除本地文件
        @unlink($file);

        return is_bool($res) ? '' : $path;
    }

    /**
     * 下载
     *
     * @param $url
     * @param $path
     * @return false|string
     */
    private static function download($url, $path = '')
    {
        header('Access-Control-Allow-Origin:*');
        $response = Http::withoutRedirecting()
            ->withoutVerifying()
            ->get($url);

        if (!$response->ok()) {
            return false;
        }
        $md5      = md5($url . time());
        $filename = $md5 . ".jpg"; //文件名
        file_put_contents($path . $filename, $response->body());

        return $path . $filename;
    }
}
