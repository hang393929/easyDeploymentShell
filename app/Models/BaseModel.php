<?php
namespace App\Models;

use DateTimeInterface;
use App\Library\CachePage\CachePage;
use Illuminate\Database\Eloquent\Model;

class BaseModel extends Model
{
    // 主从库
    protected $connection = 'xzq';

    public static function boot()
    {
        parent::boot();

        // todo 当支持页面缓存时，将可以缓存的Model变化时间戳更新到redis上
        if (env('IS_CACHE_PAGES', false)) {
            // 更新redis上的模型修改时间戳
            static::saved(function ($model) {
                self::doChangedNotify($model, null);
            });
            static::created(function ($model) {
                self::doChangedNotify($model, '*');
            });
            static::updated(function ($model) {
                self::doChangedNotify($model, null);
            });
            static::deleted(function ($model) {
                self::doChangedNotify($model, '*');
            });
        }
    }

    public static function doChangedNotify($model, $specifyId = null) {
        $monitorModels = config('business.monitor_models');
        if ($monitorModels) {
            $clazz = get_called_class();
            $whitelist = $monitorModels['whitelist'] ?? [];
            $blacklist = $monitorModels['blacklist'] ?? [];
            $defaultMonitor = $monitorModels['default_monitor'] ?? false;
            if (in_array($clazz, $whitelist)) {
                $monitor = true;
            } elseif (in_array($clazz, $blacklist)) {
                $monitor = false;
            } else {
                $monitor = $defaultMonitor;
            }
            if ($specifyId) {
                $id = $specifyId;
            } else {
                $id = $model ? ($model->id ?? null) : null;
            }
            if ($monitor && $id) {
                CachePage::notifyChanged($clazz, $id);
            }
        }
    }

    public function serializeDate(DateTimeInterface $date)
    {
        return $date->format("Y-m-d H:i:s");
    }
}
