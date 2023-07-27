<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class MonitorSystemMemory extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'observe:system';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '系统状态巡检';

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
        $this->monitorSystemParameters();
    }

    public function monitorSystemParameters()
    {
        // 获取系统当前内存大小、已占用大小、剩余可用大小
        $free            = shell_exec('free -b');
        $free            = (string)trim($free);
        $freeArr         = explode("\n", $free);
        $mem             = explode(" ", $freeArr[1]);
        $mem             = array_filter($mem);
        $mem             = array_merge($mem);
        $totalMemory     = (int)$mem[1];
        $usedMemory      = (int)$mem[2];
        $availableMemory = (int)$mem[6];

        // 获取磁盘总大小和剩余大小
        $df                 = shell_exec('df -B1 /');
        $df                 = (string)trim($df);
        $dfArr              = explode("\n", $df);
        $disk               = explode(" ", $dfArr[1]);
        $disk               = array_filter($disk);
        $disk               = array_merge($disk);
        $totalDiskSpace     = (int)$disk[1];
        $availableDiskSpace = (int)$disk[3];

        // 计算内存占用比
        $memoryUsageRatio = round(($usedMemory / $totalMemory) * 100, 2);

        // 获取当前进程数
        $processes = shell_exec('ps aux --no-heading | wc -l');
        $processes = (int)trim($processes);

        // 当前服务器IP
        $response  = Http::get('https://api.ipify.org');
        $public_ip = $response->body();

        // cpu占用率
        $cpu_load = $this->getCurrentCpuUsage();

        $reslut = [
            'total_memory'         => number_format((float)$totalMemory / 1073741824, 2),  // 系统当前内存总大小（以字节为单位）
            'used_memory'          => number_format((float)$usedMemory / 1073741824, 2),   // 系统当前已使用的内存大小（以字节为单位）
            'available_memory'     => number_format((float)$availableMemory / 1073741824, 2), // 系统当前剩余可用的内存大小（以字节为单位）
            'total_disk_space'     => number_format((float)$totalDiskSpace / 1073741824, 2),   // 系统磁盘总大小（以字节为单位）
            'available_disk_space' => number_format((float)$availableDiskSpace / 1073741824, 2), // 系统当前剩余可用的磁盘空间大小（以字节为单位）
            'memory_usage_ratio'   => $memoryUsageRatio,             // 系统当前内存使用比例（以百分比表示，保留两位小数）
            'processes'            => $processes,                    // 当前运行的进程数
            'cpu_usage_ration'     => $cpu_load,     // cpu使用率
            'public_ip'            => $public_ip                     // 公网IP
        ];

        if($reslut['available_memory'] <= 2 || $reslut['available_disk_space'] <= 10 || $reslut['memory_usage_ratio'] >= 80) {
            $msg = $this->wxNoticTemplate($reslut);
            wxNotice('系统巡检报警', [], $msg);
        }
    }

    private function wxNoticTemplate($data) {
        // 内存维度
        if($data['available_memory'] <= 2 && $data['available_memory'] >= 1) {
            $grade = '三级';
        } else if($data['available_memory'] < 1){
            $grade = '一级';
        }
        // 磁盘维度
        if(!isset($grade)) {
            if($data['available_disk_space'] <= 10 && $data['available_disk_space'] >= 5) {
                $grade = '四级';
            } else if($data['available_disk_space'] < 5 && $data['available_disk_space'] >= 2) {
                $grade = '三级';
            } else if($data['available_disk_space'] <= 1) {
                $grade = '一级';
            }
        }
        // 内存占用维度
        if(!isset($grade)) {
            if($data['memory_usage_ratio'] >= 80) {
                $grade = '三级';
            } else if($data['memory_usage_ratio'] >= 90) {
                $grade = '一级';
            }
        }
        // 数据库占用维度
        if(!isset($grade)) {
            $grade = '五级';
        }

        return <<<EOF
                当前告警级别: {$grade}
                Server IP: {$data['public_ip']}
                CPU占用: {$data['cpu_usage_ration']} % (仅供参考)
                系统当前内存总大小: {$data['total_memory']} GB
                系统当前已使用的内存大小: {$data['used_memory']} GB
                系统当前剩余可用的内存大小: {$data['available_memory']} GB
                系统磁盘总大小: {$data['total_disk_space']} GB
                系统当前剩余可用的磁盘空间大小: {$data['available_disk_space']} GB
                系统当前内存使用比例: {$data['memory_usage_ratio']}%
                当前运行的进程数: {$data['processes']}
            EOF;
    }


    /**
     * Linux操作系统自动获取CPU(内存)使用率
     */
    private function getCurrentCpuUsage() {
        // 获取当前CPU的负载信息
        $loadAvg = sys_getloadavg();
        $number = str_replace('.', '', $loadAvg[0]);
        $number = intval($number);
        // 获取当前CPU的逻辑核心数
        $cpuCores = shell_exec('nproc');

        // 计算CPU占用率
        return $number == 0 ? 0 : @round($number / ((int)$cpuCores * 100), 2);
    }

}
