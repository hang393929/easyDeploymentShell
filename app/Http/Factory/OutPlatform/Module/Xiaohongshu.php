<?php
namespace App\Http\Factory\OutPlatform\Module;

use App\Http\Helper\Log;
use App\Models\FensGender;
use App\Http\Services\UserService;
use App\Events\OldFensInUserCallBack;
use App\Exceptions\OutPlatformException;
use App\Http\Factory\OutPlatform\OutPlatformBase;

class Xiaohongshu extends OutPlatformBase
{
    private static $genderMap = [
        '男性' => FensGender::TYPE_MALE,
        '女性' => FensGender::TYPE_FEMALE
    ];

    public function formatUserData($user, $data)
    {
        $info['nick_name'] = $data['data'][0]['personal_info']['name_new'] ?? "";
        $info['unique_3']  = $data['data'][0]['personal_info']['red_num_new'] ?? "";
        $info['fens']      = $data['data'][0]['personal_info']['fans_count'] ?? 0;
        $info['follow']    = $data['data'][0]['personal_info']['follow_count'] ?? 0;
        $info['like']      = $data['data'][0]['personal_info']['faved_count'] ?? 0;

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
        $data = $data['data'][0];
        $info['projectId']  = $user->project;
        $info['userUnique'] = $user->unique;
        $info['platformId'] = $user->platform;
        $info['fansCnt']    = empty($data['overall_new']['seven']['fans_count']) ? 0 : $data['overall_new']['seven']['fans_count'];

        // 粉丝年龄分布
        $info['fensAge']      = empty($data['fans_portrait_new']['age']) ? '' : $data['fans_portrait_new']['age'];
        // 粉丝性别分布
        $info['fensGender']   = empty($data['fans_portrait_new']['gender']) ? '' : $data['fans_portrait_new']['gender'];
        // 粉丝兴趣分布
        $info['fensInterest'] = empty($data['fans_portrait_new']['interest']) ? '' : $data['fans_portrait_new']['interest'];
        // 粉丝地区分布
        $info['fensCity']     = empty($data['fans_portrait_new']['city']) ? '' : $data['fans_portrait_new']['city'];

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
                $ratio = number_format(($age['value'] / $total) * 100, 2, '.', '');

                $info['fensAge'][$key] = array(
                    'range'  => $age['title'],
                    'number' => $age['value'],
                    'ratio'  => $ratio
                );
            }

            $info['fensAge'] = array_values($info['fensAge']);
        }

        if(!empty($info['fensGender'])) {
            $total = array_sum(array_column($info['fensGender'], 'value'));
            foreach ($info['fensGender'] as $key => $gender) {
                $ratio = number_format(($gender['value'] / $total) * 100, 2, '.', '');

                $info['fensGender'][$key] = array(
                    'type'   => self::$genderMap[$gender['title']] ?? FensGender::TYPE_OTHER,
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
                    'name'   => $interest['title'],
                    'number' => $interest['value'],
                    'ratio'  => $ratio
                );
            }

            $info['fensInterest'] = array_values($info['fensInterest']);
        }

        if(!empty($info['fensCity'])) {
            $total = array_sum(array_column($info['fensCity'], 'value'));
            foreach ($info['fensCity'] as $key => $area) {
                $ratio = number_format(($area['value'] / $total)  * 100, 2, '.', '');

                $info['fensCity'][$key] = array(
                    'name'   => $area['title'],
                    'number' => $area['value'],
                    'ratio'  => $ratio
                );
            }

            $info['fensCity'] = array_values($info['fensCity']);
        }

        return $info;
    }

    public function formatVideoData($v, $list)
    {
        if (!$v || !isset($v['video_info'])) {
            return false;
        }

        if (empty($v['time'])) {
            Log::debug('videolog', '==xhs==time数据失败==' . json_encode($v));
            return false;
        }

        $message = '--';
        if ($v['tab_status'] == 1) {
            $status = 1;
        } else if ($v['tab_status'] == 2) {
            $status = 3;
        } else if ($v['tab_status'] == 3) {
            $status = 2;
        } else {
            $status = 1;
        }
        $info['unique']         = $v['id'];
        $info['unique_new']     = $v['id'] ?? '';
        $info['title']          = $v['display_title'] ?? '';
        $info['link']           = 'https://www.xiaohongshu.com/discovery/item/' . $v['id'];
        $info['img']            = $v['images_list'][0]['url'] ?? '';
        $info['status_message'] = $message;
        $info['status']         = $status;
        $info['play']           = $v['view_count'] ?? 0;
        $info['recommend']      = 0;
        $info['publish_time']   = strtotime($v['time']);
        $info['like']           = $v['likes'] ?? 0;
        $info['comment']        = $v['comments_count'] ?? 0;
        $info['collect']        = $v['collected_count'] ?? 0;
        $info['share']          = $v['shared_count'] ?? 0;

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
        if(empty($data['data'][0]['seven'])){
            throw new OutPlatformException('参数不全');
        }

        $data            = $data['data'][0]['seven'];
        $share_count     = array_column($data['share_list'], 'count', 'date');
        $like_count      = array_column($data['like_list'], 'count', 'date');
        $rise_fans_count = array_column($data['rise_fans_list'], 'count', 'date');
        $comment_count   = array_column($data['comment_list'], 'count', 'date');
        $collect_count   = array_column($data['collect_list'], 'count', 'date');

        if (!empty($data['view_list']) && is_array($data['view_list'])) {
            foreach ($data['view_list'] as $item) {
                $info['date']        = date('Y-m-d', strtotime(getMsecToMescdate($item['date'])));
                $info['consume_pv']  = $item['count'] ?? 0;
                $info['share_pv']    = $share_count[$item['date']] ?? 0;
                $info['like_pv']     = $like_count[$item['date']] ?? 0;
//                $info['follow_add']  = $rise_fans_count[$item['date']] ?? 0;
//                empty($rise_fans_count[$item['date']]) ?: $info['follow_add'] = $rise_fans_count[$item['date']];
                $info['cmt_pv']      = $comment_count[$item['date']] ?? 0;
                $info['fav_pv']      = $collect_count[$item['date']] ?? 0;
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

    /**
     * 视频详情
     * @param $data
     * @param $list
     * @return void
     */
    public function formatVideoDetailData($data, $list)
    {
        if (empty($data['id'])) {
            throw new OutPlatformException('参数不全');
        }
        $res['info']['unique']      = $data['id'];
        $res['info']['title']       = $data['title'];
        $res['info']['play']        = $data['read'];
        $res['info']['like']        = $data['like'];
        $res['info']['comment']     = $data['comment'];
        $res['info']['share']       = $data['share'];
        $res['info']['recommend']   = 0;
        $res['info']['collect']     = $data['fav'];

        $res['detail']=[];
        if (!empty($data['read_lst'])){
            $dayInfo = $data['read_lst'];
//            $follow_list   = array_column($data['follow_list'], 'total_num', 'date');
            $like_count    = array_column($data['like_list'], 'total_num', 'date');
            $fav_list      = array_column($data['fav_list'], 'total_num', 'date');
            $comment_count = array_column($data['comment_list'], 'total_num', 'date');
            $share_list    = array_column($data['share_list'], 'total_num', 'date');
            $danmaku_list  = array_column($data['danmaku_count_list'], 'total_num', 'date');
            foreach ($dayInfo as $key=>$item) {
                $res['detail'][$key]['unique']         = $data['id'];
                $res['detail'][$key]['unique_new']     = $data['id'];
                $res['detail'][$key]['date']           = $item['date'];
                $res['detail'][$key]['recommend_num']  = $item['recommend_count'] ?? 0;
                $res['detail'][$key]['play_num']       = $item['total_num'] ?? 0;
                $res['detail'][$key]['like_num']       = $like_count[$item['date']] ?? 0;
                $res['detail'][$key]['collect_num']    = $fav_list[$item['date']] ?? 0;
                $res['detail'][$key]['comment_num']    = $comment_count[$item['date']] ?? 0;
                $res['detail'][$key]['share_num']      = $share_list[$item['date']] ?? 0;
                $res['detail'][$key]['danmu_num']      = $danmaku_list[$item['date']] ?? 0;
            }
        }
        return $res;
    }
}
