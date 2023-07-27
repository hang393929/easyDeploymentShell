<?php

namespace App\Http\Factory\OutPlatform\Module;

use App\Http\Services\UserService;
use App\Exceptions\OutPlatformException;
use App\Http\Factory\OutPlatform\OutPlatformBase;

class Jingdong extends OutPlatformBase
{

    public function formatUserData($user, $data)
    {
        $info = [];
        $info['nick_name'] = $data['data'][0]['talentName_new'] ?? "";
        $info['unique_3']  = $data['data'][0]['talentId_new'] ?? "";
        $info['follow']    = $data['data'][0]['followerNum'] ?? 0;
        $info['credit']    = $data['data'][0]['ability']['totalScore'] ?? '';

        return $info;
    }

    public function formatFanDrawData($user, $data)
    {

    }

    public function formatVideoData($v, $list)
    {
        $time    = strtotime($v['publishTime']);
        $message = '--';
        if ($v['contributeStatus'] == 4) {
            $status  = 2;
            $message = $v['offerReason'] ?? '';
        } elseif ($v['status'] == 50) {
            $status = 1;
        } else {
            $status = 1;//3;
        }
        $info['unique']     = $v['id'] ?? '';
        $info['unique_new'] = $v['id'] ?? '';
        $info['title']      = $v['title'] ?? '';
        $info['link']       = 'https://h5.m.jd.com/active/faxian/video/index.html?id=' . $v['id'] . '&style=' . $v['style'] . '&playtype=5&type=code';
        if ($v['indexImage']) {
            $info['img'] = 'https://m.360buyimg.com/' . $v['indexImage'] ?? '';
        } else {
            $info['img'] = '';
        }
        $info['status_message'] = $message;
        $info['tag']            = '--';
        $info['status']         = $status;
        $info['recommend']      = $v['recommend'];
        $info['publish_time']   = $time;

        return $info;
    }

    /**
     * 回调数据解析+入库
     *
     * @param $user
     * @param $data
     * @param $sync
     * @return void
     * @throws OutPlatformException
     */
    public function formatDataData($user, $data, $sync)
    {
        if (!$data) {
            throw new OutPlatformException('参数不全');
        }
        $info['date']        = date('Y-m-d');
        $info['consume_pv']  = $data['qualityVideoNum'] ?? 0;
        $info['cmt_pv']      = $data['contentCommentPv'] ?? 0;
        $info['share_pv']    = $data['contentSharePv'] ?? 0;
        $info['fav_pv']      = $data['contentCollectPv'] ?? 0;
        $info['like_pv']     = $data['contentLikePv'] ?? 0;
        $info['mcn_id']      = $sync['mcn_id'] ?? 0;
        $info['sync_id']     = $sync['sync_id'] ?? 0;
        $info['platform']    = $user['platform'];
        $info['user_id']     = $user['id'];
        $info['sync_status'] = 1;

        UserService::haddleUserLog($info);
    }

    public function formatIncomeData($user, $data, $sync)
    {

    }

    public function formatVideoDetailData($data, $list)
    {
        // TODO: Implement formatVideoDetailData() method.
    }
}
