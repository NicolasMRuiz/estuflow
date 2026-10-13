<?php

namespace App\Console\Commands;

use App\Academic\CatalogImporter;
use Illuminate\Console\Command;

class ImportCatalog extends Command
{
    protected $signature = 'catalog:import {path? : JSON a importar; por defecto catálogo M03 versionado}';

    protected $description = 'Importa planes inmutables de forma transaccional sin alterar progreso personal';

    public function handle(CatalogImporter $importer): int
    {
        $count = $importer->file($this->argument('path'));
        $this->info("Catálogo validado e importado. Planes disponibles: {$count}.");

        return self::SUCCESS;
    }
}
