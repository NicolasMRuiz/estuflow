<?php

namespace App\Console\Commands;

use App\Feeds\BoardParser;
use App\Feeds\SourceHttp;
use Illuminate\Console\Command;
use Throwable;

class DiagnoseM04 extends Command
{
    protected $signature = 'm04:diagnose';

    protected $description = 'Leer fuentes públicas sin guardar, notificar ni enviar correo';

    public function handle(SourceHttp $http, BoardParser $parser): int
    {
        $failed = false;
        foreach (['holidays' => 'https://api.argentinadatos.com/v1/feriados/'.now(config('m04.timezone'))->year, 'board' => BoardParser::SOURCE] as $name => $url) {
            try {
                $body = $http->get($url);
                $rows = $name === 'board' ? $parser->parse($body) : json_decode($body, true, 32, JSON_THROW_ON_ERROR);
                $this->info($name.': HTTP válido, '.strlen($body).' bytes, '.count($rows).' entradas. Sin persistencia ni envíos.');
            } catch (Throwable) {
                $failed = true;
                $this->error($name.': diagnóstico no disponible; revisar red o contrato de fuente.');
            }
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
