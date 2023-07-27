<?php
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| callback Routes
|--------------------------------------------------------------------------
|
| This path file only saves data for callback related logic processing
|
*/

/**
 * 回调路由组
 */
Route::namespace('App\Http\Controllers')->group(function ($route) {
    //$route->post('callbackData', 'CallBackController@callBackData');  // 粉丝老接口解析
    // 权限效验
    Route::middleware(['check.authority'])->group(function ($route){
        $route->post('callbackUser', 'CallBackController@user');          // 用户解析
        $route->post('callbackVideo', 'CallBackController@video');        // 视频解析
        $route->post('callbackFens', 'CallBackController@fens');            // 粉丝解析
        $route->post('callbackIncome', 'CallBackController@income');      // income老接口解析
        $route->post('callbackData', 'CallBackController@callBackData');  // 粉丝老接口解析
        $route->post('callbackVideoDetail', 'CallBackController@videoDetail'); // 视频详情析
    });
});


