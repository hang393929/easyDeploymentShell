<?php

namespace App\Http\Factory\OutPlatform\Module;

use App\Http\Helper\Log;
use App\Models\FensGender;
use App\Http\Services\UserService;
use App\Exceptions\OutPlatformException;
use App\Http\Factory\OutPlatform\OutPlatformBase;

class Tengxunshipin extends OutPlatformBase
{
    private static $levelName = [
        2  => [
            1     => '金V',
            2     => '银V',
            3     => '铜V',
            10    => '蓝V',
            11    => '蓝V',
            10001 => '普通',
            10002 => '试运营'
        ],
        3  => [
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
        1  => [
            1 => 'S',
            2 => 'A',
            3 => 'B'
        ]
    ];

    public function formatUserData($user, $data)
    {
        $data               = $data['data'][0];
        $info['nick_name']  = $data['GetSummary']['account_info']['nick_new'] ?? "";
        $info['unique_3']   = $data['GetVcuid']['items']['0']['vcuid_new'] ?? "";
        $info['user_name']  = $data['GetSummary']['account_info']['nick'];
        $info['level']      = is_numeric($data['GetGrowthLevel']['current_level_id']) ? $data['GetGrowthLevel']['current_level_id'] : 0;
        $info['level_name'] = self::$levelName[10][$data['GetGrowthLevel']['current_level_id']] ?? '';
        $fens               = $data['GetGrowthLevel']['fans_count'] ?? 0;
        $info['fens']       = $fens > 0 ? $fens : 0;
        $original           = 2;
        $original_data      = $data['GetCreatorRights']['right_groups'];
        if($original_data) {
            foreach ($original_data as $v) {
                if ($v['group_name'] == '创作权益') {
                    foreach ($v['rights'] as $o) {
                        if ($o['right_name'] == '视频原创声明' && $o['right_status'] == 1) {
                            $original = 1;
                        }
                    }
                }
            }
        }

        $info['original'] = $original;
        $info['credit']   = $data['GetCredit']['value'] ?? '';

        return $info;
    }

    /**
     * 粉丝数据解析
     *
     * @param $user
     * @param $data
     * @return false
     */
    public function formatFanDrawData($user, $data)
    {
        $data               = $data['data'][0]['data'];
        $info['fans_cnt']   = $data['fans_count'] ?? 0; // 粉丝数
        $info['projectId']  = $user->project;
        $info['userUnique'] = $user->unique;
        $info['platformId'] = $user->platform;

        // 粉丝性别 and 粉丝年龄
        $info['fensAgeSource'] = $info['fensGenderSource'] = empty($data['lines'][0]) ? '' : $data['lines'][0];
        if (!empty($data['pies'])) {
            foreach ($data['pies'] as $value) {
                if (!empty($value['name'])) {
                    if ($value['name'] == 'u_bdb_dis_province') {
                        // 粉丝地区分布
                        $info['fensAreaSource'] = empty($value['values']) ? '' : $value['values'];
                    }
                    if ($value['name'] == 'u_bdb_dis_grade') {
                        // 粉丝学历分布
                        $info['fensEducationSource'] = empty($value['values']) ? '' : $value['values'];
                    }
                    if ($value['name'] == 'u_bdb_dis_tag') {
                        // 粉丝兴趣分布
                        $info['fensInterestSource'] = empty($value['values']) ? '' : $value['values'];
                    }
                }
            }
        }

        return $this->haddleFanDrawData($info);
    }

    /**
     * 粉丝数据格式化
     *
     * @param $info
     * @return mixed
     */
    private function haddleFanDrawData($info)
    {
        if (!empty($info['fensGenderSource']['labels']) && !empty($info['fensGenderSource']['values'])) {
            $age = $gender = [];
            // 兼容数据全为男或女的情况
            if(!empty($info['fensGenderSource']['values'][0]['value'])) {
                $totalCount = array_sum($info['fensGenderSource']['values'][0]['value']);
                $totalCount += empty($info['fensGenderSource']['values'][1]['value']) ? 0 : array_sum($info['fensGenderSource']['values'][1]['value']);
            }
            foreach ($info['fensGenderSource']['labels'] as $key => $label) {
                $ageData = [
                    'range'  => $label,
                    'number' => $info['fensGenderSource']['values'][0]['value'][$key] ?? 0 + $info['fensGenderSource']['values'][1]['value'][$key] ?? 0,
                    'ratio'  => $totalCount == 0 ? '0.00' : number_format(($info['fensGenderSource']['values'][0]['value'][$key] ?? 0 + $info['fensGenderSource']['values'][1]['value'][$key]) ?? 0 / $totalCount * 100, 2, '.', '')
                ];

                $age[] = $ageData;
            }

            // 粉丝年龄
            $info['fensAge'] = array_values($age);

            foreach ($info['fensGenderSource']['values'] as $value) {
                $genderData = [
                    'type'   => $value['name'] == '女' ? FensGender::TYPE_FEMALE : FensGender::TYPE_MALE,
                    'number' => array_sum($value['value']),
                    'ratio'  => $totalCount == 0 ? '0.00' : number_format(array_sum($value['value']) / $totalCount * 100, 2, '.', '')
                ];

                $gender[] = $genderData;
            }

            // 粉丝性别
            $info['fensGender'] = array_values($gender);
        }

        if (!empty($info['fensAreaSource'])) {
            foreach ($info['fensAreaSource'] as $key => $area) {
                $info['fensArea'][$key] = [
                    'name'   => $area['name'],
                    'number' => $area['value'],
                    'ratio'  => number_format($area['ratio'] * 100, 2, '.', '')
                ];
            }
        }

        if (!empty($info['fensEducationSource'])) {
            foreach ($info['fensEducationSource'] as $key => $education) {
                $info['fensEducation'][$key] = [
                    'name'   => $education['name'],
                    'number' => $education['value'],
                    'ratio'  => number_format($education['ratio'] * 100, 2, '.', '')
                ];
            }
        }

        if (!empty($info['fensInterestSource'])) {
            foreach ($info['fensInterestSource'] as $key => $interest) {
                $info['fensInterest'][$key] = [
                    'name'   => $interest['name'],
                    'number' => $interest['value'],
                    'ratio'  => number_format($interest['ratio'] * 100, 2, '.', '')
                ];
            }
        }

        return $info;
    }

    public function formatVideoData($v, $list)
    {
        if (!$v) {
            return false;
        }
        if (empty($v['uploadTime'])) {
            Log::debug('videolog', '==txsp==puploadTime数据失败==' . json_encode($v));
            return false;
        }

        $status_message = '';
        if ($v['status'] == 2) {
            $status = 1;
        } elseif ($v['status'] == 6) {
            $status         = 2;
            $status_message = $v['status_show'][1]['content'] ?? '';
        } else {
            $status = 1;//3;
        }

        $info['unique']         = $v['vid'];
        $info['unique_new']     = $v['vid'] ?? '';
        $info['title']          = $v['title'] ?? '';
        $info['link']           = 'https://v.qq.com/x/cover/mzc002001f91tpl/' . $v['vid'] . '.html';
        $info['img']            = $v['imageUrl'] ?? '';
        $info['status_message'] = $status_message ?? '';
        $info['status']         = $status;
        $info['play']           = $v['allnumc'] ?? 0;
        $info['share']          = 0;
        $info['recommend']      = 0;
        $info['like']           = $v['like'] ?? 0;
        $info['comment']        = $v['comment'] ?? 0;
        $info['tag']            = '--';
        $info['publish_time']   = strtotime($v['uploadTime']);
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
        if (empty($data['data'][0]['GetTrendAnalysis']['trends'][0])) {
            throw new OutPlatformException('参数不全');
        }
        foreach ($data['data'][0]['GetTrendAnalysis']['trends'] as $v) {
            if ($v['name'] == '播放数据') {
                $list_4_1 = $v['indicators'][0]['dates'];
                $list_4_2 = $v['indicators'][0]['data'] ?? 0;
                $list_4_5 = $v['indicators'][2]['data'] ?? 0;
            }
            if ($v['name'] == '互动数据') {
                $list_4_3 = $v['indicators'][0]['data'] ?? 0;
                $list_4_4 = $v['indicators'][1]['data'] ?? 0;
            }
            if ($v['name'] == '粉丝数据') {
                $list_4_6 = $v['indicators'][0]['data'] ?? 0;;
                $list_4_7 = $v['indicators'][1]['data'] ?? 0;;
            }
        }
        foreach ($list_4_1 as $k => $v) {
            $info['date']        = date('Y-m-d', strtotime($v));
            $info['consume_pv']  = $list_4_2[$k] ?? 0;
            $info['like_pv']     = $list_4_3[$k] ?? 0;
            $info['cmt_pv']      = $list_4_4[$k] ?? 0;
            $info['time_s']      = $list_4_5[$k] ?? 0;
            $info['fens']        = $list_4_6[$k] ?? 0;
            $info['follow_add']  = $list_4_7[$k] ?? 0;
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
        $data     = $data['data'][0];
        $list_5_1 = $data['lineCharts']['total']['abscissas'];
        $list_5_2 = $data['lineCharts']['total']['ordinate'];
        foreach ($list_5_1 as $k => $v) {
            $info             = [];
            $info['date']     = date('Y-m-d', strtotime($v));
            $info['user_id']  = $user['id'];
            $info['income']   = $list_5_2[$k];
            $info['mcn_id']   = $sync['mcn_id'];
            $info['sync_id']  = $sync['sync_id'];
            $info['platform'] = $user['platform'];

            UserService::haddleUserLog($info);
        }
    }

    public function formatVideoDetailData($data, $list)
    {
        // TODO: Implement formatVideoDetailData() method.
    }
}
