<?php
namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class DeleteOldLogFiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'delete:log';

    protected $day = 14;

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '删除老日志，只保存14天';

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
     * @return int
     */
    public function handle()
    {
        // 获取日志文件存放的目录路径
        $logDirectory = storage_path('logs');

        // 清除14天前的老日志
        $this->deleteOldLogFiles($logDirectory, $this->day);

        // 清除laravel-worker.log队列日志
        $this->clearLaravelWorker();
    }


    /**
     * 根据目录及时间递归删除文件
     *
     * @param $directory
     * @param $daysToKeep
     * @return void
     */
    public function deleteOldLogFiles($directory, $daysToKeep) {
        if (!File::isDirectory($directory)) {
            return;
        }

        $files = File::allFiles($directory);
        foreach ($files as $file) {
            $fileName = $file->getFilename();

            // 从文件名中提取日期部分
            $pattern = '/(\d{4}-\d{2}-\d{2})/';
            preg_match($pattern, $fileName, $matches);

            if (!empty($matches[1])) {
                $dateString = $matches[1];

                // 解析日期
                $fileDate       = Carbon::createFromFormat('Y-m-d', $dateString)->startOfDay();
                $daysDifference = Carbon::now()->diffInDays($fileDate);

                if ($daysDifference > $daysToKeep) {
                    File::delete($file->getPathname());
                }
            }
        }
    }

    /**
     * 清除laravel-worker.log
     *
     * @return void
     */
    public function clearLaravelWorker() {
        $file = app_path('../laravel-worker.log');
        if($file) {
            file_put_contents($file, '');
        }
    }
}
