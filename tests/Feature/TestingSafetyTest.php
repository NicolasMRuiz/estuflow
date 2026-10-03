<?php

namespace Tests\Feature;

use App\Support\TestingDatabase;
use RuntimeException;
use Tests\TestCase;

class TestingSafetyTest extends TestCase
{
    public function test_reset_guard_rejects_a_development_database(): void
    {
        config(['database.connections.pgsql.database' => 'estuflow']);
        $this->expectException(RuntimeException::class);
        TestingDatabase::assertSafe();
    }

    public function test_reset_guard_rejects_a_development_host(): void
    {
        config(['database.connections.pgsql.host' => 'postgres']);
        $this->expectException(RuntimeException::class);
        TestingDatabase::assertSafe();
    }

    public function test_reset_guard_rejects_non_testing_environment(): void
    {
        app()->instance('env', 'local');
        $this->expectException(RuntimeException::class);
        TestingDatabase::assertSafe();
    }
}
