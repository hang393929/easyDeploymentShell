<?php

namespace App\Constants;

class PlatformType
{
    public const BAIJIAHAO       = 1;
    public const BILIBILI        = 4;
    public const DOUYIN          = 5;
    public const DAYUHAO         = 6;
    public const AQIYTI          = 7;
    public const KUAISHOU        = 11;
    public const QQXIAOSHIJIE    = 12;
    public const TENGXUNSHIPIN   = 14;
    public const XIGUASHIPIN     = 18;
    public const XIAOHONGSHU     = 20;
    public const WEIXINSHIPINHAO = 21;
    public const JINGDONG        = 28;
    public const PINDUODUO       = 29;
    public const TAOBAO          = 30;

    public static $getPlatform = [
        self::BAIJIAHAO       => '百家号',
        self::BILIBILI        => 'bilibili',
        self::DOUYIN          => '抖音',
        self::DAYUHAO         => '大鱼号',
        self::KUAISHOU        => '快手',
        self::QQXIAOSHIJIE    => 'QQ小世界',
        self::TENGXUNSHIPIN   => '腾讯视频',
        self::XIGUASHIPIN     => '西瓜视频',
        self::XIAOHONGSHU     => '小红书',
        self::WEIXINSHIPINHAO => '微信视频号',
        self::JINGDONG        => '京东',
        self::PINDUODUO       => '拼多多',
        self::TAOBAO          => '淘宝',
        self::AQIYTI          => '爱奇艺',
    ];


}
