<?php
/**
 * 示例：php artisan create:model users mysql --no_dump --repository
 * users:表
 * connection:示例链接的databases.php 中mysql的库
 * no_dump： composer dumpautoload 自动加载类文件  不传则为false,示例中为true
 * repository ：是否创建Repository  不传则为false,示例中为true
 *
 */

namespace App\Console\Commands\Gii;

use Illuminate\Support\Str;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use App\Console\Commands\Gii\Bases\BaseCreate;

class CreateModel extends BaseCreate
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'create:model {table : The name of model} {connection} {--no_dump} {--repository}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description   = '创建model及Repository';
    protected $type          = 'php';
    protected $tpl           = 'php/model';
    protected $baseNamespace = 'App\Models';
    protected $repository    = true;
    protected $scoure        = 'model';

    protected function getOutputPath()
    {
        $table            = $this->getTable();
        $this->outputPath = app_path('Models/' . Str::studly($this->getClassName($table)));
    }

    public function getTable()
    {
        return $this->argument('table');
    }

    /**
     * 获取数据表字段信息
     * param $table
     * 返回: mixed
     */
    public function getTableInfo($table, $connection)
    {
        $prefix = config('database.connections.' . $connection . '.prefix');
        $trueTable = $prefix . $table;
        //数据表备注信息
        $data['comment'] = DB::connection($connection)->getSchemaBuilder()->getColumnListing($trueTable);

        //字段信息
        $data['table_fields'] = collect(DB::connection($connection)->select('show full COLUMNS from `' . $trueTable . '`'))
            ->map(function ($item) {
                $comment         = explode('@', $item->Comment);
                $item->validator = Arr::get($comment, '1', ''); //字段验证
                $comment         = explode('$', $comment[0]);
                $item->showType  = in_array($item->Field, ['created_at', 'updated_at']) ? 'time' : Arr::get($comment, '1', ''); //字段显示类型
                $item->showType  = in_array($item->Field, ['deleted_at', 'left_margin', 'right_margin', 'level', 'remember_token']) ? 'hidden' : $item->showType;
                $comment         = explode(':', $comment[0]);
                $info            = ['created_at' => '创建时间', 'updated_at' => '修改时间', 'deleted_at' => '删除时间'];
                $item->info      = isset($info[$item->Field]) ? $info[$item->Field] : $comment[0]; //字段说明
                $item->info      = $item->info ?: $item->Field;
                $comment         = explode(',', Arr::get($comment, '1', ''));
                $item->values    = collect($comment)->map(function ($item) {
                    return explode('-', $item);
                })->pluck('1', '0')->filter(function ($item) {
                    return $item;
                })->toArray(); //字段值
                $item->showType  = (!$item->showType && $item->values) ? 'radio' : $item->showType;
                $item->showType  = !$item->showType ? 'text' : $item->showType;
                return collect($item)->toArray();
            })->toArray();

        return $data;
    }

    /**
     * 创建model
     */
    protected function readyDatas()
    {
        $name                    = $this->getTable();
        $data['php']             = '<?php'; //模板代码
        $data['table']           = $name;
        $data['namespace']       = $this->baseNamespace; //生成代码命名空间
        $data['name']            = Str::studly($this->getClassName($name)); //模型名称
        $data['connection']      = $this->argument('connection') ?: config('database.default');
        $data['tableInfo']       = $this->getTableInfo($this->argument('table'), $data['connection']); //数据表信息
        $table_fields            = collect($data['tableInfo']['table_fields']);
        $data['comment']         = $data['tableInfo']['comment'];
        $data['dates']           = $table_fields->filter(function ($item) {
            return $item['showType'] == 'time' || in_array($item['Field'], ['deleted_at', 'created_at', 'updated_at']);
        })->pluck('Field')->implode("','");
        $data['dates']           = $data['dates'] ? "'" . $data['dates'] . "'" : '';
        //隐藏输出字段
        $data['delete'] = $table_fields->filter(function ($item) {
            return in_array($item['showType'], ['delete', 'password']) || in_array($item['Field'], ['deleted_at']);
        })->pluck('Field');
        //批量赋值字段
        $data['fillable'] = $table_fields->pluck('Field')->diff($data['delete']->merge([
            'created_at',
            'updated_at',
            'id'
        ])->all())->implode("',\n       '");
        $data['fillable'] = $data['fillable'] ? $this->formatShow("       '" . $data['fillable'] . "'") : '';
        $data['delete']   = $data['delete']->implode("','");
        $data['delete']   = $data['delete'] ? "'" . $data['delete'] . "'" : '';
        $data['fieldsShowMaps'] = $this->formatShow(collect($table_fields)->filter(function ($item) {
            return in_array($item['showType'], ['radio', 'checkbox', 'select', 'label', 'icheck-radio', 'icheck', 'icheck-checkbox']);
        })->keyBy('Field')->map(function ($item, $key) {
            $res = "        '" . $key . "'" . '=>[' . $this->formatShow(collect($item['values'])->map(function ($value, $key) {
                    return '            "' . $key . '"' . "=>'" . $value . "'";
                })->implode(",\n")) . '     ]';
            return $res;
        })->implode(",\n"));
        $data['checkboxs']      = collect($table_fields)->filter(function ($item) {
            return in_array($item['showType'], ['checkbox']);
        });
        $data['passwords']      = collect($table_fields)->filter(function ($item) {
            return in_array($item['showType'], ['password']);
        });
        $data['jsons']          = collect($table_fields)->filter(function ($item) {
            return in_array($item['showType'], ['json']);
        });
        $data['fieldsDefault']  = $this->formatShow(collect($table_fields)->filter(function ($item) {
            return !in_array($item['Field'], ['id', 'deleted_at', 'updated_at', 'created_at']) && !is_null($item['Default']);
        })->map(function ($item) {
            if (is_bool($item['Default'])) {
                $value = $item['Default'] ? 'true' : 'false';
            } elseif (is_numeric($item['Default']) && $item['showType'] == 'checkbox') {
                $field = $item['values'];
                unset($field[0]);
                $value = '[' . implode(',', multiple($item['Default'], $field)) . ']';
            } elseif (is_numeric($item['Default'])) {
                $value = $item['Default'];
            } else {
                $value = "'" . $item['Default'] . "'";
            };
            return "        '" . $item['Field'] . "' => " . $value;
        })->implode(",\n"));
        $data['fieldsName']     = $this->formatShow(collect($table_fields)->map(function ($item) {
            return "        '" . $item['Field'] . "' => '" . $item['info'] . "'";
        })->implode(",\n"));

        $this->datas            = $data;
    }

    protected function formatShow($str)
    {
        return $str ? "\n" . $str . "\n    " : '';
    }
}
