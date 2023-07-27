{!! $php !!}

/**
* {{$name}}Controller 控制器
*/
namespace {{$namespace}};

@if ($service)
use App\Http\Services\{{$service}};
@endif

class {{$name}}Controller extends ApiController
{

@if ($service)
    /**
    * @var {{$service}}
    */
    public ${{$smallService}};

    public function __construct({{$service}} ${{$smallService}}) {
        $this->{{$smallService}} = ${{$smallService}};
    }
@endif



}
