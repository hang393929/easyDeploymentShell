<?php
/**
 * @deprecated 临时使用，用户检测user数据
 */
namespace App\Console\Commands\CallBack;

use App\Constants\RedisKey;
use App\Exceptions\OutPlatformException;
use App\Exceptions\ResponseCode;
use App\Http\Factory\OutPlatform\OutPlatformFactory;
use App\Jobs\CallBackFens as CallBackFensJob;
use Illuminate\Console\Command;
use App\Http\Services\UserService;
use App\Http\Helper\Log as LogHelper;
use Illuminate\Support\Facades\Redis;
use App\Http\Services\CallBackService;

class CallBackUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'callback:users';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '用户数据解析';

    protected static $base = RedisKey::REDIS_DATABASES_ONE; // redis库
    protected static $mode = 'order'; // 执行顺序
    protected static $key  = 'callbackUserList'; // redis解析队列key

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @deprecated 不用定时任务，直接压队列
     * @return int
     */
    public function handle(CallBackService $callBackService, UserService $userService)
    {
//        Redis::select(self::$base);
//        while (true) {
//            $redisData = Redis::LPOP(self::$key);
//            $data = is_array($redisData) ? $redisData : json_decode($redisData, true);
//
//            $userId = $data['user_id'];
//
//            if (!$userService->checkUserIsExistById($userId)) {
//                continue;
//            }
//
//            // 压入队列 配置4号库
//            dispatch(new \App\Jobs\CallBackUser($data))->onConnection('redis_queue_three')->onQueue('callBackUser');
//
//        }
//        exit;

        $i = 0;
        Redis::select(self::$base);
        while (true) {
            if (self::$mode == 'cross') {
                // 首尾交替处理 每次处理一条数据
//                $redisData = time() & 1 ? Redis::lpop(self::$key) : Redis::rpop(self::$key);
            } else {
                // 顺序
//                $redisData = Redis::LPOP(self::$key);
                $i++;
//                LogHelper::add('data/', '当前数据:' . $redisData);
                $redisData = '{"data":[{"overview_data_v3":{"isShowLoyal":false,"isNewLoyal":false,"overviewList":[{"MeetCondition":true,"TotalCount":0,"FansCount":0,"DataType":1,"TotalCountText":"播放量","LatestUpdateDateTotal":"2023-07-13","FansCountText":"粉丝播放量","LatestUpdateDateFans":"2023-07-13"},{"MeetCondition":true,"TotalCount":0,"FansCount":0,"DataType":3,"TotalCountText":"评论量","LatestUpdateDateTotal":"2023-07-13","FansCountText":"粉丝评论量","LatestUpdateDateFans":"2023-07-13"},{"MeetCondition":true,"TotalCount":0,"DataType":6,"TotalCountText":"粉丝量","LatestUpdateDateTotal":"2023-07-13"},{"MeetCondition":true,"TotalCount":0,"DataType":13,"TotalCountText":"视频创作收益/元","LatestUpdateDateTotal":"2023-07-12"}]},"author_benefits":{"CreatorProject":true,"FansCount":0,"CreditData":{"CreditScore":100,"Tip":"","ScoreChange":0,"StickId":0,"StickIdList":[]},"Benenfits":[{"BenefitsLevel":100,"BenefitsDescribe":"基础权益","LevelType":0,"Benefits":[{"BenefitType":21,"Name":"视频创作收益","Describe":"发布原创横版视频（时长 >= 1分钟），西瓜视频、抖音、今日头条的播放量可按照平台规则计算创作收益","Status":0,"Tag":"","Message":"","FreezeExpireTime":1687156714,"ApplyType":3,"Schema":"sslocal://webview?url=https%3A%2F%2Fxgfe.snssdk.com%2Fvideofe%2Ffeoffline%2Freward_project%2Findex.html%3Fenter_from%3Dcreator_plan&hide_status_bar=1&hide_bar=1&hide_back_button=1&status_bar_text_color=white&status_bar_color=white&background_color=%23f3dfcc&webview_bg_color=fff&disable_web_progressView=1","Url":"https://studio.ixigua.com/mvp?enter_from=creator_plan","MessageSchema":"","MessageUrl":"","IsVideoAdNew":true,"GreyBenefitImage":"http://p3-sign.toutiaoimg.com/tos-cn-i-tt/250cdc154922bfba2b43b81d78f97085.png~tplv-tt-data-cut-02.png?x-expires=1691917296&x-signature=eUh174Sdkc1Q9RRscSYKoocu8e4%3D","ColorBenefitImage":"http://p3-sign.toutiaoimg.com/tos-cn-i-tt/bd3c0237cb3bb33c255df4f3c49f2a9c.png~tplv-tt-data-cut-02.png?x-expires=1691917296&x-signature=qNu7%2BKHENi1Dzf4e2OiQ9fAvkY0%3D","ReApplyMessage":"","CreateTime":1687156714},{"BenefitType":14,"Name":"视频原创","Describe":"发布横版视频并勾选「原创」，可获得更多推荐与创作收益，并享受原创保护","Status":3,"Tag":"","Message":"","FreezeExpireTime":1687156714,"ApplyType":1,"Schema":"","Url":"","MessageSchema":"","MessageUrl":"","IsVideoAdNew":false,"GreyBenefitImage":"http://p3-sign.toutiaoimg.com/tos-cn-i-tt/7f7359e29d2fdeea79ac452c387ee784.png~tplv-tt-data-cut-02.png?x-expires=1691917296&x-signature=h9kjHhsiieRqoghIqLk3mlX2Nbs%3D","ColorBenefitImage":"http://p3-sign.toutiaoimg.com/tos-cn-i-tt/902fcffbc9bb5ee1c58c5a0c1702bc34.png~tplv-tt-data-cut-02.png?x-expires=1691917296&x-signature=30757HBg3GtCOgp9dk6F1EqVmlM%3D","ReApplyMessage":"","CreateTime":1687156714}],"FansCountThresHold":0},{"BenefitsLevel":200,"BenefitsDescribe":"千粉权益","LevelType":0,"Benefits":[{"BenefitType":16,"Name":"视频赞赏","Describe":"权益开通后，观众可对作者发布的视频进行打赏，所得收益全部归作者所有","Status":0,"Tag":"","Message":"","FreezeExpireTime":0,"ApplyType":1,"Schema":"","Url":"","MessageSchema":"","MessageUrl":"","IsVideoAdNew":false,"GreyBenefitImage":"http://p3-sign.toutiaoimg.com/tos-cn-i-tt/c5a1ef46425a62918b412537aab20c0a.png~tplv-tt-data-cut-02.png?x-expires=1691917296&x-signature=uDNV3QgmlPMp5XJtZzYsgIHzMr8%3D","ColorBenefitImage":"http://p3-sign.toutiaoimg.com/tos-cn-i-tt/32a7571f7daaafbc6bde7d65e9e65e63.png~tplv-tt-data-cut-02.png?x-expires=1691917296&x-signature=5kyNDsphXoglF5%2FSWyPJL2LsSq0%3D","ReApplyMessage":""}],"FansCountThresHold":1000},{"BenefitsLevel":300,"BenefitsDescribe":"万粉权益","LevelType":0,"Benefits":[{"BenefitType":6,"Name":"付费专栏","Describe":"发布多种形式付费内容，自主定价进行售卖，专栏被购买后作者可获得收益分成","Status":0,"Tag":"","Message":"","FreezeExpireTime":0,"ApplyType":2,"Schema":"","Url":"cert_auth","MessageSchema":"","MessageUrl":"","IsVideoAdNew":false,"GreyBenefitImage":"http://p3-sign.toutiaoimg.com/tos-cn-i-tt/87cd1702643bca1432c48eaf959887ac.png~tplv-tt-data-cut-02.png?x-expires=1691917296&x-signature=WXsvmWOIbvLxHYrB56yHRBt5ryg%3D","ColorBenefitImage":"http://p3-sign.toutiaoimg.com/tos-cn-i-tt/f66aa41a72581a7844754adf163a2681.png~tplv-tt-data-cut-02.png?x-expires=1691917296&x-signature=9vYPwbaw83q4CFiRufr1apil8IY%3D","IsUserAuth":true,"ReApplyMessage":""},{"BenefitType":24,"Name":"视频排雷小助手","Describe":"对作品进行全方位审核，可提前了解作品是否符合平台规范，获得详细的修改建议","Status":0,"Tag":"","Message":"","FreezeExpireTime":0,"ApplyType":1,"Schema":"","Url":"","MessageSchema":"","MessageUrl":"","IsVideoAdNew":false,"GreyBenefitImage":"http://p3-sign.toutiaoimg.com/tos-cn-i-6fytvtotv6/3d50bc0b1d357bc4bb8246b00ee4368f.png~tplv-tt-data-cut-02.png?x-expires=1691917296&x-signature=SF1ADzPaiW3hofgakWyMYbmVTjo%3D","ColorBenefitImage":"http://p3-sign.toutiaoimg.com/tos-cn-i-6fytvtotv6/82f9ba710fbcf5bedea5f33623213373.png~tplv-tt-data-cut-02.png?x-expires=1691917296&x-signature=Pj9xMFDB2g9rzIiiXfWGhTbXjoU%3D","ReApplyMessage":""}],"FansCountThresHold":10000},{"BenefitsLevel":400,"BenefitsDescribe":"五万粉权益","LevelType":0,"Benefits":[{"BenefitType":13,"Name":"VIP客服","Describe":"反馈内容可优先获得人工客服的响应，协助你更好地使用平台","Status":0,"Tag":"","Message":"","FreezeExpireTime":0,"ApplyType":1,"Schema":"","Url":"","MessageSchema":"","MessageUrl":"","IsVideoAdNew":false,"GreyBenefitImage":"http://p26-sign.toutiaoimg.com/tos-cn-i-tt/5a58503d3f06e247eec4bda182ecf2c5.png~tplv-tt-data-cut-02.png?x-expires=1691917296&x-signature=So8SaF%2FmOjQR%2FqFCbADLg56g7zA%3D","ColorBenefitImage":"http://p3-sign.toutiaoimg.com/tos-cn-i-tt/259d545b2d52764703a4422bfd0fb3a2.png~tplv-tt-data-cut-02.png?x-expires=1691917296&x-signature=MLe8PF8iMuxecAnEIOJdVjcgwAw%3D","ReApplyMessage":""}],"FansCountThresHold":50000}],"IsLowQuality":false,"CreatorProjectInfo":{"Type":0,"Status":0,"Message":"","CanReJoin":false},"FansThreshold":0,"BaseResp":{"StatusMessage":"","StatusCode":0},"creator_project_status":{"Type":0,"Status":0,"Message":"","CanReJoin":false},"is_low_quality":false},"data_trend_v2_4":{"AccountCreateTime":1623940717,"IsShowLoyal":false,"IsNewLoyal":false,"CreatorDataTrends":[{"TimeType":0,"MeetCondition":true,"DataType":4,"Details":[{"MeetCondition":true,"TotalCount":220,"TimeAfterPublish":750,"DataTime":1688659200,"DataType":4,"TotalCountText":"净增粉丝","LatestUpdateDateTotal":"2023-07-13"},{"MeetCondition":true,"TotalCount":0,"TimeAfterPublish":751,"DataTime":1688745600,"DataType":4,"TotalCountText":"净增粉丝","LatestUpdateDateTotal":"2023-07-13"},{"MeetCondition":true,"TotalCount":0,"TimeAfterPublish":752,"DataTime":1688832000,"DataType":4,"TotalCountText":"净增粉丝","LatestUpdateDateTotal":"2023-07-13"},{"MeetCondition":true,"TotalCount":0,"TimeAfterPublish":753,"DataTime":1688918400,"DataType":4,"TotalCountText":"净增粉丝","LatestUpdateDateTotal":"2023-07-13"},{"MeetCondition":true,"TotalCount":0,"TimeAfterPublish":754,"DataTime":1689004800,"DataType":4,"TotalCountText":"净增粉丝","LatestUpdateDateTotal":"2023-07-13"},{"MeetCondition":true,"TotalCount":0,"TimeAfterPublish":755,"DataTime":1689091200,"DataType":4,"TotalCountText":"净增粉丝","LatestUpdateDateTotal":"2023-07-13"},{"MeetCondition":true,"TotalCount":0,"TimeAfterPublish":756,"DataTime":1689177600,"DataType":4,"TotalCountText":"净增粉丝","LatestUpdateDateTotal":"2023-07-13"}],"LatestUpdateDate":"2023-07-13"},{"TimeType":0,"MeetCondition":true,"DataType":16,"Details":[{"MeetCondition":true,"TotalCount":0,"TimeAfterPublish":750,"DataTime":1688659200,"DataType":16,"TotalCountText":"视频发布数","LatestUpdateDateTotal":"2023-07-13"},{"MeetCondition":true,"TotalCount":0,"TimeAfterPublish":751,"DataTime":1688745600,"DataType":16,"TotalCountText":"视频发布数","LatestUpdateDateTotal":"2023-07-13"},{"MeetCondition":true,"TotalCount":0,"TimeAfterPublish":752,"DataTime":1688832000,"DataType":16,"TotalCountText":"视频发布数","LatestUpdateDateTotal":"2023-07-13"},{"MeetCondition":true,"TotalCount":0,"TimeAfterPublish":753,"DataTime":1688918400,"DataType":16,"TotalCountText":"视频发布数","LatestUpdateDateTotal":"2023-07-13"},{"MeetCondition":true,"TotalCount":0,"TimeAfterPublish":754,"DataTime":1689004800,"DataType":16,"TotalCountText":"视频发布数","LatestUpdateDateTotal":"2023-07-13"},{"MeetCondition":true,"TotalCount":0,"TimeAfterPublish":755,"DataTime":1689091200,"DataType":16,"TotalCountText":"视频发布数","LatestUpdateDateTotal":"2023-07-13"},{"MeetCondition":true,"TotalCount":0,"TimeAfterPublish":756,"DataTime":1689177600,"DataType":16,"TotalCountText":"视频发布数","LatestUpdateDateTotal":"2023-07-13"}],"LatestUpdateDate":"2023-07-13"}],"BaseResp":{"StatusMessage":"SUCCESS","StatusCode":0},"dates":["2023-07-07","2023-07-08","2023-07-09","2023-07-10","2023-07-11","2023-07-12","2023-07-13"],"data":[0,0,0,0,0,0,0],"newGroupCntList":[0,0,0,0,0,0,0],"fansData":[null,null,null,null,null,null,null],"loyalFansData":[null,null,null,null,null,null,null],"metricName":{"TotalCountText":"净增粉丝","FansCountText":"","LoyalFansCountText":""},"accountCreateDate":"2021-06-17"},"data_trend_v2_6":{"AccountCreateTime":1623940717,"IsShowLoyal":false,"IsNewLoyal":false,"CreatorDataTrends":[{"TimeType":0,"MeetCondition":true,"DataType":6,"Details":[{"MeetCondition":true,"TotalCount":0,"TimeAfterPublish":750,"DataTime":1688659200,"DataType":6,"TotalCountText":"粉丝量","LatestUpdateDateTotal":"2023-07-13"},{"MeetCondition":true,"TotalCount":0,"TimeAfterPublish":751,"DataTime":1688745600,"DataType":6,"TotalCountText":"粉丝量","LatestUpdateDateTotal":"2023-07-13"},{"MeetCondition":true,"TotalCount":10,"TimeAfterPublish":752,"DataTime":1688832000,"DataType":6,"TotalCountText":"粉丝量","LatestUpdateDateTotal":"2023-07-13"},{"MeetCondition":true,"TotalCount":0,"TimeAfterPublish":753,"DataTime":1688918400,"DataType":6,"TotalCountText":"粉丝量","LatestUpdateDateTotal":"2023-07-13"},{"MeetCondition":true,"TotalCount":30,"TimeAfterPublish":754,"DataTime":1689004800,"DataType":6,"TotalCountText":"粉丝量","LatestUpdateDateTotal":"2023-07-13"},{"MeetCondition":true,"TotalCount":0,"TimeAfterPublish":755,"DataTime":1689091200,"DataType":6,"TotalCountText":"粉丝量","LatestUpdateDateTotal":"2023-07-13"},{"MeetCondition":true,"TotalCount":0,"TimeAfterPublish":756,"DataTime":1689177600,"DataType":6,"TotalCountText":"粉丝量","LatestUpdateDateTotal":"2023-07-13"}],"LatestUpdateDate":"2023-07-13"},{"TimeType":0,"MeetCondition":true,"DataType":16,"Details":[{"MeetCondition":true,"TotalCount":40,"TimeAfterPublish":750,"DataTime":1688659200,"DataType":16,"TotalCountText":"视频发布数","LatestUpdateDateTotal":"2023-07-13"},{"MeetCondition":true,"TotalCount":0,"TimeAfterPublish":751,"DataTime":1688745600,"DataType":16,"TotalCountText":"视频发布数","LatestUpdateDateTotal":"2023-07-13"},{"MeetCondition":true,"TotalCount":20,"TimeAfterPublish":752,"DataTime":1688832000,"DataType":16,"TotalCountText":"视频发布数","LatestUpdateDateTotal":"2023-07-13"},{"MeetCondition":true,"TotalCount":0,"TimeAfterPublish":753,"DataTime":1688918400,"DataType":16,"TotalCountText":"视频发布数","LatestUpdateDateTotal":"2023-07-13"},{"MeetCondition":true,"TotalCount":0,"TimeAfterPublish":754,"DataTime":1689004800,"DataType":16,"TotalCountText":"视频发布数","LatestUpdateDateTotal":"2023-07-13"},{"MeetCondition":true,"TotalCount":0,"TimeAfterPublish":755,"DataTime":1689091200,"DataType":16,"TotalCountText":"视频发布数","LatestUpdateDateTotal":"2023-07-13"},{"MeetCondition":true,"TotalCount":0,"TimeAfterPublish":756,"DataTime":1689177600,"DataType":16,"TotalCountText":"视频发布数","LatestUpdateDateTotal":"2023-07-13"}],"LatestUpdateDate":"2023-07-13"}],"BaseResp":{"StatusMessage":"SUCCESS","StatusCode":0},"dates":["2023-07-07","2023-07-08","2023-07-09","2023-07-10","2023-07-11","2023-07-12","2023-07-13"],"data":[0,0,0,0,0,0,0],"newGroupCntList":[0,0,0,0,0,0,0],"fansData":[null,null,null,null,null,null,null],"loyalFansData":[null,null,null,null,null,null,null],"metricName":{"TotalCountText":"粉丝量","FansCountText":"","LoyalFansCountText":""},"accountCreateDate":"2021-06-17"},"data_trend_v2_15":{"AccountCreateTime":1623940717,"IsShowLoyal":false,"IsNewLoyal":false,"CreatorDataTrends":[{"TimeType":0,"MeetCondition":true,"DataType":15,"Details":[{"MeetCondition":true,"TotalCount":110,"TimeAfterPublish":750,"DataTime":1688659200,"DataType":15,"TotalCountText":"取消关注","LatestUpdateDateTotal":"2023-07-13"},{"MeetCondition":true,"TotalCount":-10,"TimeAfterPublish":751,"DataTime":1688745600,"DataType":15,"TotalCountText":"取消关注","LatestUpdateDateTotal":"2023-07-13"},{"MeetCondition":true,"TotalCount":0,"TimeAfterPublish":752,"DataTime":1688832000,"DataType":15,"TotalCountText":"取消关注","LatestUpdateDateTotal":"2023-07-13"},{"MeetCondition":true,"TotalCount":-20,"TimeAfterPublish":753,"DataTime":1688918400,"DataType":15,"TotalCountText":"取消关注","LatestUpdateDateTotal":"2023-07-13"},{"MeetCondition":true,"TotalCount":0,"TimeAfterPublish":754,"DataTime":1689004800,"DataType":15,"TotalCountText":"取消关注","LatestUpdateDateTotal":"2023-07-13"},{"MeetCondition":true,"TotalCount":0,"TimeAfterPublish":755,"DataTime":1689091200,"DataType":15,"TotalCountText":"取消关注","LatestUpdateDateTotal":"2023-07-13"},{"MeetCondition":true,"TotalCount":0,"TimeAfterPublish":756,"DataTime":1689177600,"DataType":15,"TotalCountText":"取消关注","LatestUpdateDateTotal":"2023-07-13"}],"LatestUpdateDate":"2023-07-13"},{"TimeType":0,"MeetCondition":true,"DataType":16,"Details":[{"MeetCondition":true,"TotalCount":0,"TimeAfterPublish":750,"DataTime":1688659200,"DataType":16,"TotalCountText":"视频发布数","LatestUpdateDateTotal":"2023-07-13"},{"MeetCondition":true,"TotalCount":0,"TimeAfterPublish":751,"DataTime":1688745600,"DataType":16,"TotalCountText":"视频发布数","LatestUpdateDateTotal":"2023-07-13"},{"MeetCondition":true,"TotalCount":0,"TimeAfterPublish":752,"DataTime":1688832000,"DataType":16,"TotalCountText":"视频发布数","LatestUpdateDateTotal":"2023-07-13"},{"MeetCondition":true,"TotalCount":0,"TimeAfterPublish":753,"DataTime":1688918400,"DataType":16,"TotalCountText":"视频发布数","LatestUpdateDateTotal":"2023-07-13"},{"MeetCondition":true,"TotalCount":0,"TimeAfterPublish":754,"DataTime":1689004800,"DataType":16,"TotalCountText":"视频发布数","LatestUpdateDateTotal":"2023-07-13"},{"MeetCondition":true,"TotalCount":0,"TimeAfterPublish":755,"DataTime":1689091200,"DataType":16,"TotalCountText":"视频发布数","LatestUpdateDateTotal":"2023-07-13"},{"MeetCondition":true,"TotalCount":0,"TimeAfterPublish":756,"DataTime":1689177600,"DataType":16,"TotalCountText":"视频发布数","LatestUpdateDateTotal":"2023-07-13"}],"LatestUpdateDate":"2023-07-13"}],"BaseResp":{"StatusMessage":"SUCCESS","StatusCode":0},"dates":["2023-07-07","2023-07-08","2023-07-09","2023-07-10","2023-07-11","2023-07-12","2023-07-13"],"data":[0,0,0,0,0,0,0],"newGroupCntList":[0,0,0,0,0,0,0],"fansData":[null,null,null,null,null,null,null],"loyalFansData":[null,null,null,null,null,null,null],"metricName":{"TotalCountText":"取消关注","FansCountText":"","LoyalFansCountText":""},"accountCreateDate":"2021-06-17"},"data_trend_v2_3":{"AccountCreateTime":1623940717,"IsShowLoyal":false,"IsNewLoyal":false,"CreatorDataTrends":[{"TimeType":0,"MeetCondition":true,"DataType":3,"Details":[{"MeetCondition":true,"TotalCount":0,"FansCount":0,"TimeAfterPublish":750,"DataTime":1688659200,"DataType":3,"TotalCountText":"总评论量","LatestUpdateDateTotal":"2023-07-13","FansCountText":"粉丝评论量","LatestUpdateDateFans":"2023-07-13"},{"MeetCondition":true,"TotalCount":0,"FansCount":0,"TimeAfterPublish":751,"DataTime":1688745600,"DataType":3,"TotalCountText":"总评论量","LatestUpdateDateTotal":"2023-07-13","FansCountText":"粉丝评论量","LatestUpdateDateFans":"2023-07-13"},{"MeetCondition":true,"TotalCount":0,"FansCount":0,"TimeAfterPublish":752,"DataTime":1688832000,"DataType":3,"TotalCountText":"总评论量","LatestUpdateDateTotal":"2023-07-13","FansCountText":"粉丝评论量","LatestUpdateDateFans":"2023-07-13"},{"MeetCondition":true,"TotalCount":0,"FansCount":0,"TimeAfterPublish":753,"DataTime":1688918400,"DataType":3,"TotalCountText":"总评论量","LatestUpdateDateTotal":"2023-07-13","FansCountText":"粉丝评论量","LatestUpdateDateFans":"2023-07-13"},{"MeetCondition":true,"TotalCount":0,"FansCount":0,"TimeAfterPublish":754,"DataTime":1689004800,"DataType":3,"TotalCountText":"总评论量","LatestUpdateDateTotal":"2023-07-13","FansCountText":"粉丝评论量","LatestUpdateDateFans":"2023-07-13"},{"MeetCondition":true,"TotalCount":0,"FansCount":0,"TimeAfterPublish":755,"DataTime":1689091200,"DataType":3,"TotalCountText":"总评论量","LatestUpdateDateTotal":"2023-07-13","FansCountText":"粉丝评论量","LatestUpdateDateFans":"2023-07-13"},{"MeetCondition":true,"TotalCount":0,"FansCount":0,"TimeAfterPublish":756,"DataTime":1689177600,"DataType":3,"TotalCountText":"总评论量","LatestUpdateDateTotal":"2023-07-13","FansCountText":"粉丝评论量","LatestUpdateDateFans":"2023-07-13"}],"LatestUpdateDate":"2023-07-13"},{"TimeType":0,"MeetCondition":true,"DataType":16,"Details":[{"MeetCondition":true,"TotalCount":0,"TimeAfterPublish":750,"DataTime":1688659200,"DataType":16,"TotalCountText":"视频发布数","LatestUpdateDateTotal":"2023-07-13"},{"MeetCondition":true,"TotalCount":0,"TimeAfterPublish":751,"DataTime":1688745600,"DataType":16,"TotalCountText":"视频发布数","LatestUpdateDateTotal":"2023-07-13"},{"MeetCondition":true,"TotalCount":0,"TimeAfterPublish":752,"DataTime":1688832000,"DataType":16,"TotalCountText":"视频发布数","LatestUpdateDateTotal":"2023-07-13"},{"MeetCondition":true,"TotalCount":0,"TimeAfterPublish":753,"DataTime":1688918400,"DataType":16,"TotalCountText":"视频发布数","LatestUpdateDateTotal":"2023-07-13"},{"MeetCondition":true,"TotalCount":0,"TimeAfterPublish":754,"DataTime":1689004800,"DataType":16,"TotalCountText":"视频发布数","LatestUpdateDateTotal":"2023-07-13"},{"MeetCondition":true,"TotalCount":0,"TimeAfterPublish":755,"DataTime":1689091200,"DataType":16,"TotalCountText":"视频发布数","LatestUpdateDateTotal":"2023-07-13"},{"MeetCondition":true,"TotalCount":0,"TimeAfterPublish":756,"DataTime":1689177600,"DataType":16,"TotalCountText":"视频发布数","LatestUpdateDateTotal":"2023-07-13"}],"LatestUpdateDate":"2023-07-13"}],"BaseResp":{"StatusMessage":"SUCCESS","StatusCode":0},"dates":["2023-07-07","2023-07-08","2023-07-09","2023-07-10","2023-07-11","2023-07-12","2023-07-13"],"data":[0,0,0,0,0,0,0],"newGroupCntList":[0,0,0,0,0,0,0],"fansData":[0,0,0,0,0,0,0],"loyalFansData":[null,null,null,null,null,null,null],"metricName":{"TotalCountText":"总评论量","FansCountText":"粉丝评论量","LoyalFansCountText":""},"accountCreateDate":"2021-06-17"},"userInfo":{"_user_name":"奶芙芙沙琪玛","_user_id":2708815626640615,"_user_id_new":"none","_user_phone_new":"none","_user_name_new":"none","total_play":0}}],"user_id":1426,"unique":2708815626640615}';


            }
            if($i > 1) {
                exit();
            }
            if (empty($redisData)) {
                sleep(1);
                continue;
            }
            $redisData = is_array($redisData) ? $redisData : json_decode($redisData, true);

            $userId = $redisData['user_id'];

            // 过滤重复数据
            /*$key = 'CallbackUser_' . $userId;
            if(!$callBackService->repeatFilter($redisData, $key, RedisKey::REDIS_DATABASES_THREE)) {
                return $this->success();
            }*/

            // 用户是否存在于平台
            if (!$user = $userService->checkUserIsExistById($userId, true)) {
                continue;
            }

            try {
                // 解析
                $data = OutPlatformFactory::register($user->platform)->formatUserData($user, $redisData);

                // 入库
                $callBackService::haddleCallBackUser($user->id, $data);
            } catch (OutPlatformException $e) {
                LogHelper::add('userJobParamError/', '当前数据:' . json_encode($redisData));
            } catch (\ErrorException $e) {
                LogHelper::add('userJobFormatError/',
                    '当前数据:' . json_encode($redisData) . '当前错误信息:' . $e->getMessage() .
                    '当前文件:' . $e->getFile() . '当前错误所在行:'. $e->getLine()
                );
                wxNotice('用户解析Format类型异常', getErrorTemplateMessage($e));
            } catch (\Throwable $e) {
                LogHelper::add('userJobHaddleError/', getErrorTemplateMessage($e));
                wxNotice('用户解析异常', getErrorTemplateMessage($e));
            }
        }
    }

}
