<?php

namespace App\Http\Factory\OutPlatform;

use App\Constants\PlatformType;
use App\Exceptions\OutPlatformException;
use App\Http\Factory\OutPlatform\Module\{
    Aqiyi,
    Baijiahao,
    Bilibili,
    Dayuhao,
    Douyin,
    Kuaishou,
    QQxiaoshijie,
    Tengxunshipin,
    Xiaohongshu,
    Xiguashipin,
    Weixinshipinhao,
    Jingdong,
    Pinduoduo,
    Taobao
};

class OutPlatformFactory
{
    private static $platforms = [
        PlatformType::BAIJIAHAO       => Baijiahao::class,
        PlatformType::BILIBILI        => Bilibili::class,
        PlatformType::DOUYIN          => Douyin::class,
        PlatformType::DAYUHAO         => Dayuhao::class,
        PlatformType::KUAISHOU        => Kuaishou::class,
        PlatformType::QQXIAOSHIJIE    => QQxiaoshijie::class,
        PlatformType::TENGXUNSHIPIN   => Tengxunshipin::class,
        PlatformType::XIGUASHIPIN     => Xiguashipin::class,
        PlatformType::XIAOHONGSHU     => Xiaohongshu::class,
        PlatformType::WEIXINSHIPINHAO => Weixinshipinhao::class,
        PlatformType::JINGDONG        => Jingdong::class,
        PlatformType::PINDUODUO       => Pinduoduo::class,
        PlatformType::TAOBAO          => Taobao::class,
        PlatformType::AQIYTI          => Aqiyi::class,
    ];

    /**
     * 平台是否存在
     *
     * @param int $type 平台类型
     * @return bool
     */
    public static function hasModule(int $type)
    {
        return isset(static::$platforms[$type]);
    }

    /**
     * 注册模块
     *
     * @param int $type 平台类型
     * @return mixed
     */
    public static function register(int $type, string $method = '')
    {
        if (self::hasModule($type)) {
            return new self::$platforms[$type]();
        }

        $msg = $method ? '方法:' . $method . '不存在' : '';
        throw new OutPlatformException("平台类型： {$type} not exist" . $msg);
    }

    /**
     * 处理静态调用
     *
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    public static function __callStatic($method, $parameters)
    {
        $adapter = self::register($parameters[0]);
        if (method_exists($adapter, $method)) {
            array_shift($parameters);
            return call_user_func_array([$adapter, $method], $parameters);
        }
    }
}
