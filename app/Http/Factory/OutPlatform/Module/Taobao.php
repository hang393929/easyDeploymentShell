<?php

namespace App\Http\Factory\OutPlatform\Module;

use App\Models\FensGender;
use App\Http\Services\UserService;
use App\Exceptions\OutPlatformException;
use App\Http\Factory\OutPlatform\OutPlatformBase;

class Taobao extends OutPlatformBase
{
    private static $genderMap = [
        '男' => FensGender::TYPE_MALE,
        '女' => FensGender::TYPE_FEMALE
    ];

    public function formatUserData($user, $data)
    {
        $info = [];
        $info['nick_name'] = $data['data'][0]['userNick_new'] ?? "";
        $info['unique_3']  = $data['data'][0]['id_new'] ?? "";
        $info['fens']      = $data['data'][0]['fansCount'] ?? 0;
        $info['follow']    = $data['data'][0]['followCount'] ?? 0;
        $info['like']      = $data['data'][0]['likeCount'] ?? 0;
        $info['credit']    = $data['data'][0]['creditScore'] ?? 0;

        return $info;
    }

    /**
     * 粉丝数据解析
     *
     * @param $user
     * @param $data
     * @return array|false
     */
    public function formatFanDrawData($user, $data)
    {
        // 粉丝量
        $data = $data['data'][0];
        $info['fansCnt']    = $data['fans_info']['data']['composerSumFansCnt']['value'] ?? 0;
        $info['projectId']  = $user->project;
        $info['userUnique'] = $user->unique;
        $info['platformId'] = $user->platform;

        // 性别分布
        $info['fensGender']    = empty($data['fans_gender']['content']['data']) ? '' : $data['fans_gender']['content']['data'];
        // 年龄分布
        $info['fensAge']       = empty($data['fans_age']['content']['data']) ? '' : $data['fans_age']['content']['data'];
        // 地域分布
        $info['fansCity']      = empty($data['fans_city']['content']['data']) ? '' : $data['fans_city']['content']['data'];
        // 学历占比
        $info['fensEducation'] = empty($data['educational']['content']['data']) ? '' : $data['educational']['content']['data'];
        // 兴趣爱好
        $info['fensInterest']  = empty($data['interests']['content']['data']) ? '' : $data['interests']['content']['data'];

        return $this->haddleFanDrawData($info);
    }

    /**
     * 粉丝数据格式化
     *
     * @param $info
     * @return array
     */
    private function haddleFanDrawData($info)
    {
        if(!empty($info['fensAge'])) {
            foreach ($info['fensAge'] as $key => $age) {
                $info['fensAge'][$key] = array(
                    'range'  => $age['profileKey']['value'],
                    'number' => $age['profileValue']['value'],
                    'ratio'  => number_format($age['profileValue']['ratio']  * 100, 2, '.', '')
                );
            }

            $info['fensAge'] = array_values($info['fensAge']);
        }

        if(!empty($info['fensGender'])) {
            foreach ($info['fensGender'] as $key => $gender) {
                $info['fensGender'][$key] = array(
                    'type'   => self::$genderMap[$gender['profileKey']['value']],
                    'number' => $gender['profileValue']['value'],
                    'ratio'  => number_format($gender['profileValue']['ratio']  * 100, 2, '.', '')
                );
            }

            $info['fensGender'] = array_values($info['fensGender']);
        }

        if(!empty($info['fensInterest'])) {
            foreach ($info['fensInterest'] as $key => $interest) {
                $info['fensInterest'][$key] = array(
                    'name'   => $interest['profileKey']['value'],
                    'number' => $interest['profileValue']['value'],
                    'ratio'  => $interest['profileValue']['ratio']
                );
            }

            $info['fensInterest'] = array_values($info['fensInterest']);
        }

        if(!empty($info['fansCity'])) {
            foreach ($info['fansCity'] as $key => $city) {
                $info['fansCity'][$key] = array(
                    'name'   => $city['profileKey']['value'],
                    'number' => $city['profileValue']['value'],
                    'ratio'  => $city['profileValue']['ratio']
                );
            }

            $info['fansCity'] = array_values($info['fansCity']);
        }

        if(!empty($info['fensEducation'])) {
            foreach ($info['fensEducation'] as $key => $education) {
                $info['fensEducation'][$key] = array(
                    'name'   => $education['profileKey']['value'],
                    'number' => $education['profileValue']['value'],
                    'ratio'  => $education['profileValue']['ratio']
                );
            }

            $info['fensEducation'] = array_values($info['fensEducation']);
        }

        return $info;
    }


    public function formatVideoData($v, $list)
    {
        if (!isset($v['baseInfo']['title'])) {
            return false;
        }
        if ($v['baseInfo']['type'] != 'video') {
            return false;
        }
        $times = $v['baseInfo']['releaseTime'];

        $time = strtotime(getMsecToMescdate($times));

        $message = '--';

        if ($v['auditInfo']['status'] == 1) {
            $status = 1;
        } elseif ($v['auditInfo']['status'] == -1) {
            $status  = 2;
            $message = $v['auditInfo']['refuseDescriptions'][0]['reason'] ?? '';
        } elseif ($v['auditInfo']['status'] == 0) {
            $status = 3;
        } else {
            $status = 1;
        }
        $info['unique']         = $v['baseInfo']['id'];
        $info['unique_new']     = $v['baseInfo']['video']['videoId'] ?? '';
        $info['title']          = $v['baseInfo']['title'] ?? '';
        $info['link']           = $v['baseInfo']['video']['playUrl'] ?? '';
        $info['img']            = $v['baseInfo']['cover']['url'] ?? '';
        $info['status_message'] = $message;
        $info['status']         = $status;
        $info['play']           = $v['statisticInfo']['pvCount'] ?? 0;
        $info['recommend']      = 0;
        $info['tag']            = '--';
        $info['like']           = $v['statisticInfo']['likeCount'] ?? 0;
        $info['comment']        = $v['statisticInfo']['commentCount'] ?? 0;
        $info['collect']        = $v['statisticInfo']['collectCount'] ?? 0;
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
        if (empty($data['data'][0]['module'])) {
            throw new OutPlatformException('参数不全');
        }
        $data = $data['data'][0]['module'];
        if (!empty($data) && is_array($data)) {
            foreach ($data as $item) {
                $info['date']        = date('Y-m-d', strtotime($item['ds']));
                $info['consume_pv']  = $item['play_vv_1d'] ?? 0;
                $info['share_pv']    = $item['share_pv_1d'] ?? 0;
                $info['cmt_pv']      = $item['comment_pv_1d'] ?? 0;
                $info['fav_pv']      = $item['collect_pv_1d'] ?? 0;
                $info['like_pv']     = $item['favor_pv_1d'] ?? 0;
                $info['time_s']      = $item['play_rate_1d_complete'] * 100 ?? 0;
                $info['mcn_id']      = $sync['mcn_id'] ?? 0;
                $info['sync_id']     = $sync['sync_id'] ?? 0;
                $info['platform']    = $user['platform'];
                $info['user_id']     = $user['id'];
                $info['sync_status'] = 1;

                UserService::haddleUserLog($info);
            }
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
