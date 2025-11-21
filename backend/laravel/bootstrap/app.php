<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Auth\AuthenticationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // ルート定義で middleware('auth') が呼ばれた場合、vendor 版ではなく app 版を実行
        $middleware->alias([
            'auth' => \App\Http\Middleware\Authenticate::class,
        ]);

        // すべてのリクエストに一意の Request ID を付与し、ログに紐づける
        $middleware->append(\App\Http\Middleware\RequestIdMiddleware::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (Throwable $e, $request) {
            // APIルートで発生した AuthenticationException のみをキャッチ
            if ($request->is('api/*') && $e instanceof AuthenticationException) {
                return response()->json([
                    'message' => '認証に失敗しました。トークンが無効または期限切れです。再度ログインしてください。',
                ], 401);
            }
            // それ以外の例外は、Laravel標準のハンドリングに任せる
            return null;
        });
    })
    ->create();
