<?php

use Carbon\Carbon;
use Illuminate\Support\Str;
use App\Http\Helper\AlarmNotice;
use Illuminate\Support\Facades\Redis;

/**
 *  发送报警通知
 *
 * @param  string        $title  警报类型或业务类型说明, 默认使用：App\Http\Helper\NoticeErrorType::RUNNING_EXCEPTION;
 * @param  string|array  $data   详细信息
 */
function wxNotice($title, $data, $msg = '')
{
    $msg = $msg ?: AlarmNotice::template($data);

    try {
        $ckey = 'wxnotice:'.AlarmNotice::getIdentifier();
        if (Redis::exists($ckey)) {
            return false;
        }

        // 相同告警限制: 1次/分钟
        Redis::select(3);
        Redis::set($ckey, "", "EX", 60);
        AlarmNotice::send([$title, $msg]);
    } catch (Exception $e) {
        //$msg = sprintf("%s #%s, %s", $e->getFile(), $e->getLine(), $e->getMessage());
        //Log::error("wxnotice_error", $msg);
    }
}

function getErrorTemplateMessage($e)
{
    $message = $e->getMessage();

    // 判断错误信息长度是否超过1000个字符
    if (Str::length($message) > 1000) {
        $message = Str::limit($message, 1000);
    }
    return "错误文件:" . $e->getFile() . "\n" . "错误所在行:" . $e->getLine() . "\n" . "错误信息:" . $message;
}

/**
 * 备注：毫秒时间戳转为日期（Y-m-d H:i:s）
 *
 * @param $msectime
 * @return false|string
 */
function getMsecToMescdate($msectime)
{
    if (!is_numeric($msectime)) {
        return false;
    }
    $msectime = $msectime * 0.001;
    if (strstr($msectime, '.')) {
        sprintf("%01.3f", $msectime);
        list($usec, $sec) = explode(".", $msectime);
    } else {
        $usec = $msectime;
    }
    $date = date("Y-m-d H:i:s", $usec);

    return $date;
}

/**
 * 毫秒转日期（Y-m-d）
 * @param $milliseconds
 * @return bool|string
 */
function getTimeToDate($milliseconds){
    if (empty($milliseconds)) {
        return false;
    }
    $seconds = $milliseconds / 1000;

    $carbon        = Carbon::createFromTimestamp($seconds);
    $formattedDate = $carbon->format('Y-m-d');

    return $formattedDate;
}

/**
 * 翻译给定的信息
 *
 * @param  string|null  $key
 * @param  array  $replace
 * @param  string|null  $locale
 * @return \Illuminate\Contracts\Translation\Translator|string|array|null
 */
function trans_path($key = null,$path='', $replace = [], $locale = null)
{
    $prefix = '';
    if($path && !is_null($key)){
        $prefix = $path.'.';
        $key = $prefix.$key;
    }
    $res = trans($key,$replace,$locale);
    if(is_string($res) && $prefix && !is_null($key) && \Illuminate\Support\Str::startsWith($res,$prefix)){
        return \Illuminate\Support\Str::replaceFirst($prefix,'',$res);
    }
    return $res;
}

/**
 * 下划线转驼峰&NULL统一替换为''
 *
 * @param  array  $params  字符串
 *
 * @return  mixed|string
 */
if (!function_exists("convertUnderline")) {
    function convertUnderline($params, $res = [])
    {
        if (!$params) {
            return $params;
        }
        foreach ($params as $key => $value) {
            if (is_array($value)) {
                $value = convertUnderline($value);
            }
            $key = ucwords(str_replace('_', ' ', $key));
            $key = str_replace(' ', '', lcfirst($key));
            if($value === null){
                $value = '';
            }
            $res[$key] = $value;
        }

        return $res;
    }
}

if (!function_exists('convertCamelCase')) {
    /**
     * 驼峰转下划线
     *
     * @param  array  $data
     *
     * @return array
     */
    function convertCamelCase(array $data): array
    {
        if (empty($data)) {
            return $data;
        }
        $resultData = [];
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $value = convertCamelCase($value);
            }
            $key = strtolower(trim(preg_replace("/[A-Z]/", "_\\0", $key), "_"));
            $key = str_replace(' ', '', lcfirst($key));
            if ($value === null) {
                $value = '';
            }
            $resultData[$key] = $value;
        }

        return $resultData;
    }
}

/**
 * 字符串,符号转驼峰
 *
 * @param string $string
 * @param $tag
 * @return string
 */
function underscoreToCamel(string $string, $tag = '-')
{
    $parts = explode('_', $string);

    $camelCase = '';
    foreach ($parts as $part) {
        $camelCase .= \Illuminate\Support\Str::studly($part);
    }

    return $camelCase;
}

/**
 * 二进制数转多选值
 * @param $value
 * @param array $options
 */
function multiple($value, array $options)
{
    $result = [];
    $i = 0;
    foreach ($options as $option) {
        $val = pow(2, $i);
        if ($val & $value) {
            $result[] = $val;
        }
        $i++;
    }
    return $result;
}
