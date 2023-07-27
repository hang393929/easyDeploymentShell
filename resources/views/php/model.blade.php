{!! $php !!}

/**
 * {{$table}}模型
 */
namespace {{$namespace}};

class {{$name}} extends BaseModel
{

    //数据表名称
    protected $table = '{{$table}}';

    //批量赋值白名单
    protected $fillable = [{!! $fillable !!}];

    //输出隐藏字段
    protected $hidden = [{!! $delete !!}];

    //日期字段
    protected $dates = [{!! $dates !!}];

    //字段值map
    protected $fieldsShowMaps = [{!! $fieldsShowMaps !!}];

    //字段默认值
    protected $fieldsDefault = [{!! $fieldsDefault !!}];

    //字段说明
    protected $fieldsName = [{!! $fieldsName !!}];


}
