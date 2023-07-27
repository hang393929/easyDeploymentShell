<?php
namespace App\Http\Factory\OutPlatform\Module;

use App\Http\Helper\Log;
use App\Models\FensGender;
use App\Http\Services\UserService;
use App\Events\OldFensInUserCallBack;
use App\Exceptions\OutPlatformException;
use App\Http\Factory\OutPlatform\OutPlatformBase;

class Dayuhao extends OutPlatformBase
{
    private static $genderMap = [
        'm' => FensGender::TYPE_MALE,
        'f' => FensGender::TYPE_FEMALE
    ];

    private static $levelName = [
        2 => [
            1 => '金V',
            2 => '银V',
            3 => '铜V',
            10 => '蓝V',
            11 => '蓝V',
            10001 => '普通',
            10002 => '试运营'
        ],
        3 => [
            1 => 'A',
            2 => 'AA',
            3 => 'AAA'
        ],
        10 => [
            100 => '新手创作者',
            200 => '百粉创作者',
            300 => '千粉创作者',
            400 => '万粉创作者',
            500 => '五万粉创作者',
            600 => '十万粉创作者'
        ],
        1 => [
            1 => 'S',
            2 => 'A',
            3 => 'B'
        ]
    ];

    public function formatUserData($user, $data)
    {
        $haddleData         = $data['data'][0];
        $info['unique_3']   = $haddleData['globalConfig']['wmid_new'];
        $info['nick_name']  = $haddleData['globalConfig']['subjectname_new'];
        $info['user_name']  = $haddleData['globalConfig']['subjectname'];
        $info['level']      = is_numeric($haddleData['globalConfig']['vip_level']) ? $haddleData['globalConfig']['vip_level'] : 0;
        $info['level_name'] = self::$levelName[2][$haddleData['globalConfig']['vip_level']] ?? '';
        if (isset($haddleData['globalConfig']['role']['original_video'])) {
            if ($haddleData['globalConfig']['role']['original_video'] == 1) {
                $info['original'] = 1;
            } else {
                $info['original'] = 2;
            }
        } else {
            $info['original'] = 2;
        }
        $info['field']   = $haddleData['globalConfig']['field'] ?? '';

        $info['quality'] = $haddleData['level']['nextLevelConditionInfo']['condition']['quality_index_day'] ?? '';
        $info['credit']  = $haddleData['level']['nextLevelConditionInfo']['condition']['credit_score'] ?? '';
        $fens            = $haddleData['level']['nextLevelConditionInfo']['condition']['follow_uv_all'] ?? 0;
        $info['fens']    = $fens > 0 ? $fens : 0;

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
        // todo 粉丝数未获取到

        // 粉丝年龄分布
        $info['fensAge']    = empty($data['age_portrait']) ? '' : $data['age_portrait'];
        // 粉丝性别分布
        $info['fensGender'] = empty($data['gender_portrait']) ? '' : $data['gender_portrait'];
        // 粉丝地区分布
        $info['fensArea']   = empty($data['province_portrait']) ? '' : $data['province_portrait'];

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
                    'range'  => $key,
                    'ratio'  => number_format($age * 100, 2, '.', '')
                );
            }

            $info['fensAge'] = array_values($info['fensAge']);
        }

        if(!empty($info['fensGender'])) {
            foreach ($info['fensGender'] as $key => $gender) {
                $info['fensGender'][$key] = array(
                    'type'   => self::$genderMap[$key] ?? FensGender::TYPE_OTHER,
                    'ratio'  => number_format($gender * 100, 2, '.', '')
                );
            }

            $info['fensGender'] = array_values($info['fensGender']);
        }

        if(!empty($info['fensArea'])) {
            foreach ($info['fensArea'] as $key => $area) {
                $info['fensArea'][$key] = array(
                    'name'   => $area['name'],
                    'ratio'  => number_format($area['value'] * 100, 2, '.', '')
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
        // 小视频
        if ($list['data'][0]['getArticleList']['_data_type'] ?? 'spiderBaseVideoList' == 'spiderShortVideoList') {
            return $this->formatShortVideoData($v);
        }
        // 正常视频
        return $this->formatNormalVideoData($v);
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
        foreach ($data['data'][0] as $k => $v) {
            $info['date']        = date('Y-m-d', strtotime($v['date']));
            $info['consume_pv']  = $v['consume_pv'] ?? 0;
            $info['cmt_pv']      = $v['cmt_pv'] ?? 0;
            $info['fav_pv']      = $v['fav_pv'] ?? 0;
            $info['share_pv']    = $v['share_pv'] ?? 0;
            $info['like_pv']     = $v['like_pv'] ?? 0;
            $info['mcn_id']      = $sync['mcn_id'];
            $info['sync_id']     = $sync['sync_id'];
            $info['platform']    = $user['platform'];
            $info['user_id']     = $user['id'];
            $info['sync_status'] = 1;

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
        if (empty($data['data'][0])){
            throw new OutPlatformException('数据不存在');
        }

        $data = $data['data'][0];
        foreach ($data as $v) {
            $info = [];
            $info['date']     = date('Y-m-d', strtotime($v['report_date']));
            $info['user_id']  = $user['id'];
            $info['income']   = $infov['feed_account_income'] ?? 0;
            $info['mcn_id']   = $sync['mcn_id'];
            $info['sync_id']  = $sync['sync_id'];
            $info['platform'] = $user['platform'];

            UserService::haddleUserLog($info);
        }
    }

    /**
     * 大鱼号正常视频解析
     * @param $v
     * @return array|false
     */
    private function formatNormalVideoData($v){
        if ($v['status'] == 1) {
            $status  = 1;
            $message = '--';
        } elseif ($v['status'] == 4) {
            $status  = 2;
            $message = $v['_app_extra']['audit_remark'] ?? '';
        } else {
            $status  = 1;//3;
        }

        $info['unique']         = $v['_id'];
        $info['unique_new']     = $v['origin_id'] ?? '';
        $info['title']          = $v['title'] ?? '';
        $info['link']           = $v['ucUrl'] ?? '';
        $info['img']            = $v['cover_url'] ?? '';
        $info['status_message'] = $message ?? '';
        $info['status']         = $status;
        $info['play']           = $list[$v['_id']]['data'][0]['consume_pv'] ?? 0;
        $info['share']          = $list[$v['_id']]['data'][0]['share_pv'] ?? 0;
        $info['recommend']      = $list[$v['_id']]['data'][0]['show_pv'] ?? 0;
        $info['like']           = $list[$v['_id']]['data'][0]['like_pv'] ?? 0;
        $info['comment']        = $list[$v['_id']]['data'][0]['cmt_pv'] ?? 0;
        $info['collect']        = $list[$v['_id']]['data'][0]['fav_pv'] ?? 0;
        $info['tag']            = '--';
        $info['publish_time']   = strtotime($v['publish_at']);

        return $info;
    }

    /**
     * 大鱼号小视频解析
     * @param $v
     * @return array|false
     */
    private function formatShortVideoData($v){
        if ($v['status'] == 1) {
            $status  = 1;
            $message = '--';
        } elseif ($v['status'] == 4) {
            $status  = 2;
            $message = $v['_app_extra']['audit_remark'] ?? '';
        } else {
            $status  = 1;//3;
        }

        $info['unique']         = $v['content_id'];
        $info['unique_new']     = $v['content_id'] ?? '';
        $info['title']          = $v['title'] ?? '';
        $info['link']           = $v['counters']['share_url'] ?? '';
        $info['img']            = $v['cover_url'] ?? '';
        $info['status_message'] = $message ?? '';
        $info['status']         = $status;
        $info['play']           = $v['counters']['play_cnt'] ?? 0;
        $info['share']          = $v['counters']['share_cnt'] ?? 0;
        $info['recommend']      = 0;
        $info['like']           = $v['counters']['like_cnt'] ?? 0;
        $info['comment']        = $v['counters']['cmt_cnt'] ?? 0;
        $info['collect']        = $v['counters']['fav_cnt'] ?? 0;
        $info['tag']            = '--';
        $info['publish_time']   = strtotime($v['published_at']);

        return $info;
    }

    public function formatVideoDetailData($data, $list)
    {
        // TODO: Implement formatVideoDetailData() method.
    }
}
