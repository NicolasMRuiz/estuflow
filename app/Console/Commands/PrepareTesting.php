<?php

namespace App\Console\Commands;

use App\Support\TestingDatabase;
use Illuminate\Console\Command;

class PrepareTesting extends Command
{
    protected $signature = 'testing:prepare';

    protected $description = 'Recrea únicamente la base aislada de pruebas';

    public function handle(): int
    {
        TestingDatabase::assertSafe();

        return $this->call('migrate:fresh', ['--force' => true]);
    }
}
