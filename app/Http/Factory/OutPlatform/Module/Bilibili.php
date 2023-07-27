<?php
namespace App\Http\Factory\OutPlatform\Module;

use App\Http\Helper\Log;
use App\Models\FensGender;
use App\Http\Services\UserService;
use App\Exceptions\OutPlatformException;
use App\Http\Factory\OutPlatform\OutPlatformBase;

class Bilibili extends OutPlatformBase
{
    private static $ageMap = [
        'age_one'   => '0-16',
        'age_two'   => '16-25',
        'age_three' => '25-40',
        'age_four'  => '40以上'
    ];

    private static $genderMap = [
        'male'   => FensGender::TYPE_MALE,
        'female' => FensGender::TYPE_FEMALE
    ];

    public function formatUserData($user, $data)
    {
        $info = [];
        $info['nick_name']  = $data['data'][0]['info']['uname_new'] ?? "";
        $info['unique_3']   = $data['data'][0]['info']['mid_new'] ?? "";
        $info['level']      = !empty($data['data'][0]['info']['level']) ? $data['data'][0]['info']['level'] : 0;
        $info['credit']     = $data['data'][0]['info']['credit'] ?? '';
        $info['like']       = $data['data'][0]['stat']['total_like'] ?? 0;
        $info['fens']       = $data['data'][0]['stat']['total_fans'] ?? 0;
        $info['total_play'] = $data['data'][0]['stat']['total_click'] ?? 0;

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
        $data = $data['data'][0];
        $info['projectId']  = $user->project;
        $info['userUnique'] = $user->unique;
        $info['platformId'] = $user->platform;
        $info['fansCnt']    = $data['fan']['summary']['total'] ?? 0; // 粉丝数

        // 粉丝年龄分布
        $info['fensAge']      = empty($data['portrayal']['fans_age']) ? '' : $data['portrayal']['fans_age'];
        // 粉丝性别分布
        $info['fensGender']   = empty($data['portrayal']['fans_gender']) ? '' : $data['portrayal']['fans_gender'];
        // 粉丝兴趣分布
        $info['fensInterest'] = empty($data['portrayal']['viewer_ty']) ? '' : $data['portrayal']['viewer_ty'];
        // 粉丝地区分布
        $info['fensArea']     = empty($data['portrayal']['viewer_area']) ? '' : $data['portrayal']['viewer_area'];

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
            $total = array_sum($info['fensAge']);
            foreach ($info['fensAge'] as $key => $age) {
                $ratio = $total == 0 ? 0 : number_format(($age / $total) * 100, 2, '.', '');

                $info['fensAge'][$key] = array(
                    'range'  => self::$ageMap[$key],
                    'number' => $age,
                    'ratio'  => $ratio
                );
            }

            $info['fensAge'] = array_values($info['fensAge']);
        }

        if(!empty($info['fensGender'])) {
            $total = array_sum($info['fensGender']);
            foreach ($info['fensGender'] as $key => $gender) {
                $ratio = $total == 0 ? 0 : number_format(($gender / $total) * 100, 2, '.', '');

                $info['fensGender'][$key] = array(
                    'type'   => self::$genderMap[$key] ?? FensGender::TYPE_OTHER,
                    'number' => $gender,
                    'ratio'  => $ratio
                );
            }

            $info['fensGender'] = array_values($info['fensGender']);
        }

        if(!empty($info['fensInterest'])) {
            $total = array_sum(array_column($info['fensInterest'], 'count'));
            foreach ($info['fensInterest'] as $key => $interest) {
                $ratio = $total == 0 ? 0 : number_format(($interest['count'] / $total) * 100, 2, '.', '');

                $info['fensInterest'][$key] = array(
                    'name'   => $interest['tag_name'],
                    'number' => $interest['count'],
                    'ratio'  => $ratio
                );
            }

            $info['fensInterest'] = array_values($info['fensInterest']);
        }

        if(!empty($info['fensArea'])) {
            $total = array_sum(array_column($info['fensArea'], 'count'));
            foreach ($info['fensArea'] as $key => $area) {
                $ratio = $total == 0 ? 0 : number_format(($area['count'] / $total) * 100, 2, '.', '');

                $info['fensArea'][$key] = array(
                    'name'   => $area['location'],
                    'number' => $area['count'],
                    'ratio'  => $ratio
                );
            }

            $info['fensArea'] = array_values($info['fensArea']);
        }

        return $info;
    }

    public function formatVideoData($v, $list)
    {
        if (!$v) {
            return false;
        }
        $message = '--';
        if ($v['Archive']['state'] == '-4') {
            $status  = 2;
            $message = $v['Archive']['reject_reason'] ?? '';
            if ($v['Archive']['reject_reason'] == '稿件中发现1个问题。') {
                $message = $v['Videos'][0]['reject_reason'] ?? '';
            }
        } elseif ($v['Archive']['state'] == 0) {
            $status = 1;
        } else {
            $status = 1;//3
        }
        if (empty($v['Archive']['ptime'])) {
            Log::debug('videolog', '==bili==ptime数据失败==' . json_encode($v));
            return false;
        }

        $info['unique']         = $v['Archive']['aid'];
        $info['unique_new']     = $v['Archive']['aid'] ?? '';
        $info['title']          = $v['Archive']['title'] ?? '';
        $info['link']           = 'https://www.bilibili.com/video/' . $v['Archive']['bvid'] . '/';
        $info['img']            = $v['Archive']['cover'] ?? '';
        $info['status_message'] = $message;
        $info['status']         = $status;
        $info['play']           = $v['stat']['view'] ?? 0;
        $info['recommend']      = 0;
        $info['tag']            = '--';
        $info['publish_time']   = $v['Archive']['ptime'];
        $info['like']           = $v['stat']['like'] ?? 0;
        $info['share']          = $v['stat']['share'] ?? 0;
        $info['comment']        = $v['stat']['reply'] ?? 0;
        $info['collect']        = $v['stat']['favorite'] ?? 0;

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
        if (empty($data['data'][0])) {
            throw new OutPlatformException('参数不全');
        }

        $data    = $data['data'][0];
        $data_4  = $data['data_4']['tendency'] ?? [];
        $data_6  = array_column($data['data_6']['tendency'] ?? [], 'total_inc', 'date_key');
        $data_7  = array_column($data['data_7']['tendency'] ?? [], 'total_inc', 'date_key');
        $data_8  = array_column($data['data_8']['tendency'] ?? [], 'total_inc', 'date_key');
        $data_9  = array_column($data['data_9']['tendency'] ?? [], 'total_inc', 'date_key');
        $data_10 = array_column($data['data_10']['tendency'] ?? [], 'total_inc', 'date_key');
        $data_11 = array_column($data['data_11']['tendency'] ?? [], 'total_inc', 'date_key');
        $data_12 = array_column($data['data_12']['tendency'] ?? [], 'total_inc', 'date_key');
        if (!empty($data_4)) {
            foreach ($data_4 as $item) {
                $info['date'] = date('Y-m-d', $item['date_key']);
                $info['consume_pv'] = !empty($item['total_inc']) && $item['total_inc'] > 0 ? $item['total_inc'] : 0;
                $info['follow_add'] = !empty($data_6[$item['date_key']]) && $data_6[$item['date_key']] > 0 ? $data_6[$item['date_key']] : 0;
                $info['fens'] = !empty($data_7[$item['date_key']]) && $data_7[$item['date_key']] > 0 ? $data_7[$item['date_key']] : 0;
                $info['follow_cancel'] = !empty($data_8[$item['date_key']]) && $data_8[$item['date_key']] > 0 ? $data_8[$item['date_key']] : 0;
                $info['like_pv'] = !empty($data_9[$item['date_key']]) && $data_9[$item['date_key']] > 0 ? $data_9[$item['date_key']] : 0;
                $info['fav_pv'] = !empty($data_10[$item['date_key']]) && $data_10[$item['date_key']] > 0 ? $data_10[$item['date_key']] : 0;
                $info['cmt_pv'] = !empty($data_11[$item['date_key']]) && $data_11[$item['date_key']] > 0 ? $data_11[$item['date_key']] : 0;
                $info['share_pv'] = !empty($data_12[$item['date_key']]) && $data_12[$item['date_key']] > 0 ? $data_12[$item['date_key']] : 0;
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
