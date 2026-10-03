<?php

namespace Tests;

use App\Support\TestingDatabase;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public function createApplication(): Application
    {
        $app = parent::createApplication();
        TestingDatabase::assertSafe();

        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.'.(crc32($this->name()) % 250 + 1)]);
    }
}
