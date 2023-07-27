<?php
namespace App\Http\Factory\OutPlatform;

abstract class OutPlatformBase
{
    /**
     * 加载配置参数
     */
    //abstract public function loadConfig();

    abstract public function formatUserData($user, $data);

    abstract public function formatFanDrawData($user, $data);

    abstract public function formatVideoData($data, $list);

    abstract public function formatDataData($user, $data, $sync);

    abstract public function formatIncomeData($user, $data, $sync);
    abstract public function formatVideoDetailData($data, $list);
}
