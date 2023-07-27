<?php
namespace App\Http\Services;

use App\Http\Repository\Video\VideoDetailRepository;
use Illuminate\Support\Arr;
use App\Constants\RedisKey;
use App\Constants\PlatformType;
use App\Http\Traits\FensHaddleFunc;
use App\Http\Helper\Log as LogHelper;
use Illuminate\Support\Facades\Redis;
use App\Exceptions\OutPlatformException;
use App\Http\Repository\Fens\DrawRepository;
use App\Http\Repository\User\UserRepository;
use App\Http\Repository\Video\VideoRepository;
use App\Http\Repository\Task\PushAllRepository;
use App\Http\Repository\Task\PushTaskRepository;
use App\Http\Repository\Video\VideoDataRepository;
use App\Http\Factory\OutPlatform\OutPlatformFactory;
use App\Jobs\CallBackVideoAdd as CallBackVideoAddJob;
use App\Jobs\CallBackVideoUpdate as CallBackVideoUpdateJob;

class CallBackService
{
    use FensHaddleFunc;
    private $dataType='';
    /**
     * 视频回调数据处理
     *
     * @param $user
     * @param $data
     * @return void
     * @throws OutPlatformException
     */
    public function haddleCallBackVideo($user, $sync, $data) {
        if(empty($user) || empty($data) || !$list = $this->CallBackVideoFactor($user, $data)) {
            throw new OutPlatformException('参数异常');
        }
        foreach ($list as $v) {
            $video = OutPlatformFactory::register($user->platform)->formatVideoData($v, $data);
            if(empty($video)) {
                continue;
            }
            $video['project'] = $user['project'];
            $video['platform'] = $user['platform'];
            //发布中的直接丢掉|| $video['status']==3
            if(empty($video['title']) || empty($video['unique'])){
                continue;
            }

            //调用匹配task新逻辑
            $pushAll = $this->checkCallbackVideoCreate($video);
            if (!empty($pushAll)) {
                if($video['unique_new'] && $pushAll->unique_new && $pushAll->unique_new !=$video['unique_new']){
                    LogHelper::info('errvideolog', '==不匹配video==' . json_encode($video));
                    continue;
                }

                $video['push_all_id']   = $pushAll->id;
                $video['project']       = $pushAll->project;
                $video['sync_id']       = $pushAll->sync_id;
                $video['mcn_id']        = $sync->mcn_id;
                $video['classify']      = $pushAll->classify;
                $video['channel_child'] = $pushAll->channel_child;
                $video['user_id']       = $pushAll->user_id;
                $video['platform']      = $pushAll->platform;
                if (!empty($video['status_message'])) {
                    $video['status_message'] = mb_substr($video['status_message'], 0, 500);
                }

                $info = app(VideoRepository::class)->getByUnique($video['unique']);
                if ($info) {
                    $video['id'] = $info['id'];
                    // 压入队列 配置5号库
                    dispatch(new CallBackVideoUpdateJob($video))->onConnection('redis_queue_five')->onQueue('callBackVideoUpdate');
                } else {
                    // 压入队列 配置5号库
                    dispatch(new CallBackVideoAddJob($video))->onConnection('redis_queue_five')->onQueue('callBackVideoAdd');
                }

                if (Arr::exists($video, 'oneself_img')) {
                    unset($video['oneself_img']);
                }
                if (Arr::exists($video, 'unique_new')) {
                    unset($video['unique_new']);
                }
                if (Arr::exists($video,'project')){
                    unset($video['project']);
                }
                if (Arr::exists($video,'classify')){
                    unset($video['classify']);
                }
                if (Arr::exists($video,'channel_child')){
                    unset($video['channel_child']);
                }
                app(VideoRepository::class)->addVideoLog($video);
            }
        }

    }

    /**
     * 验证video数据是否可用
     *
     * @param $data
     * @return false
     */
    private function checkCallbackVideoCreate($data) {
        //匹配pushTask数据是否存在
        $pushTaskList = [];
        if ($data['unique_new']) {
            $pushTaskList = app(PushTaskRepository::class)->getListByUserIdAndAndUniqueNew(
                $data['unique_new'], $data['platform'], $data['project'], true
            );
        }
        //上一层条件匹配不到 使用title匹配
        if (!$pushTaskList && in_array($data['platform'],[1,11])) {
            $pushTaskList = app(PushTaskRepository::class)->getListByUserAndTitle(
                VideoService::checkVideoTitle($data['title']), $data['platform'], $data['project'], true
            );
        }
        if (!$pushTaskList || count($pushTaskList) == 0) {
            return false;
        }

        if (count($pushTaskList) > 1) {
            $begin = 0;
            $res   = [];
            foreach ($pushTaskList as $value) {
                $end = $data['publish_time'] - $value['publish_time'];
                if ($begin == 0) {
                    $begin = $end;
                }
                if ($end > 0 && $end <= $begin) {
                    $res   = $value;
                    $begin = $end;
                }
            }
            if (empty($res)) {
                return false;
            }
            $res = $this->getTaskTime($res);
            if (!$res) {
                return false;
            }

            $pushAll = app(PushAllRepository::class)->getByIds($res['parent_id']);
            if ($pushAll) {
                $pushAll->user_id    = $res['user_id'];
                $pushAll->platform   = $res['platform'];
                $pushAll->unique_new = $res['unique_new'];

                return $pushAll;
            }

            return false;
        } else {
            $pushTask = $pushTaskList[0];
            $pushTask = $this->getTaskTime($pushTask);
            if (!$pushTask) {
                return false;
            }

            $pushAll = app(PushAllRepository::class)->getByIds($pushTask['parent_id']);
            if ($pushAll) {
                $pushAll->user_id    = $pushTask['user_id'];
                $pushAll->platform   = $pushTask['platform'];
                $pushAll->unique_new = $pushTask['unique_new'];

                return $pushAll;
            }

            return false;
        }
    }

    private function getTaskTime($data){
        $now = '1687104000';
        if (!$data) {
            return false;
        }

        if ($data['publish_time'] >= $now) {
            if ($data['status'] == 1) {
                return $data;
            }

            return false;
        }

        return $data;
    }

    /**
     * 视频数据前置参数工厂处理
     *
     * @param $user
     * @param $data
     * @return array|mixed
     */
    private function CallBackVideoFactor($user, $data) {
        switch ($user['platform']) {
            case PlatformType::DOUYIN:
                $list = $data['data'][0];
                break;
            case PlatformType::TENGXUNSHIPIN:
                $list = $data['data'][0]['list'];
                break;
            case PlatformType::XIGUASHIPIN:
                $this ->dataType = $data['data'][0]['_data_type'] ?? 'spiderBaseVideoList';
                if ($this ->dataType == 'spiderBaseVideoList') {
                    $list = $data['data'][0]['ArticleList'];
                } else {
                    $list = $data['data'][0]['Contents'];
                }
//                $list = $data['data'][0]['ArticleList'];
                break;
            case PlatformType::DAYUHAO:
                $list = $data['data'][0]['getArticleList']['data'];
                break;
            case PlatformType::WEIXINSHIPINHAO:
                $list = $data['data'][0]['list'];
                break;
            case PlatformType::KUAISHOU:
                $list = $data['data'][0]['list'];
                break;
            case PlatformType::BAIJIAHAO:
                $list = $data['data'][0] ?? [];
                break;
            case PlatformType::XIAOHONGSHU:
                $list = $data['data'][0]['notes'] ?? [];
                break;
            case PlatformType::JINGDONG:
                $list = $data['data'][0]['content'] ?? [];
                break;
            case PlatformType::TAOBAO:
                $list = $data['data'][0]['data'] ?? [];
                break;
            case PlatformType::BILIBILI:
                $list = $data['data'][0]['arc_audits'] ?? [];
                break;
            case PlatformType::PINDUODUO:
                $list = $data['data'][0]['feeds'] ?? [];
                break;
            default:
                $list = [];
                break;
        }

        return $list;
    }

    /**
     * 视频详情工厂数据转化
     * @param $user
     * @param $data
     * @return array|mixed
     */
    private function CallBackVideoDetailFactor($user, $data) {
        switch ($user['platform']) {
            case PlatformType::DOUYIN:
                $list = $data['data'][0];
                break;
            case PlatformType::TENGXUNSHIPIN:
                $list = $data['data'][0]['list'];
                break;
            case PlatformType::XIGUASHIPIN:
                $this ->dataType = $data['data'][0]['_data_type'] ?? 'spiderBaseVideoList';
                if ($this ->dataType == 'spiderBaseVideoList') {
                    $list = $data['data'][0]['ArticleList'];
                } else {
                    $list = $data['data'][0]['Contents'];
                }
//                $list = $data['data'][0]['ArticleList'];
                break;
            case PlatformType::DAYUHAO:
                $list = $data['data'][0]['getArticleList']['data'];
                break;
            case PlatformType::WEIXINSHIPINHAO:
                $list = $data['data'][0]['list'];
                break;
            case PlatformType::KUAISHOU:
                $list = $data['data'][0]['list'];
                break;
            case PlatformType::BAIJIAHAO:
                $list = $data['data'] ?? [];
                break;
            case PlatformType::XIAOHONGSHU:
                $list = $data['data'][0]['note_infos'] ?? [];
                break;
            case PlatformType::JINGDONG:
                $list = $data['data'][0]['content'] ?? [];
                break;
            case PlatformType::TAOBAO:
                $list = $data['data'][0]['data'] ?? [];
                break;
            case PlatformType::BILIBILI:
                $list = $data['data'][0]['arc_audits'] ?? [];
                break;
            case PlatformType::PINDUODUO:
                $list = $data['data'][0]['feeds'] ?? [];
                break;
            default:
                $list = [];
                break;
        }

        return $list;
    }
    /**
     * 处理粉丝相关数据
     *
     * @param array $data
     * @return void
     */
    public static function haddleCallBackFens(array $data) {
        if(empty($data) || empty($data['projectId']) || empty($data['userUnique']) || empty($data['platformId'])) {
            throw new OutPlatformException('当前参数不全');
        }

        // 粉丝数
        $fensDraw = app(DrawRepository::class)->getFensDrowOnly($data['userUnique'], $data['platformId'], $data['projectId']);
        if($fensDraw) {
            empty($data['fansCnt']) ?: app(DrawRepository::class)->updateFansCntByModel($fensDraw, $data['fansCnt']);
            $fensDrawId = $fensDraw->id;
        } else {
            $fensDrawId = app(DrawRepository::class)->insertFensDraw(
                $data['userUnique'], $data['platformId'], $data['projectId'], $data['fansCnt'] ?? 0
            );
        }

        unset($fensDraw);

        // 年龄
        empty($data['fensAge'])       ?: self::haddleAge($fensDrawId, $data['fensAge']);
        // 地域
        empty($data['fensArea'])      ?: self::haddleArea($fensDrawId, $data['fensArea']);
        // 城市
        empty($data['fensCity'])      ?: self::haddleCity($fensDrawId, $data['fensCity']);
        // 学历
        empty($data['fensEducation']) ?: self::haddleEducation($fensDrawId, $data['fensEducation']);
        // 性别
        empty($data['fensGender'])    ?: self::haddleGender($fensDrawId, $data['fensGender']);
        // 兴趣爱好
        empty($data['fensInterest'])  ?: self::haddleInterest($fensDrawId, $data['fensInterest']);
    }

    /**
     * 幂等性效验（取出重复数据，缓存一小时）
     *
     * @param array $data
     * @param $user
     * @return bool
     */
    public static function repeatFilter(array $data, $key, $select = 0)
    {
        Redis::select($select);
        $cachedData = Redis::get($key);

        if ($cachedData) {
            // 缓存存在，判断是否与 $data 相同
            if ($cachedData === md5(json_encode($data))) {
                return false;
            }
        }

        // 缓存不存在，将 $data 存入缓存, 即使有，在缓存中没匹配上，更新当前最新数据到缓存
        Redis::set($key, md5(json_encode($data)));
        // 设置缓存过期时间，1 小时
        Redis::expire($key, 60 * 60);

        return true;
    }

    /**
     * 通过用户ID更新数据
     *
     * @param int $id
     * @param array $data
     * @return mixed
     */
    public static function haddleCallBackUser(int $id, array $data) {
        return app(UserRepository::class)->updateByIds($id, $data);
    }

    /**
     * 处理income相关数据
     *
     * @param array $data
     * @return void
     * @throws OutPlatformException
     */
    public static function haddleCallBackIncome(array $data) {
        $user = UserService::getUserByIds($data['user_id']);
        if(!$user) {
            throw new OutPlatformException('老粉丝数据异常');
        }
        $sync = UserService::getSyncUserByIds($user['user_id']);
        if(!$sync) {
            throw new OutPlatformException('老粉丝数据异常');
        }

        // 解析+入库
        OutPlatformFactory::register($user['platform'])->formatIncomeData($user, $data, $sync);
    }

    /**
     * 回调视频数据锁
     *
     * @param int $userId
     * @param bool $get
     * @return bool|void
     */
    public static function lockForCallBackVideo(int $userId , bool $get = true) {
        Redis::select(RedisKey::REDIS_DATABASES_TWO);
        $fiveMinute = 'callVideo_fiveMinute_' . $userId;

        if($get) {
            return Redis::get('callVideo' . $userId) && !Redis::get($fiveMinute);
        }

        if(!Redis::get('callVideo' . $userId)) {
            Redis::set('callVideo' . $userId, json_encode($userId, JSON_UNESCAPED_UNICODE));
            Redis::expire('callVideo' . $userId, 60 * 60);
            Redis::set($fiveMinute, json_encode($userId, JSON_UNESCAPED_UNICODE));
            Redis::expire($fiveMinute, 60 * 5);
        }
    }

    /**
     * 新增VideoData
     *
     * @param array $data
     * @return mixed
     */
    public static function addVideoData(array $data) {
        return app(VideoDataRepository::class)->insertData($data);
    }

    /**
     * 视频详情数据
     * @param $user
     * @param $sync
     * @param $data
     * @return void
     * @throws OutPlatformException
     */
    public function haddleCallBackVideoDetail($user, $data){
        if(empty($user) || empty($data) || !$list = $this->CallBackVideoDetailFactor($user, $data)) {
            throw new OutPlatformException('视频详情参数异常');
        }

        foreach ($list as $v) {
            $video = OutPlatformFactory::formatVideoDetailData($user->platform,$v, $data);
            if(empty($video)) {
                continue;
            }
            $info=$video['info'];
            //详情标题和唯一值为空的过滤
            if(empty($info['title']) || empty($info['unique'])){
                continue;
            }
            //调用video主表，判断是否存在
            $videoInfo = app(VideoRepository::class)->getByUnique($info['unique']);
            if (empty($videoInfo)){
                continue;
            }
//            $videoInfo->update($info);
//            app(VideoRepository::class)->updateById($videoInfo['id'],$info);
            if (empty($video['detail'])){
                continue;
            }else{
                $detailList=$video['detail'];
                foreach ($detailList as $item) {
                    $detail['video_id']      = $videoInfo->id;
                    $detail['sync_id']       = $videoInfo->sync_id;
                    $detail['mcn_id']        = $videoInfo->mcn_id ?? 0;
                    $detail['user_id']       = $videoInfo->user_id;
                    $detail['platform']      = $videoInfo->platform;
                    $detail['project']       = $videoInfo->project;
                    $detail['classify']      = $videoInfo->classify;
                    $detail['channel_child'] = $videoInfo->channel_child;
                    $detail['date']          = $item['date'];
                    $detail['unique']        = $item['unique'];
                    $detail['unique_new']    = $item['unique_new'];
                    $detail['recommend_num'] = $item['recommend_num'] ?? 0;
                    $detail['play_num']      = $item['play_num'] ?? 0;
                    $detail['like_num']      = $item['like_num'] ?? 0;
                    $detail['comment_num']   = $item['comment_num'] ?? 0;
                    $detail['collect_num']   = $item['collect_num'] ?? 0;
                    $detail['share_num']     = $item['share_num'] ?? 0;
                    $detail['add_fens_count']= $item['add_fens_count'] ?? 0;
                    $detail['danmu_num']     = $item['danmu_num'] ?? 0;
                    app(VideoDetailRepository::class)->addVideoDetail($detail);
                }
            }
        }
    }
}
