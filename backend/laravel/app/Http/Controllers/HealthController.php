<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class HealthController extends Controller
{
    /**
     * Laravelアプリケーションが稼働し、HTTPリクエストに応答できることを確認する。
     */
    public function live(): JsonResponse
    {
        return response()->json(['status' => 'ok'], 200);
    }

    /**
     * LaravelアプリケーションからMySQLへ接続し、クエリを実行できることを確認する。
     */
    public function ready(): JsonResponse
    {
        try {
            // 接続確認だけを目的とし、監視のたびに業務テーブルへ負荷をかけないようにする。
            DB::selectOne('SELECT 1 AS health_check');

            return response()->json(['status' => 'ok'], 200);
        } catch (Throwable $e) {
            Log::error('[Readinessチェック] データベースへの接続確認に失敗しました。', [
                '接続名' => config('database.default'),
                'エラー内容' => $e->getMessage(),
                'ファイル名' => $e->getFile(),
                '行番号' => $e->getLine(),
            ]);

            return response()->json(['status' => 'unavailable'], 503);
        }
    }
}
