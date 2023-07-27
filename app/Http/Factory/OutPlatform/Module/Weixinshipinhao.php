<?php
namespace App\Http\Factory\OutPlatform\Module;

use App\Http\Helper\Log;
use App\Http\Helper\Util;
use App\Models\FensGender;
use Illuminate\Support\Collection;
use App\Http\Services\UserService;
use App\Exceptions\OutPlatformException;
use App\Http\Factory\OutPlatform\OutPlatformBase;

class Weixinshipinhao extends OutPlatformBase
{
    private static $genderMap = [
        'unknow' => FensGender::TYPE_OTHER,
        '2'      => FensGender::TYPE_FEMALE,
        '1'      => FensGender::TYPE_MALE
    ];

    public function formatUserData($user, $data)
    {
        $info['user_name'] = $data['data'][0]['finderUser']['nickname'];
        $fens              = $data['data'][0]['finderUser']['fansCount'] ?? 0;
        $info['fens']      = $fens > 0 ? $fens : 0;
        $info['unique_3']  = $data['data'][0]['finderUser']['uniqId_new'] ?? "";
        $info['nick_name'] = $data['data'][0]['finderUser']['nickname_new'] ?? "";
        $info['video']     = $data['data'][0]['finderUser']['feedsCount'];

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
        $data = is_array($data['data'][0]['data']['respJson'])
            ? $data['data'][0]['data']['respJson']['metric_info_list']
            : json_decode($data['data'][0]['data']['respJson'], true)['metric_info_list'];
        if (empty($data)) {
            throw new OutPlatformException('抓取为空数据');
        }

        $info['projectId']  = $user->project;
        $info['userUnique'] = $user->unique;
        $info['platformId'] = $user->platform;

        foreach ($data as $value) {
            if (!empty($value['name'])) {
                if ($value['name'] == 'age_list') {
                    // 粉丝年龄分布
                    $info['fensAge'] = empty($value['value']) ? '' : $value['value'];
                }
                if ($value['name'] == 'sex_list') {
                    // 性别分布
                    $info['fensGender'] = empty($value['value']) ? '' : $value['value'];
                    // 粉丝数
                    $info['fansCnt'] = Collection::make($value["value"])->where("dim", "@_all")
                        ->pluck("value")->first();
                }
                if ($value['name'] == 'province_list') {
                    // 地域分布
                    $info['fensArea'] = empty($value['value']) ? '' : $value['value'];
                }
                if ($value['name'] == 'city_list') {
                    // 城市分布
                    $info['fensCity'] = empty($value['value']) ? '' : $value['value'];
                }
            }
        }

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
                    'range'  => str_contains($age['dim'], 'fage_') ? substr($age['dim'], 5) : $age['dim'],
                    'number' => $age['value'],
                    'ratio'  => $ratio
                );
            }

            $info['fensAge'] = array_values($info['fensAge']);
        }

        if(!empty($info['fensGender'])) {
            $group = Util::arrayKeyBy($info['fensGender'], 'dim');
            $total = $group['@_all']['value'];
            unset($group['@_all']);
            foreach (array_values($group) as $key => $gender) {
                $ratio = number_format(($gender['value'] / $total) * 100, 2, '.', '');
                $info['fensGender'][$key] = array(
                    'type'   => self::$genderMap[$gender['dim']],
                    'number' => $gender['value'],
                    'ratio'  => $ratio
                );
            }

            // 过滤嵌套多的值
            $info['fensGender'] = array_filter($info['fensGender'], function ($item) {
                return isset($item['type']);
            });

            $info['fensGender'] = array_values($info['fensGender']);
        }

        if(!empty($info['fensCity'])) {
            $total = array_sum(array_column($info['fensCity'], 'value'));
            foreach ($info['fensCity'] as $key => $area) {
                $ratio = number_format(($area['value'] / $total)  * 100, 2, '.', '');

                $info['fensCity'][$key] = array(
                    'name'   => $area['dim'],
                    'number' => $area['value'],
                    'ratio'  => $ratio
                );
            }

            $info['fensCity'] = array_values($info['fensCity']);
        }

        if(!empty($info['fensArea'])) {
            $total = array_sum(array_column($info['fensArea'], 'value'));
            foreach ($info['fensArea'] as $key => $area) {
                $ratio = number_format(($area['value'] / $total)  * 100, 2, '.', '');

                $info['fensArea'][$key] = array(
                    'name'   => $area['dim'],
                    'number' => $area['value'],
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
        if (empty($v['createTime'])) {
            Log::debug('videolog', '==weixin==createTime数据失败==' . json_encode($v));
            return false;
        }

        $message = '--';
        if ($v['status'] == 1) {
            $status = 1;
        } else {
            $status = 2;
        }
        $info['unique']         = $v['objectNonce'];
        $info['unique_new']     = $v['desc']['media'][0]['md5sum'] ?? '';
        $info['title']          = $v['desc']['description'];
        $info['link']           = $v['desc']['media'][0]['url'] ?? '';
        $info['img']            = $v['desc']['media'][0]['thumbUrl'] ?? '';
        $info['status_message'] = $message;
        $info['status']         = $status;
        $info['play']           = $v['readCount'] ?? 0;
        $info['like']           = $v['likeCount'] ?? 0;
        $info['doc_id']         = $v['objectId'] ?? '';
        $info['comment']        = $v['commentCount'] ?? 0;
        $info['share']          = $v['forwardCount'] ?? 0;
        $info['recommend']      = 0;
        $info['publish_time']   = $v['createTime'];
        $info['collect']        = $v['favCount'] ?? 0;

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
        if (empty($data['data'][0]['totalData']['browse'])) {
            throw new OutPlatformException('参数不全');
        }
        foreach ($data['data'][0]['totalData']['browse'] as $k => $v) {
            $day                 = 8 - $k;
            $info['date']        = date('Y-m-d', strtotime("- $day days"));
            $info['consume_pv']  = $v ?? 0;
            $info['like_pv']     = $data['data'][0]['totalData']['like'][$k] ?? 0;
            $info['fav_pv']      = $data['data'][0]['totalData']['fav'][$k] ?? 0;
            $info['cmt_pv']      = $data['data'][0]['totalData']['comment'][$k] ?? 0;
            $info['share_pv']    = $data['data'][0]['totalData']['forward'][$k] ?? 0;
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
        foreach ($data['data'][0]['total'] as $k=> $v)
        {
            $info = [];
            $info['user_id']       = $user['id'];
            $day                   = 2 - $k;
            $info['date']          = date('Y-m-d', strtotime("- $day days"));
            $info['fens']          = $v ?? 0;
            $info['follow_cancel'] = $data['data'][0]['reduce'][$k] ?? 0;
            $info['follow_add']    = $data['data'][0]['netAdd'][$k] ?? 0;
            $info['platform']      = $user['platform'];

            UserService::haddleUserLog($info);
        }
    }

    public function formatVideoDetailData($data, $list)
    {
        // TODO: Implement formatVideoDetailData() method.
    }
}
