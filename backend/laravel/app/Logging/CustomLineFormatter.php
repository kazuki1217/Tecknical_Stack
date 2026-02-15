<?php

namespace App\Logging;

use Illuminate\Log\Logger as IlluminateLogger;
use Illuminate\Support\Str;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\FormattableHandlerInterface;

class CustomLineFormatter
{
    /**
     * ログのフォーマットで、日時とログレベルの間に8文字のリクエストIDを表示するための設定
     */
    public function __invoke(IlluminateLogger $logger): void
    {
        // LaravelのLoggerから、実際にログ整形を担当するMonolog本体を取得する。
        $monolog = $logger->getLogger();

        // プロセス内で共通の8文字IDを1回だけ解決する。
        $requestId = $this->resolveRequestId();

        // formatter:
        // ログファイルに書き込む最終文字列のフォーマット。
        // 例:
        // [2026-02-15 19:00:00] [request_id=abc123de] local.INFO: メッセージ []
        $formatter = new LineFormatter(
            "[%datetime%] [request_id={$requestId}] %channel%.%level_name%: %message% %context%\n",
            'Y-m-d H:i:s',
            true,
            true
        );

        // single/dailyなど、紐づいている全handlerに同じformatterを適用する。
        foreach ($monolog->getHandlers() as $handler) {
            if ($handler instanceof FormattableHandlerInterface) {
                $handler->setFormatter($formatter);
            }
        }
    }

    /**
     * 8文字のリクエストIDを返す。
     * プロセス内では同じIDを使い回す。
     */
    private function resolveRequestId(): string
    {
        // リクエストID（初回はnull）
        static $requestId = null;

        // 既に8文字のリクエストIDがあればそれを返す
        if (is_string($requestId) && preg_match('/^[a-zA-Z0-9]{8}$/', $requestId) === 1) {
            return $requestId;
        }

        // リクエストIDを新規作成して返す
        $requestId = Str::lower(Str::random(8));
        return $requestId;
    }
}
