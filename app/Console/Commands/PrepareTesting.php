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

        $result = $this->call('migrate:fresh', ['--force' => true]);

        return $result === self::SUCCESS ? $this->call('catalog:import') : $result;
    }
}
