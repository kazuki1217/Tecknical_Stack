<?php

namespace Tests\Feature;

use Tests\TestCase;

class HealthTest extends TestCase
{
    public function test_health_endpoint_returns_ok(): void
    {
        $this->getJson('/api/health')
            ->assertStatus(200)
            ->assertExactJson('正常に稼働しています。');
    }
}
