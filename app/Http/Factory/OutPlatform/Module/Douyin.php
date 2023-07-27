<?php
namespace App\Http\Factory\OutPlatform\Module;

use App\Http\Helper\Log;
use App\Models\FensGender;
use App\Http\Services\UserService;
use App\Events\OldFensInUserCallBack;
use App\Exceptions\OutPlatformException;
use App\Http\Factory\OutPlatform\OutPlatformBase;

class Douyin extends OutPlatformBase
{

    /**
     * 用户数据解析
     *
     * @param $data
     * @return mixed
     */
    public function formatUserData($user, $data)
    {
        $info['nick_name'] = $data['data']['user_profile']['nick_name_new'] ?? "";
        $info['unique_3']  = $data['data']['user_profile']['unique_id_new'] ?? "";
        $fens              = $data['data']['user_profile']['follower_count'] ?? 0;
        $info['fens']      = $fens > 0 ? $fens : 0;

        // 触发粉丝事件（兼容老粉丝数据）
        event(new OldFensInUserCallBack($user, $data));

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
        // 匹配两种类型
        if (empty($data['fans_cnt']) && empty($data['data'][0])) {
            throw new OutPlatformException('参数不全');
        }

        if (!empty($data['data'][0])) {
            $data = $data['data'][0];
        }

        // 粉丝数量
        $info['fansCnt']    = $data['fans_cnt'] ?? 0;
        $info['projectId']  = $user->project;
        $info['userUnique'] = $user->unique;
        $info['platformId'] = $user->platform;

        // 年龄分布
        $info['fensAge'] = empty($data['age_data']) ? '' : $data['age_data'];

        // 兴趣分布
        $info['fensInterest'] = empty($data['fans_interest_data']) ? '' : $data['fans_interest_data'];

        // 性别分布
        $info['fensGender'] = empty($data['gender_data']) ? '' : $data['gender_data'];

        // 地区分布
        $info['fensArea'] = empty($data['province_data']) ? '' : $data['province_data'];

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
            $total = array_sum(array_column($info['fensAge'], 'value'));
            foreach ($info['fensAge'] as $key => $age) {
                $ratio = number_format(($age['value'] / $total)  * 100, 2, '.', '');

                $info['fensAge'][$key] = array(
                    'range'  => $age['dimension'],
                    'number' => $age['value'],
                    'ratio'  => $ratio
                );
            }

            $info['fensAge'] = array_values($info['fensAge']);
        }

        if(!empty($info['fensGender'])) {
            $total = array_sum(array_column($info['fensGender'], 'value'));
            foreach ($info['fensGender'] as $key => $gender) {
                $ratio = number_format(($gender['value'] / $total)  * 100, 2, '.', '');

                $info['fensGender'][$key] = array(
                    'type'   => $gender['dimension'] ?? FensGender::TYPE_OTHER,
                    'number' => $gender['value'],
                    'ratio'  => $ratio
                );
            }

            $info['fensGender'] = array_values($info['fensGender']);
        }

        if(!empty($info['fensInterest'])) {
            $total = array_sum(array_column($info['fensInterest'], 'value'));
            foreach ($info['fensInterest'] as $key => $interest) {
                $ratio = number_format(($interest['value'] / $total)  * 100, 2, '.', '');

                $info['fensInterest'][$key] = array(
                    'name'   => $interest['dimension'],
                    'number' => $interest['value'],
                    'ratio'  => $ratio
                );
            }

            $info['fensInterest'] = array_values($info['fensInterest']);
        }

        if(!empty($info['fensArea'])) {
            $total = array_sum(array_column($info['fensArea'], 'value'));
            foreach ($info['fensArea'] as $key => $area) {
                $ratio = number_format(($area['value'] / $total)  * 100, 2, '.', '');

                $info['fensArea'][$key] = array(
                    'name'   => $area['dimension'],
                    'number' => $area['value'],
                    'ratio'  => $ratio
                );
            }

            $info['fensArea'] = array_values($info['fensArea']);
        }

        return $info;
    }

    public function formatVideoData($data, $list)
    {
        if (!$data) {
            return false;
        }
        if (empty($data['create_time'])) {
            Log::debug('videolog', '==callbackVideo==douyin==create_time数据失败==' . json_encode($data));
            return false;
        }

        $message = '--';
        if ($data['status_value'] == 102) {
            $status = 1;
        } elseif ($data['status_value'] == 141 || $data['status_value'] == 144) {
            $status  = 2;
            $message = $v['review_struct']['status_desc'] ?? '';
        } elseif ($data['status_value'] == 140) {
            $status = 4;
        } else {
            $status = 1;
        }
        $info['unique']     = $data['aweme_id'];
        $info['unique_new'] = $data['aweme_id'] ?? '';
        $info['title']      = $data['desc'] ?? '';
        $info['link']       = 'https://www.douyin.com/video/' . $data['aweme_id'];
        if (!empty($data['video']['optimized_cover']['url_list'][0])) {
            $info['img'] = $data['video']['optimized_cover']['url_list'][0];
        } elseif (!empty($data['video']['cover']['url_list'][0])) {
            $info['img'] = $data['video']['cover']['url_list'][0];
        } else {
            $info['img'] = '';
        }
        $info['status_message'] = $message;
        $info['status']         = $status ?? 1;
        $info['play']           = $data['statistics']['play_count'] ?? 0;
        $info['recommend']      = 0;
        $info['publish_time']   = $data['create_time'];
        $info['like']           = $data['statistics']['digg_count'] ?? 0;
        $info['share']          = $data['statistics']['share_count'] ?? 0;
        $info['comment']        = $data['statistics']['comment_count'] ?? 0;
        $info['collect']        = $data['statistics']['collect_count'] ?? 0;

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
        if (empty($data['data'][0]['play']['option_list'])) {
            throw new OutPlatformException('参数不全');
        }
        $play         = $data['data'][0]['play']['option_list'];
        $comment_data = $data['data'][0]['comment']['option_list'];
        $digg_data    = $data['data'][0]['digg']['option_list'];
        $share_data   = $data['data'][0]['share']['option_list'];
        $comment      = array_column($comment_data, 'count', 'date');
        $dig          = array_column($digg_data, 'count', 'date');
        $share        = array_column($share_data, 'count', 'date');
        foreach ($play as $v) {
            $info                = [];
            $info['date']        = $v['date'];
            $info['consume_pv']  = !empty($v['count']) && $v['count'] > 0 ? $v['count'] : 0;
            $info['like_pv']     = !empty($dig[$v['date']]) && $dig[$v['date']] > 0 ? $dig[$v['date']] : 0;
            $info['fav_pv']      = !empty($fav[$v['date']]) && $fav[$v['date']] > 0 ? $fav[$v['date']] : 0;
            $info['cmt_pv']      = !empty($comment[$v['date']]) && $comment[$v['date']] > 0 ? $comment[$v['date']] : 0;
            $info['share_pv']    = !empty($share[$v['date']]) && $share[$v['date']] > 0 ? $share[$v['date']] : 0;
            $info['look_pv']     = !empty($v['look_pv']) && $v['look_pv'] > 0 ? $v['look_pv'] : 0;
            $info['time_s']      = $v['time_s'] ?? 0;
            $info['mcn_id']      = $sync['mcn_id'];
            $info['sync_id']     = $sync['sync_id'];
            $info['platform']    = $user['platform'];
            $info['user_id']     = $user['id'];
            $info['sync_status'] = 1;

            // 入库
            UserService::haddleUserLog($info);
        }
    }

    /**
     * 粉丝数据解析+入库 （旧）
     *
     * @param $user
     * @param $data
     * @param $sync
     * @return void
     */
    public function formatIncomeData($user, $data, $sync)
    {
        $fans = $data['data'][0]['fans']['option_list'] ?? [];
        if($fans) {
            $follow_cancel_data = $data['data'][0]['follow_cancel'] > 0 ? $data['data'][0]['follow_cancel'] : 0;
            $follow_add_data    = $data['data'][0]['follow_add'] > 0 ? $data['data'][0]['follow_cancel'] : 0;
            $follow_add         = array_column($follow_add_data, 'count', 'date');
            $follow_cancel      = array_column($follow_cancel_data, 'count', 'date');
            foreach ($fans as $v) {
                $info = [];
                $info['date']          = $v['date'];
//            $info['fens']          = $v['count'] ?? 0;
                empty($v['count']) ?: $info['fens'] = $v['count'];
                $info['follow_cancel'] = $follow_cancel[$v['date']] ?? 0;
                $info['follow_add']    = $follow_add[$v['date']] ?? 0;
                $info['mcn_id']        = $sync['mcn_id'];
                $info['sync_id']       = $sync['sync_id'];
                $info['platform']      = $user['platform'];
                $info['user_id']       = $user['id'];

                UserService::haddleUserLog($info);
            }
        }
    }

    public function formatVideoDetailData($data, $list)
    {
        // TODO: Implement formatVideoDetailData() method.
    }
}
