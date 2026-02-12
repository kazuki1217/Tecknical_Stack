<?php

namespace Tests\Unit;

use App\Http\Requests\PostStoreRequest;
use App\Http\Requests\PostUpdateRequest;
use PHPUnit\Framework\TestCase;

class PostRequestRulesTest extends TestCase
{
    public function test_post_store_has_rules_keys(): void
    {
        // PostStoreRequest のバリデーション定義を取得
        $rules = (new PostStoreRequest())->rules();

        // 必須のキーだけ確認（細かい文字列比較はしない）
        $this->assertArrayHasKey('content', $rules);
        $this->assertArrayHasKey('image', $rules);
    }

    public function test_post_update_has_content_rule(): void
    {
        // PostUpdateRequest のバリデーション定義を取得
        $rules = (new PostUpdateRequest())->rules();

        // content ルールがあることだけ確認
        $this->assertArrayHasKey('content', $rules);
    }
}
