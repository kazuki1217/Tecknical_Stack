<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Auth\Factory as Auth;
use Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests;

class Authenticate implements AuthenticatesRequests
{
    protected $auth;

    public function __construct(Auth $auth)
    {
        $this->auth = $auth;
    }

    public static function using($guard, ...$others)
    {
        return static::class . ':' . implode(',', [$guard, ...$others]);
    }

    public function handle($request, Closure $next, ...$guards)
    {
        $this->authenticate($request, $guards);
        return $next($request);
    }

    protected function authenticate($request, array $guards)
    {
        if (empty($guards)) {
            $guards = [null];
        }

        foreach ($guards as $guard) {
            if ($this->auth->guard($guard)->check()) {
                return $this->auth->shouldUse($guard);
            }
        }

        // API認証エラーが発生した際、laravel のデフォルトの動作は "login" ルートへリダイレクトする仕組みになっている。
        // 今回は web.php を使用せず、すべてのリクエストを API 経由で処理するため、リダイレクト処理を無効化して例外を直接投げるよう修正
        // 
        // 関連ファイル
        // ・vendor/laravel/framework/src/Illuminate/Auth/Middleware/Authenticate.php 
        // ・vendor/laravel/framework/src/Illuminate/Foundation/Exceptions/Handler.php
        throw new AuthenticationException('Unauthenticated.', $guards, null);
    }
}
