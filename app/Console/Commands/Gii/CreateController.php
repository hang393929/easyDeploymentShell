<?php
/**
 * 示例：php artisan create:controller user empty --no_dump --service
 * users:控制器 UserController
 * empty:目录名称
 * no_dump： composer dumpautoload 自动加载类文件  不传则为false,示例中为true
 * service ：是否创建service  不传则为false,示例中为true
 *
 */

namespace App\Console\Commands\Gii;

use Illuminate\Support\Str;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use App\Console\Commands\Gii\Bases\BaseCreate;

class CreateController extends BaseCreate
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'create:controller {name} {directory : Place the hierarchical directory here} {--no_dump} {--service} {--scoure}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '创建控制器';

    protected $type          = 'php';
    protected $tpl           = 'php/controller';
    protected $baseNamespace = 'App\Http\Controllers';
    protected $scoure        = 'controller';

    public function getDirectory()
    {
        return $this->argument('directory') == 'empty' ? '' : ucfirst((string)$this->argument('directory'));
    }

    public function getTable()
    {
        return $this->argument('name');
    }

    protected function getOutputPath()
    {
        $directory = $this->getDirectory();
        if ($directory) {
            $directory .= '/';
        }

        $this->outputPath = app_path(
            'Http/Controllers/' . $directory . Str::studly($this->argument('name')) . 'Controller'
        );
    }


    /**
     * 创建控制器
     */
    protected function readyDatas()
    {
        $name                 = underscoreToCamel($this->getTable());
        $data['php']          = '<?php'; //模板代码
        $data['namespace']    = $this->getDirectory() ? $this->baseNamespace . '\\' . $this->getDirectory() : $this->baseNamespace;
        $data['service']      = $this->hasOption('service') && $this->option('service') ? $name . 'Service' : '';
        $data['name']         = $name;
        $data['smallService'] = $data['service'] ? Str::camel($data['service']) : '';

        $this->datas = $data;

        if($this->hasOption('scoure') && $this->option('scoure')) {
            $this->scoure = '';
        }
    }
}
