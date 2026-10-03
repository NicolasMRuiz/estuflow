<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use RuntimeException;

final class TestingDatabase
{
    public static function assertSafe(): void
    {
        if (! app()->environment('testing')
            || config('database.default') !== 'pgsql'
            || config('database.connections.pgsql.host') !== 'test-postgres'
            || config('database.connections.pgsql.database') !== 'estuflow_test'
            || DB::connection()->getDatabaseName() !== 'estuflow_test') {
            throw new RuntimeException('Operación cancelada: requiere testing y PostgreSQL test-postgres/estuflow_test.');
        }
    }
}
