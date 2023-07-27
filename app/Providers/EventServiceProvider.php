<?php
namespace App\Providers;

use Illuminate\Support\Facades\Event;
use App\Events\OldFensInUserCallBack;
use Illuminate\Auth\Events\Registered;
use App\Listeners\OldFensInUserCallBackListener;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        // 基于用户数据回调兼容老粉丝数据
        OldFensInUserCallBack::class => [
            OldFensInUserCallBackListener::class,
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
