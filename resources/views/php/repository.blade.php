{!! $php !!}

/**
 * {{$name}}仓储
 */
namespace {{$namespace}};

use App\Models\{{$table}};
use App\Http\Repository\BaseRepository;

class {{$name}} extends BaseRepository
{
    /**
    * @var {{$table}} $model
    */

    protected $model;
    public function __construct({{$table}} $model)
    {
        parent::__construct($model);
    }


}
