<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class RequestIdMiddleware
{
    /**
     * リクエストごとに一意の Request ID を付与し、
     * ログのコンテキストとレスポンスヘッダへ設定するミドルウェア。
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure(\Illuminate\Http\Request):(\Symfony\Component\HttpFoundation\Response) $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 既に X-Request-ID が来ていればそれを利用（CloudFront/ALB対策）
        $requestId = $request->header('X-Request-ID') ?? (string) Str::uuid();

        // ログに自動付与
        Log::withContext([
            'リクエストID' => $requestId,
        ]);

        // レスポンスヘッダにも付けて返す（外部でエラーが発生した際に、対象のログを特定しやすくするため）
        $response = $next($request);
        $response->headers->set('X-Request-ID', $requestId);

        return $response;
    }
}
