<?php

namespace App\Console\Commands;

use App\Calendar\Reminders;
use App\Feeds\BoardParser;
use App\Feeds\SharedFeeds;
use App\Models\CalendarEvent;
use App\Models\EventReminder;
use App\Models\FeedSync;
use App\Models\User;
use App\Support\TestingDatabase;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

// CLI-only bridge for the isolated browser container. No HTTP route or production hook.
class ControlM04Testing extends Command
{
    protected $signature = 'testing:m04-control';

    protected $description = 'Controlador CLI de fixtures/reloj de E2E, exclusivamente en PostgreSQL de testing';

    public function handle(): int
    {
        TestingDatabase::assertSafe();
        if (config('mail.mailers.smtp.host') !== 'test-mailpit') {
            return self::FAILURE;
        }
        $directory = storage_path('framework/testing-m04');
        if (! is_dir($directory)) {
            mkdir($directory, 0700, true);
        }
        Http::preventStrayRequests();
        $deadline = microtime(true) + 3600;
        while (microtime(true) < $deadline) {
            foreach (glob($directory.'/*.request.json') ?: [] as $file) {
                $output = str_replace('.request.json', '.result.json', $file);
                try {
                    $input = json_decode(file_get_contents($file), true, 8, JSON_THROW_ON_ERROR);
                    if (! Str::isUuid(basename($file, '.request.json')) || ! is_string($input['email'] ?? null) || ! str_ends_with($input['email'], '@example.test')) {
                        throw new \RuntimeException;
                    }
                    $user = User::where('email', $input['email'])->firstOrFail();
                    $event = isset($input['event']) ? CalendarEvent::withTrashed()->where('user_id', $user->id)->findOrFail((int) $input['event']) : null;
                    $result = match ($input['action'] ?? '') {
                        'inspect' => ['version' => $event?->schedule_version, 'sent' => EventReminder::where('calendar_event_id', $event?->id)->where('email_status', 'sent')->count(), 'pending' => EventReminder::where('calendar_event_id', $event?->id)->where('internal_status', 'pending')->count(), 'cancelled' => EventReminder::where('calendar_event_id', $event?->id)->where('internal_status', 'cancelled')->count()],
                        'deliver' => $this->deliver($event),
                        'board' => $this->board(),
                        default => throw new \RuntimeException,
                    };
                    file_put_contents($output, json_encode(['ok' => true, 'result' => $result], JSON_THROW_ON_ERROR));
                } catch (Throwable) {
                    file_put_contents($output, '{"ok":false}');
                }
                unlink($file);
            }
            usleep(100_000);
        }

        return self::SUCCESS;
    }

    /** @return array<string, bool> */
    private function deliver(?CalendarEvent $event): array
    {
        abort_unless($event !== null, 404);
        $r = EventReminder::where('calendar_event_id', $event->id)->where('version', $event->schedule_version)->where('internal_status', 'pending')->orderBy('due_at')->firstOrFail();
        // Advance the persisted due instant; the real queue worker still uses real time.
        $r->update(['due_at' => now()->subSecond()]);
        app(Reminders::class)->dispatchDue();

        return ['dispatched' => true];
    }

    /** @return array<string, bool> */
    private function board(): array
    {
        $fixture = file_get_contents(base_path('tests/Fixtures/m04/board.html'));
        $next = str_replace('</body>', '<div class="noticia"><img src="files-cartelera/20261024180000.jpg"><h3>Novedad de prueba M04</h3><div class="descripcion">Aviso ficticio para el recorrido E2E.</div><span class="fecha">24-10-2026</span></div></body>', $fixture);
        Http::fake([BoardParser::SOURCE => Http::sequence()->push($fixture)->push($next)]);
        $feeds = app(SharedFeeds::class);
        FeedSync::whereKey('board')->update(['next_attempt_at' => now()->subMinute()]);
        $feeds->board();
        FeedSync::whereKey('board')->update(['next_attempt_at' => now()->subMinute()]);
        $feeds->board();
        $feeds->fanOut();

        return ['fixture_loaded' => true];
    }
}
