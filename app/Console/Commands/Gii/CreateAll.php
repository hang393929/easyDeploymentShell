<?php
/**
 * 示例：php artisan create:all user --only=controller,services,repository,model --connection=mysql --no_dump
 * controller:控制器 UserController
 * services:服务层 UserService
 * repositor:仓储层 UserRepository
 * model: model层 UserModel
 * connection: 连接的库,示例使用的是config/databases.php 中的mysql配置
 * no_dump： composer dumpautoload 自动加载类文件  不传则为false,示例中为true
 *
 */

namespace App\Console\Commands\Gii;

use App\Console\Commands\Gii\Bases\BaseCommand;

class CreateAll extends BaseCommand
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'create:all {table : The name of model} {--only=} {--c|connection= : The database connection to use} {--no_dump}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '创建所有资源';

    protected function hasOnly($key)
    {
        $only = $this->option('only');
        if (!$only) {
            return true;
        }
        return in_array($key, explode(',', $only));
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $table      = $this->argument('table');
        $noDump     = $this->hasOption('no_dump') && $this->option('no_dump');
        $connection = $this->option('connection') ?: config('database.default');

        //生成模型
        if ($this->hasOnly('model')) {
            $this->call('create:model', [
                'table'      => $table,
                'connection' => $connection,
                '--no_dump'  => $noDump
            ]);
        }

        //生成控制器
        if ($this->hasOnly('controller')) {
            $this->call('create:controller', [
                'name'      => $table,
                'directory' => 'empty',
                '--no_dump' => $noDump,
                '--service' => true,
                '--scoure'  => true,
            ]);
        }

        //生成服务层
        if ($this->hasOnly('services')) {
            $this->call('create:services', [
                'name'      => $table,
                'directory' => 'empty',
                '--no_dump' => $noDump
            ]);
        }

        //生成仓储层
        if ($this->hasOnly('repository')) {
            $this->call('create:repository', [
                'table'     => $table,
                'directory' => 'empty',
                '--no_dump' => $noDump
            ]);
        }

        if (!($this->hasOption('no_dump') && $this->option('no_dump'))) {
            app('composer')->dumpAutoloads(); //自动加载文件
        }

    }

}
