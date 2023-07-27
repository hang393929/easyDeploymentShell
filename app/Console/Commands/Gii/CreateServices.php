<?php
/**
 * 示例：php artisan create:services user empty --no_dump
 *
 * user:服务名称 创建完：UserService
 * directory: 示例为emtpy为空,表示不生成文件目录
 * no_dump: 是否需要关闭自动加载  不写则默认开启，写了则关闭，本例中是关闭
 *
 */

namespace App\Console\Commands\Gii;

use Illuminate\Support\Str;
use App\Console\Commands\Gii\Bases\BaseCreate;

class CreateServices extends BaseCreate
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'create:services {name : The name of model} {directory : Place the hierarchical directory here} {--no_dump}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description   = '创建Services, name对应Services名称，directory：存放在哪个目录文件下';
    protected $type          = 'php';
    protected $tpl           = 'php/services';
    protected $baseNamespace = 'App\Http\Services';

    protected function getOutputPath()
    {
        $table     = $this->getTable();
        $directory = $this->getDirectory();
        if($directory) {
            $directory .= '/';
        }

        $this->outputPath = app_path(
            'Http/Services/' . $directory . Str::studly($this->getClassName($table)) . 'Service'
        );
    }

    public function getTable()
    {
        return $this->argument('name');
    }

    public function getDirectory()
    {
        return $this->argument('directory') == 'empty' ? '' : ucfirst((string)$this->argument('directory'));
    }

    /**
     * 创建repository
     */
    protected function readyDatas()
    {
        $name = $this->getTable();

        $data['php']       = '<?php'; //模板代码
        $data['namespace'] = $this->getDirectory() ? $this->baseNamespace . '\\' . $this->getDirectory() : $this->baseNamespace;
        $data['name']      = Str::studly($this->getClassName($name)) . 'Service'; //模型名称

        $this->datas = $data;
    }
}
