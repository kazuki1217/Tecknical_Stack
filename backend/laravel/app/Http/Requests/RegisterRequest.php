<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Log;

/**
 * アカウント登録の入力を検証するリクエスト
 */
class RegisterRequest extends FormRequest
{
    /**
     * 認可の判定（登録は誰でも可能）
     */
    public function authorize(): bool
    {
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
            'name' => 'required|string', // 入力必須 | 文字列であること
            'email' => 'required|email|unique:users', // 入力必須 | @ を含むメール形式であること | users テーブル内で重複しないこと
            'password' => 'required|min:6|confirmed', // 入力必須 | 6文字以上であること | password_confirmation と一致すること
        ];
    }

    /**
     * バリデーション失敗時のレスポンスを JSON で返す
     */
    protected function failedValidation(Validator $validator): void
    {
        Log::info('[アカウント登録] 入力内容に不備があったため、登録に失敗しました。');

        throw new HttpResponseException(
            response()->json(
                ['message' => '入力内容に誤りがあります。', 'errors' => $validator->errors()],
                422
            )
        );
    }
}
