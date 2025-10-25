<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Monolog\Logger;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\LogRecord;

class AppServiceProvider extends ServiceProvider
{

    public function register(): void {}

    public function boot(): void
    {
        Log::getLogger()->pushHandler(new class extends AbstractProcessingHandler {

            // ログに出力するたびに、呼び出されるメソッド
            // $record にはログに出力された情報（発生時刻、レベル、メッセージなど）を含む
            protected function write(LogRecord $record): void
            {
                // ログレベルが「ERROR」「CRITICAL」「ALERT」「EMERGENCY」のいずれかの場合、メール送信
                if ($record->level->value >= Logger::ERROR) {
                    try {
                        $subject = sprintf('【Tecknical_Stack】システムエラー発生');

                        // ログに出力した内容を取得
                        $fullText = $record->formatted ?? sprintf(
                            "[%s] %s.%s: %s %s",
                            now(),
                            app()->environment(),
                            $record->level->getName(),
                            $record->message,
                            $record->context ? json_encode($record->context, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) : ''
                        );

                        $body = "▼laravel.log ファイルに出力された内容\n\n" . $fullText;

                        // Gmail（.envで設定したSMTP）を使ってメール送信
                        Mail::raw($body, function ($message) use ($subject) {
                            $message->to(env('LOG_MAIL_TO'))
                                ->subject($subject);
                        });
                    } catch (\Exception $e) {
                        // メール送信に失敗した場合、エラーログを出力
                        Log::channel('single')->error('システムエラーの通知に失敗しました。: ' . $e->getMessage());
                    }
                }
            }
        });
    }
}
