<?php

namespace App\Console\Commands;

use App\Calendar\Reminders;
use App\Feeds\SharedFeeds;
use Illuminate\Console\Command;

class TickM04 extends Command
{
    protected $signature = 'm04:tick {--local-only : Despachar avisos locales sin consultar fuentes}';

    protected $description = 'Revisar avisos pendientes y las cadencias persistentes de fuentes compartidas';

    public function handle(Reminders $reminders, SharedFeeds $feeds): int
    {
        $reminders->dispatchDue();
        if (! $this->option('local-only')) {
            $feeds->tick();
        }
        $this->info('Revisión periódica completada.');

        return self::SUCCESS;
    }
}
