<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class HealthTest extends TestCase
{
    /**
     * Laravelが稼働している場合、データベースの状態に関係なく200が返ることを確認する
     */
    public function test_liveness_endpoint_returns_ok_without_checking_database(): void
    {
        DB::shouldReceive('selectOne')->never();

        $this->getJson('/api/health/live')
            ->assertStatus(200)
            ->assertExactJson(['status' => 'ok']);
    }

    /**
     * MySQLへ接続できる場合、200が返ることを確認する
     */
    public function test_readiness_endpoint_returns_ok_when_database_is_available(): void
    {
        DB::shouldReceive('selectOne')
            ->once()
            ->with('SELECT 1 AS health_check')
            ->andReturn((object) ['health_check' => 1]);

        $this->getJson('/api/health/ready')
            ->assertStatus(200)
            ->assertExactJson(['status' => 'ok']);
    }

    /**
     * MySQLへ接続できない場合、503が返ることを確認する
     */
    public function test_readiness_endpoint_returns_service_unavailable_when_database_is_unavailable(): void
    {
        DB::shouldReceive('selectOne')
            ->once()
            ->with('SELECT 1 AS health_check')
            ->andThrow(new RuntimeException('Database connection failed.'));

        $this->getJson('/api/health/ready')
            ->assertStatus(503)
            ->assertExactJson(['status' => 'unavailable']);
    }
}
