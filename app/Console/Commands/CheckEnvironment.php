<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class CheckEnvironment extends Command
{
    protected $signature = 'environment:check';

    protected $description = 'Comprueba PostgreSQL, sesiones y una clave temporal de Redis';

    public function handle(): int
    {
        if (DB::connection()->getDriverName() !== 'pgsql' || config('session.driver') !== 'database') {
            throw new RuntimeException('Se requieren PostgreSQL y sesiones en base de datos.');
        }
        DB::select('SELECT 1 FROM sessions LIMIT 1');
        $this->info('PASS: PostgreSQL y tabla de sesiones.');
        $key = 'readiness:'.Str::uuid();
        try {
            Cache::store('redis')->put($key, 'ok', 30);
            if (Cache::store('redis')->get($key) !== 'ok') {
                throw new RuntimeException('No se pudo leer la clave temporal de Redis.');
            }
        } finally {
            Cache::store('redis')->forget($key);
        }
        $this->info('PASS: escritura/lectura de Redis; solo la clave temporal fue eliminada.');

        return self::SUCCESS;
    }
}
