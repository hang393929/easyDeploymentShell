<?php
namespace App\Listeners;

use App\Constants\PlatformType;
use App\Http\Services\UserService;
use App\Events\OldFensInUserCallBack;
use App\Exceptions\OutPlatformException;

class OldFensInUserCallBackListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  \App\Events\OldFensInUserCallBack  $event
     * @return void
     */
    public function handle(OldFensInUserCallBack $event)
    {
        $data = $event->data;
        $user = $event->user;
        if(!$user) {
            throw new OutPlatformException('老粉丝数据异常');
        }
        $sync = UserService::getSyncUserByIds($user['user_id']);
        if(!$sync) {
            throw new OutPlatformException('老粉丝数据异常');
        }

        $this->factory($data, $user, $sync);
    }

    /**
     * 工厂处理用户数据 (ps:暂无数据支撑，保持原写法)
     *
     * @param $data
     * @return array
     */
    private function factory($data, $user, $sync) {
        switch ($user['platform']) {
            case PlatformType::DOUYIN:
                $fens = $data['data']['user_profile']['follower_count'] ?? 0;
                $info['fens']     = $fens > 0 ? $fens : 0;
//                empty($fens) ?: $info['fens'] = $fens;
                $info['user_id']  = $data['user_id'];
                $info['date']     = date('Y-m-d');
                $info['platform'] = PlatformType::DOUYIN;

                $this->saveUserLog($info);
                break;
            case PlatformType::TENGXUNSHIPIN:
                // 西瓜视频无
                break;
            case PlatformType::XIGUASHIPIN:
                if (empty($data['data'][0])){
                    break;
                }
                $fans       = array_column($data['data'][0]['data_trend_v2_6']['CreatorDataTrends'][0]['Details'], 'TotalCount', 'DataTime');
                $addfans    = array_column($data['data'][0]['data_trend_v2_4']['CreatorDataTrends'][0]['Details'], 'TotalCount', 'DataTime');
                $cancelfans = array_column($data['data'][0]['data_trend_v2_15']['CreatorDataTrends'][0]['Details'], 'TotalCount', 'DataTime');
                $cmt_pv     = array_column($data['data'][0]['data_trend_v2_3']['CreatorDataTrends'][0]['Details'], 'TotalCount', 'DataTime');
                foreach ($fans as $k => $v) {
                    $info['date']          = date('Y-m-d', $k);
                    $info['follow_add']    = $addfans[$k]?? 0;
                    $info['follow_cancel'] = $cancelfans[$k]?? 0;
                    $info['fens']          = $v ?? 0;
//                    empty($addfans[$k]) ? : $info['follow_add'] = $addfans[$k];
//                    empty($cancelfans[$k]) ?: $info['follow_cancel'] = $cancelfans[$k];
//                    empty($v) ?: $info['fens'] = $v;
                    $info['cmt_pv']        = $cmt_pv[$k] ?? 0;
                    $info['sync_id']       = $sync['sync_id'];
                    $info['mcn_id']        = $sync['mcn_id'];
                    $info['user_id']       = $user['id'];
                    $info['platform']      = $user['platform'];
                    $this->saveUserLog($info);
                }
                break;
            case PlatformType::DAYUHAO:
                $data = $data['data'][0]['daylist_uc_v2']['list'];
                foreach ($data as $v) {
                    $info['date']          = date('Y-m-d', strtotime($v['ds']));
                    $info['follow_add']    = abs($v['follow_increase']) ?? 0;
                    $info['follow_cancel'] = $v['unfollow_uv'] ?? 0;
                    $info['fens']          = $v['total_follow_uv'] ?? 0;
                    $info['sync_id']       = $sync['sync_id'];
                    $info['mcn_id']        = $sync['mcn_id'];
                    $info['user_id']       = $user['id'];
                    $info['platform']      = $user['platform'];

                    $this->saveUserLog($info);
                }
                break;
            case PlatformType::KUAISHOU:
                $fens             = $data['data'][0]['fansCnt'] ?? 0;
                $info['fens']     = $fens > 0 ? $fens : 0;
                $info['user_id']  = $data['user_id'];
                $info['date']     = date('Y-m-d');
                $info['platform'] = PlatformType::KUAISHOU;

                $this->saveUserLog($info);
                break;
            case PlatformType::WEIXINSHIPINHAO:
                // 微信视频无
                break;
            case PlatformType::BAIJIAHAO:
                $fensData = $data['data'][0]['getFansBasicInfo'];
                if (!empty($fensData['data']['list']) && is_array($fensData['data']['list'])){
                    foreach ($fensData['data']['list'] as $item) {
                        if (isset($item['sum_fans_count'])){
                            if (!is_numeric($item['sum_fans_count']) || $item['sum_fans_count']<0 ){
                                continue;
                            }
                        }
                        $info['date']          = date("Y-m-d", strtotime($item['day']));
                        $info['fens']          = $item['sum_fans_count'] ?? 0;
                        $info['follow_add']    = $item['net_fans_count'] ?? 0;
                        $info['follow_cancel'] = $item['rm_fans_count'] ?? 0;
                        $info['mcn_id']        = $sync['mcn_id'] ?? 0;
                        $info['sync_id']       = $sync['sync_id'] ?? 0;
                        $info['platform']      = $user['platform'];
                        $info['user_id']       = $user['id'];

                        $this->saveUserLog($info);
                    }
                }
                break;
            case PlatformType::XIAOHONGSHU:
                $fensData         = $data['data'][0]['overall_new'];
                if (empty($fensData['seven'])){
                    break;
                }
                $rise_fans_count  = array_column($fensData['seven']['rise_fans_list'], 'count', 'date');
                $leave_fans_count = array_column($fensData['seven']['leave_fans_list'], 'count', 'date');
                if (!empty($fensData['seven']) && is_array($fensData['seven'])) {
                    foreach ($fensData['seven']['fans_list'] as $item) {
                        if (empty($item['date'])) {
                            continue;
                        }
                        $info['date']          = getTimeToDate($item['date']);
                        $info['fens']          = $item['count'] ?? 0;
                        $info['follow_add']    = $rise_fans_count[$item['date']] ?? 0;
                        $info['follow_cancel'] = $leave_fans_count[$item['date']] ?? 0;
                        $info['mcn_id']        = $sync['mcn_id'] ?? 0;
                        $info['sync_id']       = $sync['sync_id'] ?? 0;
                        $info['platform']      = $user['platform'];
                        $info['user_id']       = $user['id'];
                        $this->saveUserLog($info);
                    }
                }
                break;
            case PlatformType::JINGDONG:
                // 京东无
                break;
            case PlatformType::PINDUODUO:
                // 拼多多无
                break;
            case PlatformType::TAOBAO:
                // 淘宝无
                break;
            case PlatformType::BILIBILI:
                // bilibi无
                break;
            default:
                break;
        }
    }

    private function saveUserLog(array $info) {
        UserService::haddleUserLog($info);
    }
}
