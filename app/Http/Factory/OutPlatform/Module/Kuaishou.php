<?php
namespace App\Http\Factory\OutPlatform\Module;

use App\Http\Helper\Log;
use App\Http\Services\UserService;
use App\Events\OldFensInUserCallBack;
use App\Exceptions\OutPlatformException;
use App\Http\Factory\OutPlatform\OutPlatformBase;
class Kuaishou extends OutPlatformBase
{

    public function formatUserData($user, $data)
    {
        $info['user_name'] = $data['data'][0]['userName'];
        $fens              = $data['data'][0]['fansCnt'] ?? 0;
        $info['fens']      = $fens > 0 ? $fens : 0;
        $info['unique_3']  = $data['data'][0]['userId_new'] ?? "";
        $info['nick_name'] = $data['data'][0]['userName_new'] ?? "";

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
        $data = $data['data'][0]['data'];
        $info['projectId']  = $user->project;
        $info['userUnique'] = $user->unique;
        $info['platformId'] = $user->platform;
        $info['fansCnt']    = $data['sex'][0]['sum'] ?? 0; // 粉丝数

        // 粉丝年龄分布
        $info['fensAge']      = empty($data['age']) ? '' : $data['age'];
        // 粉丝性别分布
        $info['fensGender']   = empty($data['sex']) ? '' : $data['sex'];
        // 粉丝兴趣分布
        $info['fensInterest'] = empty($data['like']) ? '' : $data['like'];
        // 粉丝地区分布
        $info['fensArea']     = empty($data['province']) ? '' : $data['province'];
        // 粉丝地区分布
        $info['fensCity']     = empty($data['city']) ? '' : $data['city'];

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
                    'range'  => $age['xcode'],
                    'number' => $age['value'],
                    'ratio'  => number_format(($age['value'] / $age['sum']) * 100, 2, '.', '')
                );
            }

            $info['fensAge'] = array_values($info['fensAge']);
        }

        if(!empty($info['fensGender'])) {
            foreach ($info['fensGender'] as $key => $gender) {
                $info['fensGender'][$key] = array(
                    'type'   => $gender['index'],
                    'number' => $gender['value'],
                    'ratio'  => number_format(($gender['value'] / $gender['sum']) * 100, 2, '.', '')
                );
            }

            $info['fensGender'] = array_values($info['fensGender']);
        }

        if(!empty($info['fensInterest'])) {
            foreach ($info['fensInterest'] as $key => $interest) {
                $info['fensInterest'][$key] = array(
                    'name'   => $interest['xcode'],
                    'number' => $interest['value'],
                    'ratio'  => number_format(($interest['value'] / $interest['sum']) * 100, 2, '.', '')
                );
            }

            $info['fensInterest'] = array_values($info['fensInterest']);
        }

        if(!empty($info['fensArea'])) {
            foreach ($info['fensArea'] as $key => $area) {
                $info['fensArea'][$key] = array(
                    'name'   => $area['xcode'],
                    'number' => $area['value'],
                    'ratio'  => number_format(($area['value'] / $area['sum']) * 100, 2, '.', '')
                );
            }

            $info['fensArea'] = array_values($info['fensArea']);
        }

        if(!empty($info['fensCity'])) {
            foreach ($info['fensCity'] as $key => $city) {
                $info['fensCity'][$key] = array(
                    'name'   => $city['xcode'],
                    'number' => $city['value'],
                    'ratio'  => number_format(($city['value'] / $city['sum']) * 100, 2, '.', '')
                );
            }

            $info['fensCity'] = array_values($info['fensCity']);
        }

        return $info;
    }

    public function formatVideoData($data, $list)
    {
        if (!$data) {
            return false;
        }
        $message = '--';
        if ($data['judgementStatus'] == 1) {
            $status = 1;
        } elseif ($data['judgementStatus'] == 0) {
            $status  = 2;
            $message = $data['judgementTitle'] ?? '';
        } else {
            $status = 1;
        }
        if (empty($data['workId'])) {
            Log::debug('videolog', '==kuaishou==workId数据失败==' . json_encode($data));
            return false;
        }
        if (empty($data['uploadTime'])) {
            Log::debug('videolog', '==kuaishou==workId数据发布时间失败==' . json_encode($data));
            return false;
        }
        $info['unique']         = $data['workId'];
        $info['unique_new']     = $data['workId'] ?? '';
        $info['title']          = $data['title'] ?? '';
        $info['link']           = 'https://www.kuaishou.com/short-video/' . $data['workId'] . '?utm_source=video&utm_medium=video&utm_campaign=video';
        $info['img']            = $data['publishCoverUrl'] ?? '';
        $info['status_message'] = $message ?? '';
        $info['status']         = $status;
        $info['play']           = $data['playCount'] ?? 0;
        $info['share']          = 0;
        $info['recommend']      = 0;
        $info['like']           = $data['likeCount'] ?? 0;
        $info['comment']        = $data['commentCount'] ?? 0;
        $info['tag']            = '--';
        $info['publish_time']   = strtotime(getMsecToMescdate($data['uploadTime']));
        $info['collect']        = 0;

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
        if (!isset($data['data']['basicData'][0])) {
            throw new OutPlatformException('参数不全');
        }
        foreach ($data['data']['basicData'][0]['trendData'] as $k => $v) {
            $info['date']        = date('Y-m-d', strtotime($v['date']));
            $info['consume_pv']  = $v['count'] ?? 0;
            $info['mcn_id']      = $sync['mcn_id'];
            $info['sync_id']     = $sync['sync_id'];
            $info['platform']    = $user['platform'];
            $info['user_id']     = $user['id'];
            $info['time_s']      = $data['data']['basicData'][1]['trendData'][$k]['count'] ?? '';
            $followAdd           = $data['data']['basicData'][2]['trendData'][$k]['count'];
            $info['follow_add']  = !empty($followAdd) && $followAdd > 0 ? $followAdd : 0;
            $info['cmt_pv']      = $data['data']['basicData'][3]['trendData'][$k]['count'] ?? 0;
            $info['like_pv']     = $data['data']['basicData'][4]['trendData'][$k]['count'] ?? 0;
            $info['share_pv']    = $data['data']['basicData'][5]['trendData'][$k]['count'] ?? 0;
            $info['video']       = $data['data']['basicData'][6]['trendData'][$k]['count'] ?? 0;
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
