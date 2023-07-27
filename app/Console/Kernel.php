<?php
namespace App\Console;

use App\Console\Commands\DeleteOldLogFiles;
use Illuminate\Console\Scheduling\Schedule;
use App\Console\Commands\MonitorSystemMemory;
use App\Console\Commands\CallBack\CallBackFens;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        CallBackFens::class,
        DeleteOldLogFiles::class,
        MonitorSystemMemory::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')->hourly();
        $schedule->command('observe:system')->everyThirtyMinutes(); // 系统健康检查 30分钟一次
        $schedule->command('delete:log')->daily(); // 清除日志  每天一次
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
