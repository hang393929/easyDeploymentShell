<?php
/**
 * 效验登录信息
 */

namespace App\Http\Middleware;

use Closure;
use App\Models\SyncUser;
use Illuminate\Support\Facades\Redis;
use App\Exceptions\AuthorityException;

class CheckAuthority
{
    public function handle($request, Closure $next)
    {
        $path = strtolower($request->path());
        if (in_array($path, config('other.checkAuthorityWhite'))) {
            return $next($request);
        }

        $token = $request->header('Authorization');
        if (!$token) {
            return $this->responseLoginRequired();
        }

        try {
            $userId = Redis::get($token);
            if (!$userId || $userId == '999999999999') {
                throw new AuthorityException('登录失效');
            }

            $syncUser = SyncUser::where('id', $userId)->first();
            if (!$syncUser) {
                throw new AuthorityException('未查询到该用户信息');
            }

            $expire = 60 * 60 * 8;
            if ($syncUser->login_time && ($_SERVER['REQUEST_TIME'] - $syncUser->login_time) > $expire) {
                throw new AuthorityException('登录过期');
            }

            if (
                SyncUser::where('id',$userId)->update(['login_time'=>time()])
                &&
                !in_array($path, config('other.checkAuthorityNotSetToken'))
            ){
                Redis::set($token, $userId,"ex",$expire);
            }

            // 刷新版本
            if($path == 'checklogin') {
                SyncUser::where('id',$userId)->update(['version'=>$request->header('use-version') ?? '0']);
            }

        } catch (AuthorityException $e) {
            $this->responseLoginRequired();
        } catch (\Throwable $e) {
            $this->responseLoginRequired(true);
        }

        return $next($request);
    }

    /**
     * 统一返回
     *
     * @param bool $ext
     * @return false|string
     */
    public function responseLoginRequired(bool $ext = false)
    {
        return json_encode(['code' => 502, 'message' => $ext ? '服务异常' : '请登录']);
    }
}
