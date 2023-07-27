<?php

namespace App\Http\Factory\OutPlatform\Module;

use App\Http\Factory\OutPlatform\OutPlatformBase;

class Aqiyi extends OutPlatformBase
{

    public function formatUserData($user, $data)
    {

    }

    public function formatFanDrawData($user, $data)
    {

    }

    public function formatVideoData($v, $list)
    {
        return false;
        if (!$v) {
            return false;
        }
        if (!isset($v['title'])) {
            $info['recommend']  = $v['pv'] ?? 0;
            $info['play']       = $v['vv'] ?? 0;
            $info['comment']    = $v['commentNum'] ?? 0;
            $info['like']       = $v['likeNum'] ?? 0;
            $info['share']      = $v['shareNum'] ?? 0;
            $info['unique']     = $v['qipuId'] ?? 0;
            $info['unique_new'] = $v['qipuId'] ?? '';
            return $info;
        }

        $message = '';
        if ($v['videoStatus'] != '已发布') {
            $status  = 2;
            $message = $v['mpFailShowText'] ?? '';
            if ($v['videoStatus'] == '视频转码失败') {
                $message = '视频转码失败';
            }
            if ($v['videoStatus'] == '待审核') {
                $status = 3;
            }
        } else {
            $status = 1;
        }
        $info['unique']         = $v['qipuId'];
        $info['unique_new']     = $v['qipuId'] ?? '';
        $info['title']          = $v['displayName'] ?? '';
        $info['link']           = $v['pageUrl'] ?? '';
        $info['img']            = $v['coverImage'] ?? '';
        $info['status_message'] = $message;
        $info['status']         = $status;
        $info['play']           = $v['vvNum'] ?? 0;
        $info['like']           = $v['upCount'] ?? 0;
        $info['comment']        = $v['commentCount'] ?? 0;
        $info['recommend']      = 0;
        $info['publish_time']   = $v['publishTime'] == null ? time() : strtotime($v['publishTime']);

        return $info;
    }

    public function formatDataData($user, $data, $sync)
    {

    }

    public function formatIncomeData($user, $data, $sync)
    {

    }

    public function formatVideoDetailData($data, $list)
    {
        // TODO: Implement formatVideoDetailData() method.
    }
}
