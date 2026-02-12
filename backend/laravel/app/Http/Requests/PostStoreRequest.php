<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Log;

/**
 * 投稿作成の入力を検証するリクエスト
 */
class PostStoreRequest extends FormRequest
{
    /**
     * 認可の判定（認証済みユーザーのみ想定）
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
            'content' => 'required_without:image|nullable|string|max:1000', // 画像が無い場合は必須 | 文字列 | 1000文字以下
            'image' => 'required_without:content|nullable|image|max:2048', // 本文が無い場合は必須 | 画像 | 2048KB以下
        ];
    }

    /**
     * バリデーション失敗時のレスポンスを JSON で返す
     */
    protected function failedValidation(Validator $validator): void
    {
        Log::info('[投稿作成] 入力内容に不備があったため、作成に失敗しました。');

        throw new HttpResponseException(
            response()->json(
                ['message' => '入力内容に誤りがあります。', 'errors' => $validator->errors()],
                422
            )
        );
    }
}
