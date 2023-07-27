<?php

namespace App\Http\Factory\OutPlatform\Module;

use App\Http\Services\UserService;
use App\Exceptions\OutPlatformException;
use App\Http\Factory\OutPlatform\OutPlatformBase;

class Pinduoduo extends OutPlatformBase
{

    public function formatUserData($user, $data)
    {
        $info = [];
        $info['nick_name'] = $data['data'][0]['name_new'] ?? "";
        $info['unique_3']  = $data['data'][0]['anchorUid_new'] ?? "";
        $info['fens']      = $data['data'][0]['fansNumDataTip'] ?? 0;

        return $info;
    }

    public function formatFanDrawData($user, $data)
    {

    }

    public function formatVideoData($v, $list)
    {
        if (!$v) {
            return false;
        }
        $message = '--';

        //$v['status'] == 300 未通过  202 通过  203 审核中
        if ($v['status'] == 300) {
            $status = 2;
        } elseif ($v['status'] == 202) {
            $status = 1;
        } else {
            $status = 1;//3;
        }
        $publishTime = substr($v['publishTime'], 0, 10);

        $info['unique']         = $v['feedId'];
        $info['unique_new']     = $v['feedId'] ?? '';
        $info['title']          = $v['desc'] ?? '';
        $info['link']           = $v['mediaUrl'] ?? '';
        $info['img']            = $v['coverUrl'] ?? '';
        $info['status_message'] = $message;
        $info['status']         = $status;
        $info['play']           = $v['vv'] ?? 0;
        $info['recommend']      = 0;
        $info['tag']            = '--';
        $info['publish_time']   = $publishTime;
        $info['like']           = $v['likes'] ?? 0;
        $info['comment']        = $v['comments'] ?? 0;

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
        if (empty($data['data'][0]['daysStatistic'])) {
            throw new OutPlatformException('参数不全');
        }
        $data          = $data['data'][0]['daysStatistic'];
        $like_count    = array_column($data[1], 'value', 'date');
        $comment_count = array_column($data[2], 'value', 'date');
        $share_count   = array_column($data[3], 'value', 'date');
        $follow_count  = array_column($data[4], 'value', 'date');
        foreach ($data[0]['dayValues'] as $item) {
            $info['date']        = $item['date'];
            $info['user_id']     = $user['id'];
            $info['consume_pv']  = $item['value'] ?? 0;
            $info['share_pv']    = $share_count[$item['date']] ?? 0;
            $info['like_pv']     = $like_count[$item['date']] ?? 0;
            $info['follow_add']  = $follow_count[$item['date']] ?? 0;
            $info['cmt_pv']      = $comment_count[$item['date']] ?? 0;
            $info['mcn_id']      = $sync['mcn_id'] ?? 0;
            $info['sync_id']     = $sync['sync_id'] ?? 0;
            $info['platform']    = $user['platform'];
            $info['sync_status'] = 1;

            UserService::haddleUserLog($info);
        }
    }

    public function formatIncomeData($user, $data, $sync)
    {

    }

    public function formatVideoDetailData($data, $list)
    {
        // TODO: Implement formatVideoDetailData() method.
    }
}
