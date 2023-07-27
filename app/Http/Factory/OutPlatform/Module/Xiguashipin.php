<?php
namespace App\Http\Factory\OutPlatform\Module;

use App\Http\Helper\Log;
use App\Models\FensGender;
use App\Http\Services\UserService;
use App\Events\OldFensInUserCallBack;
use App\Exceptions\OutPlatformException;
use App\Http\Factory\OutPlatform\OutPlatformBase;

class Xiguashipin extends OutPlatformBase
{
    private static $genderMap = [
        '男' => FensGender::TYPE_MALE,
        '女' => FensGender::TYPE_FEMALE
    ];

    public function formatUserData($user, $data)
    {
        $haddleData = $data['data'][0];

        $info['unique_3']   = $haddleData['userInfo']['_user_id_new'] ?? "";
        $info['nick_name']  = $haddleData['userInfo']['_user_name_new'] ?? "";
        $info['user_name']  = $haddleData['userInfo']['_user_name'] ?? '';
        $totalPlay = $haddleData['userInfo']['total_play'] ?? 0;
        $info['total_play'] = is_numeric($totalPlay) ? $totalPlay : $this->strToNum($totalPlay);
        //$info['total_play'] = $this->strToNum($haddleData['userInfo']['total_play'] ?? '');
        $info['credit']     = $haddleData['author_benefits']['CreditData']['CreditScore'] ?? '';
        $fens               = $haddleData['overview_data_v3']['overviewList'][2]['TotalCount'] ?? 0;
        $info['fens']       = $fens > 0 ? $fens : 0;

        event(new OldFensInUserCallBack($user, $data));

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
        $data               = $data['data'][0];
        $info['projectId']  = $user->project;
        $info['userUnique'] = $user->unique;
        $info['platformId'] = $user->platform;
        $info['fansCnt']    = $data['overview_data_v3']['overviewList'][0]['TotalCount'] ?? 0; // 粉丝数

        if(!empty($data['fans_analysis_v2']['Infos'])) {
            $data = $data['fans_analysis_v2']['Infos'];

            foreach ($data as $value) {
                //年龄分布
                if($value['Type'] == 3) {
                    $info['fensAge'] = $value['Infos'] ?? '';
                }
                //性别分布
                if($value['Type'] == 1) {
                    $info['fensGender'] = $value['Infos'] ?? '';
                }
                //地域分布
                if($value['Type'] == 2) {
                    $info['fensArea'] = $value['Infos'] ?? '';
                }
            }

            $this->haddleFanDrawData($info);
        }

        return $info;
    }

    /**
     * 粉丝数据格式化
     *
     * @param $info
     * @return array
     */
    private function haddleFanDrawData(&$info)
    {
        if(!empty($info['fensAge'])) {
            $total = array_sum(array_column($info['fensAge'], 'Value'));
            foreach ($info['fensAge'] as $key => $age) {
                $ratio = number_format(($age['Value'] / $total)  * 100, 2, '.', '');

                $info['fensAge'][$key] = array(
                    'range'  => $age['Key'],
                    'number' => $age['Value'],
                    'ratio'  => $ratio
                );
            }

            $info['fensAge'] = array_values($info['fensAge']);
        }

        if(!empty($info['fensGender'])) {
            $total = array_sum(array_column($info['fensGender'], 'Value'));
            foreach ($info['fensGender'] as $key => $gender) {
                $ratio = number_format(($gender['Value'] / $total)  * 100, 2, '.', '');

                $info['fensGender'][$key] = array(
                    'type'   => self::$genderMap[$gender['Key']],
                    'number' => $gender['Value'],
                    'ratio'  => $ratio
                );
            }

            $info['fensGender'] = array_values($info['fensGender']);
        }

        if(!empty($info['fensArea'])) {
            $total = array_sum(array_column($info['fensArea'], 'Value'));
            foreach ($info['fensArea'] as $key => $area) {
                $ratio = number_format(($area['Value'] / $total)  * 100, 2, '.', '');

                $info['fensArea'][$key] = array(
                    'name'   => $area['Key'],
                    'number' => $area['Value'],
                    'ratio'  => $ratio
                );
            }

            $info['fensArea'] = array_values($info['fensArea']);
        }

        return $info;
    }

    /**
     * 视频数据格式化
     *
     * @param $v
     * @param $list
     * @return array|false
     */
    public function formatVideoData($v, $list)
    {
        if (!$v) {
            return false;
        }

        // 小视频
        if ($list['data'][0]['_data_type'] ?? 'spiderBaseVideoList' == 'spiderShortVideoList') {
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
        if (empty($data['data'][0]['CreatorDataTrends'][0]['Details'])) {
            throw new OutPlatformException('参数不全');
        }
        $data = $data['data'][0]['CreatorDataTrends'][0]['Details'];
        foreach ($data as $infos) {
            if ($infos['DataType'] == 13) {
                $income[$infos['DataTime']] = $infos['TotalCount'] ?? 0;
            }
            if ($infos['DataType'] == 1) {
                $consume_pv[$infos['DataTime']] = $infos['TotalCount'] ?? 0;
            }
            if ($infos['DataType'] == 9) {
                $like_pv[$infos['DataTime']] = $infos['TotalCount'] ?? 0;
            }
            if ($infos['DataType'] == 6) {
                $fens[$infos['DataTime']] = $infos['TotalCount'] ?? 0;
            }
            if ($infos['DataType'] == 4) {
                $follow_add[$infos['DataTime']] = $infos['TotalCount'] ?? 0;
            }
            if ($infos['DataType'] == 15) {
                $follow_cancel[$infos['DataTime']] = $infos['TotalCount'] ?? 0;
            }
            if ($infos['DataType'] == 11) {
                $fav[$infos['DataTime']] = $infos['TotalCount'] ?? 0;
            }
            if ($infos['DataType'] == 12) {
                $share[$infos['DataTime']] = $infos['TotalCount'] ?? 0;
            }
        }
        if (empty($consume_pv)) {
            throw new OutPlatformException('参数不全');
        }
        foreach ($consume_pv as $k => $v) {
            $info['date'] = date('Y-m-d', $k);
//            empty($follow_add[$k])    ?: $info['follow_add']    = $follow_add[$k];
//            empty($follow_cancel[$k]) ?: $info['follow_cancel'] = $follow_cancel[$k];
            $info['like_pv']     = empty($like_pv[$k]) ? 0 : $like_pv[$k];
            $info['consume_pv']  = empty($consume_pv[$k]) ? 0 : $consume_pv[$k];
            $info['income']      = empty($income[$k]) ? 0 : $income[$k];
            $info['fav_pv']      = empty($fav[$k]) ? 0 : $fav[$k];
            $info['mcn_id']      = $sync['mcn_id'];
            $info['sync_id']     = $sync['sync_id'];
            $info['platform']    = $user['platform'];
            $info['user_id']     = $user['id'];
            $info['sync_status'] = 1;

            UserService::haddleUserLog($info);
        }
    }

    public function formatIncomeData($user, $data, $sync)
    {

    }

    /**
     * 正常视频格式化
     *
     * @param $v
     * @param $list
     * @return array|false
     */
    private function formatNormalVideoData($v) {
        if (empty($v['CreateTime'])) {
            Log::debug('videolog', '==xigua==CreateTime数据失败==' . json_encode($v));
            return false;
        }

        $message = '--';
        if ($v['ArticleStatusText'] == '已发布') {
            $status = 1;
        } elseif ($v['ArticleStatusText'] == '未通过') {
            $status  = 2;
            $message = $v['AuditData']['FeedbackList'][0]['Reason'] ?? '';
        } elseif ($v['ArticleStatusText'] == '转码中' || $v['ArticleStatusText'] == '审核中' || $v['ArticleStatusText'] == '修改审核中') {
            $status = 3;
        } else {
            $status = 1;//3;
        }
        $info['unique']         = $v['ItemId'];
        $info['unique_new']     = $v['ItemId'] ?? '';
        $info['title']          = $v['VideoData']['Title'] ?? '';
        $info['link']           = $v['VideoData']['PlayUrl'] ?? '';
        $info['img']            = $v['VideoData']['CoverUrl'] ?? '';
        $info['status_message'] = $message;
        $info['status']         = $status;
        $info['play']           = $v['ArticleStatData']['PlayCount'] ?? 0;
        $info['recommend']      = $v['ArticleStatData']['RecommendCount'] ?? 0;
        $info['publish_time']   = $v['CreateTime'];
        $info['like']           = $v['ArticleStatData']['DiggCount'] ?? 0;
        $info['share']          = $v['ArticleStatData']['ShareCount'] ?? 0;
        $info['comment']        = $v['ArticleStatData']['CommentCount'] ?? 0;
        $info['collect']        = 0;

        return $info;
    }

    /**
     * 小视频方法格式化
     *
     * @param $v
     * @return array|false
     */
    private function formatShortVideoData($v)
    {
        if (!$v) {
            return false;
        }
        $message = '--';
        if ($v['ArticleAttr']['Status'] == 2) {
            $status = 1;
        } elseif ($v['ArticleAttr']['Status'] == 1) {
            $status  = 2;
            $message = $v['ArticleAttr']['StatusDesc'] ?? '';
        } elseif ($v['ArticleAttr']['Status'] == 3) {
            $status  = 3;
            $message = $v['ArticleAttr']['StatusDesc'] ?? '';
        } else {
            $status = 1;
        }
        $info['unique']         = $v['ArticleAttr']['ItemId'];
        $info['unique_new']     = $v['ArticleAttr']['ItemId'] ?? '';
        $info['title']          = $v['ArticleAttr']['Title'] ?? '';
        $info['link']           = 'https:www.ixigua.com/' . $v['ArticleAttr']['Gid'] . '?utm_source=xiguastudio' ?? '';
        $info['img']            = $v['ArticleAttr']['CoverImage']['ImageURL'] ?? '';
        $info['status_message'] = $message;
        $info['status']         = $status;
        $info['play']           = $v['ArticleStat']['PlayCount'] ?? 0;
        $info['recommend']      = $v['ArticleStat']['ImpressionCount'] ?? 0;
        $info['publish_time']   = $v['ArticleAttr']['CreateTime'];
        $info['like']           = $v['ArticleStat']['DiggCount'] ?? 0;
        $info['share']          = $v['ArticleStat']['ShareCount'] ?? 0;
        $info['comment']        = $v['ArticleStat']['CommentCount'] ?? 0;
        $info['collect']        = 0;

        return $info;
    }


    private function strToNum($str)
    {
        if (!$str) {
            return 0;
        }
        $unit = mb_substr($str, mb_strlen($str) - 1);
        // var_dump($unit);die;
        $num = mb_strstr($str, $unit, true);
        switch ($unit) {
            case '百':
                return $num * 100;
            case '千':
                return $num * 1000;
            case '万':
                return $num * 10000;
            case '十万':
                return $num * 100000;
            case '百万':
                return $num * 1000000;
            case '千万':
                return $num * 10000000;
            case '亿':
                return $num * 100000000;
            default:
                return $num;
        }
    }

    public function formatVideoDetailData($data, $list)
    {
        // TODO: Implement formatVideoDetailData() method.
    }
}
