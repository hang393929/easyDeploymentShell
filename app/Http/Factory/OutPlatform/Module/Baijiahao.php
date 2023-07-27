<?php
namespace App\Http\Factory\OutPlatform\Module;

use App\Http\Helper\Log;
use App\Models\FensGender;
use App\Http\Services\UserService;
use App\Events\OldFensInUserCallBack;
use App\Exceptions\OutPlatformException;
use App\Http\Factory\OutPlatform\OutPlatformBase;

class Baijiahao extends OutPlatformBase
{
    private static $genderMap = [
        '男'   => FensGender::TYPE_MALE,
        '女'   => FensGender::TYPE_FEMALE
    ];

    public function formatUserData($user, $data)
    {
        $haddledata         = $data['data'][0];
        $info['unique_3']   = $haddledata['appinfo']['user']['app_id_new'] ?? '';
        $info['nick_name']  = $haddledata['appinfo']['user']['name_new'] ?? '';
        $info['original']   = $haddledata['appinfo']['user']['identity_original'] ?? 3;
        $info['fens']       = $haddledata['appinfo']['user']['ability']['total_fans'] ?? 0;
        $info['total_play'] = $haddledata['index']['coreData']['viewCount'] ?? 0;
        $info['credit']     = empty($haddledata['getindex']['score']) ? '' : (string)$haddledata['getindex']['score'];
        //$info['credit_str'] = '';

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
        $data               = $data['data'];
        $info['projectId']  = $user->project;
        $info['userUnique'] = $user->unique;
        $info['platformId'] = $user->platform;
        if ($data[0]['getFansProfile']['errno'] == 0 && !empty($data[0]['getFansProfile']['data'])) {
            // 粉丝性别
            if(!empty($data[0]['getFansProfile']['data']['data']['gender'])) {
                $info['fensGender'] = $data[0]['getFansProfile']['data']['data']['gender'];
            }
            // 粉丝数量
            if (!empty($info['fensGender'])) {
                $info['fansCnt']   = collect($data[0]['getFansProfile']['data']['data']['gender'])->flatten()->sum() ?? 0;
            }
            // 粉丝年龄分布
            if(!empty($data[0]['getFansProfile']['data']['data']['age'])) {
                $info['fensAge'] = $data[0]['getFansProfile']['data']['data']['age'];
            }
            // 粉丝兴趣娱乐
            if(!empty($data[0]['getFansProfile']['data']['data']['interest'])) {
                $info['fensInterest'] = $data[0]['getFansProfile']['data']['data']['interest'];
            }
            // 粉丝教育水平
            if(!empty($data[0]['getFansProfile']['data']['data']['education'])) {
                $info['fensEducation'] = $data[0]['getFansProfile']['data']['data']['education'];
            }
            // 粉丝地区分布
            if(!empty($data[0]['getFansProfile']['data']['data']['province'])) {
                $info['fensArea'] = $data[0]['getFansProfile']['data']['data']['province'];
            }

            $this->haddleFanDrawData($info);
        }

        return $info;
    }

    /**
     * 粉丝数据格式化
     *
     * @param $info
     * @return mixed
     */
    private function haddleFanDrawData(&$info)
    {
        if(!empty($info['fensGender'])) {
            $total = array_reduce($info['fensGender'], function ($carry, $item) {
                return $carry + reset($item);
            }, 0);
            $info['fensGender'] = array_map(function ($item) use($total){
                $key   = self::$genderMap[key($item)] ?? FensGender::TYPE_OTHER;
                $value = current($item);
                $ratio = $total == 0 ? 0 :number_format(($value / $total) * 100, 2, '.', '');
                return array(
                    'type'   => $key,
                    'number' => $value,
                    'ratio'  => $ratio,
                );
            }, $info['fensGender']);
        }

        if(!empty($info['fensAge'])) {
            $total = array_reduce($info['fensAge'], function ($carry, $item) {
                return $carry + reset($item);
            }, 0);
            $info['fensAge'] = array_map(function ($item) use ($total){
                $key   = key($item);
                $value = current($item);
                $ratio = $total == 0 ? 0 :number_format(($value / $total) * 100, 2, '.', '');
                return array(
                    'range'  => $key,
                    'number' => $value,
                    'ratio'  => $ratio,
                );
            }, $info['fensAge']);
        }

        if(!empty($info['fensInterest'])) {
            $total = array_reduce($info['fensInterest'], function ($carry, $item) {
                return $carry + reset($item);
            }, 0);
            $info['fensInterest'] = array_map(function ($item) use ($total){
                $key   = key($item);
                $value = current($item);
                $ratio = $total == 0 ? 0 :number_format(($value / $total) * 100, 2, '.', '');
                return array(
                    'name'   => $key,
                    'number' => $value,
                    'ratio'  => $ratio,
                );
            }, $info['fensInterest']);
        }

        if(!empty($info['fensEducation'])) {
            $total = array_reduce($info['fensEducation'], function ($carry, $item) {
                return $carry + reset($item);
            }, 0);
            $info['fensEducation'] = array_map(function ($item) use ($total){
                $key   = key($item);
                $value = current($item);
                $ratio = $total == 0 ? 0 :number_format(($value / $total) * 100, 2, '.', '');
                return array(
                    'name'   => $key,
                    'number' => $value,
                    'ratio'  => $ratio,
                );
            }, $info['fensEducation']);
        }

        if(!empty($info['fensArea'])) {
            $total = array_reduce($info['fensArea'], function ($carry, $item) {
                return $carry + reset($item);
            }, 0);
            $info['fensArea'] = array_map(function ($item) use ($total){
                $key   = key($item);
                $value = current($item);
                $ratio = $total == 0 ? 0 :number_format(($value / $total) * 100, 2, '.', '');
                return array(
                    'name' => $key,
                    'number'   => $value,
                    'ratio'  => $ratio,
                );
            }, $info['fensArea']);
        }

        return $info;
    }

    public function formatVideoData($v, $list)
    {
        if (empty($v['id'])) {
            return false;
        }

        $list = $list['data'][1];
        if ($list) {
            $list = collect($list)->keyBy('key')->toArray();
            if (isset($list[$v['id']])) {
                $value                        = $list[$v['id']];
                $v['cover_images']            = $value['cover_images'] ?? '';
                $v['status']                  = $value['status'] ?? 'publish';
                $v['audit_msg']               = empty($value['audit_msg']) ? '' : mb_substr($value['audit_msg'], 0, 20, 'utf-8');
                $v['quality_not_pass_reason'] = empty($value['quality_not_pass_reason']) ? '' : mb_substr($value['quality_not_pass_reason'], 0, 10, 'utf-8');
                $v['read_amount']             = $value['read_amount'] ?? 0;
                $v['rec_amount']              = $value['rec_amount'] ?? 0;
                $v['like_amount']             = $value['like_amount'] ?? 0;
                $v['share_amount']            = $value['share_amount'] ?? 0;
                $v['comment_amount']          = $value['comment_amount'] ?? 0;
                $v['collection_amount']       = $value['collection_amount'] ?? 0;
            }
        }
        if (!isset($v['publish_time'])) {
            Log::debug('videolog', '==baijia==publish_time数据不存在==' . json_encode($v));

            return false;
        }
        $info['unique']     = $v['id'];
        $info['unique_new'] = $v['transform_id'] ?? '';
        $info['title']      = $v['title'] ?? '';
        $info['link']       = $v['url'] ?? '';
        if (isset($v['cover_images'])) {
            $info['img'] = json_decode($v['cover_images'], true)[0]['src'] ?? '';
        }

        if (isset($v['status'])) {
            if ($v['status'] != 'publish') {
                if ($v['status'] == 'rejected') {
                    $info['status'] = 2;
                } else {
                    $info['status'] = 3;
                }
                if ($v['quality_not_pass_reason']) {
                    $info['status_message'] = $v['audit_msg'] . $v['quality_not_pass_reason'];
                } else {
                    $info['status_message'] = '';
                }
            } else {
                $info['status'] = 1;
            }
        } else {
            $info['status'] = 1;
        }
        $info['play']         = $v['read_amount'] ?? 0;
        $info['recommend']    = $v['rec_amount'] ?? 0;
        $info['tag']          = '--';
        $info['publish_time'] = strtotime($v['publish_time']);
        $info['like']         = $v['like_amount'] ?? 0;
        $info['share']        = $v['share_amount'] ?? 0;
        $info['comment']      = $v['comment_amount'] ?? 0;
        $info['collect']      = $v['collection_amount'] ?? 0;
        $play_rate            = $v['complete_rate'] ?? 0;
        $info['play_rate']    = $play_rate > 0 ? $play_rate : 0;

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
        if (empty($data['data'][0]['list'])) {
            throw new OutPlatformException('参数不全');
        }
        $data = $data['data'][0]['list'];
        if (!empty($data) && is_array($data)) {
            foreach ($data as $item) {
                if (isset($item['sum_fans_count'])) {
                    if (!is_numeric($item['sum_fans_count']) || $item['sum_fans_count'] < 0) {
                        continue;
                    }
                }
                if (isset($item['view_count'])) {
                    if (!is_numeric($item['view_count']) || $item['view_count'] < 0) {
                        continue;
                    }
                }
                $info['date']        = date('Y-m-d', strtotime($item['event_day']));
                $info['consume_pv']  = $item['view_count'] ?? 0;
                $info['cmt_pv']      = $item['comment_count'] ?? 0;
                $info['share_pv']    = $item['share_count'] ?? 0;
                $info['look_pv']     = $item['collect_count'] ?? 0;
                $info['mcn_id']      = $sync['mcn_id'] ?? 0;
                $info['sync_id']     = $sync['sync_id'] ?? 0;
                $info['like_pv']     = $item['likes_count'] ?? 0;
                $info['recommend']   = $item['recommend_count'] ?? 0;
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
        if (empty($data['key'])) {
            throw new OutPlatformException('参数不全');
        }
        if (empty($data['ArticleStatisticV2'])) {
            throw new OutPlatformException('参数不全');
        }
        $info = $data['ArticleStatisticV2']['data'];
        $res['info']['unique']      = $data['key']['id'];
//        $res['info']['unique_new']  = $data['key']['transform_id'];
        $res['info']['title']       = $info['title'];
        $res['info']['play']        = $info['view_count'];
        $res['info']['like']        = $info['likes_count'];
        $res['info']['comment']     = $info['comment_count'];
        $res['info']['share']       = $info['share_count'];
        $res['info']['recommend']   = $info['rec_count'];
        $res['info']['collect']     = $info['collect_count'];

        $res['detail']=[];
        if (!empty($data['articleDailyListStatisticV2']['data'])){
            $dayInfo = $data['articleDailyListStatisticV2']['data']['list'];
            foreach ($dayInfo as $key=>$item) {
                $res['detail'][$key]['unique']         = $data['key']['id'];
                $res['detail'][$key]['unique_new']     = $data['key']['transform_id'];
                $res['detail'][$key]['date']           = date('Y-m-d', strtotime($item['event_day']));
                $res['detail'][$key]['recommend_num']  = $item['recommend_count'];
                $res['detail'][$key]['play_num']       = $item['view_count'];
                $res['detail'][$key]['like_num']       = $item['likes_count'];
                $res['detail'][$key]['comment_num']    = $item['comment_count'];
                $res['detail'][$key]['collect_num']    = $item['collect_count'];
                $res['detail'][$key]['share_num']      = $item['share_count'];
                $res['detail'][$key]['add_fens_count'] = $item['fans_increase'];
            }
        }
        return $res;
    }
}
