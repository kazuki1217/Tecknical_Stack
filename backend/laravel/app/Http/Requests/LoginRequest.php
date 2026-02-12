<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * ログインの入力を検証するリクエスト
 */
class LoginRequest extends FormRequest
{
    /**
     * 認可の判定（ログインは誰でも可能）
     */
    public function authorize(): bool
    {
        $this->ensureIsNotRateLimited();
        return true;
    }

    /**
     * バリデーションルール
     *
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'email' => 'required|email', // 入力必須 | @ を含むメール形式であること
            'password' => 'required|string', // 入力必須 | 文字列であること
        ];
    }

    /**
     * バリデーション失敗時のレスポンスを JSON で返す
     */
    protected function failedValidation(Validator $validator): void
    {
        // バリデーション失敗もログイン試行としてカウント
        $throttleKey = 'login:' . Str::lower($this->ip());
        RateLimiter::hit($throttleKey, 60); // 失敗した回数を +1 加算

        Log::info('[ログイン] 入力内容に不備があったため、認証に失敗しました。');

        throw new HttpResponseException(
            response()->json(
                ['message' => '入力内容に誤りがあります。', 'errors' => $validator->errors()],
                422
            )
        );
    }

    /**
     * レート制限を超えていないか確認する
     */
    protected function ensureIsNotRateLimited(): void
    {
        // リクエスト元の IPアドレスを取得
        $throttleKey = 'login:' . Str::lower($this->ip());

        // 直近60秒間で5回以上ログインに失敗した場合
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {

            // 次に試行できるまでの残り秒数を取得
            $seconds = RateLimiter::availableIn($throttleKey);

            Log::warning('[ログイン] 直近60秒での失敗回数が上限に達したため、試行を制限しました。', ['リクエスト元のIPアドレス' => $this->ip(), '制限した時間' => $seconds]);
            throw new HttpResponseException(
                response()->json(
                    ["message" => "短時間でのログイン試行回数が多すぎます。{$seconds}秒後に再試行してください。"],
                    429
                )
            );
        }
    }
}
