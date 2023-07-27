<?php

namespace App\Providers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Events\QueryExecuted;

class ListenPdoServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {

    }

    public function boot()
    {
        DB::listen(function (QueryExecuted $executed) {
            $executed->sql = ltrim($executed->sql);
            if (stripos($executed->sql, 'insert') === 0
                || stripos($executed->sql, 'update') === 0
                || stripos($executed->sql, 'delete') === 0
            ) {
                DB::connection($executed->connectionName)->setReadPdo(null);//清空从连接,会自动使用主连接
            }
        });
    }
}
