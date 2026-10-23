<?php

namespace Tests\Feature;

use App\Calendar\Events;
use App\Models\EventReminder;
use App\Models\InboxNotice;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class M04ConcurrencyTest extends TestCase
{
    use DatabaseMigrations;

    public function test_two_real_dispatcher_processes_create_only_one_internal_notice(): void
    {
        $user = User::factory()->create();
        app(Events::class)->save($user, ['title' => 'Aviso concurrente ficticio', 'type' => 'exam', 'all_day' => 0, 'start_date' => now(config('m04.timezone'))->addDays(2)->toDateString(), 'start_time' => '18:00', 'reminder_options' => ['hour']]);
        EventReminder::query()->update(['due_at' => now()->subSecond()]);
        $command = [PHP_BINARY, 'artisan', 'm04:tick', '--local-only', '--env=testing'];
        $a = new Process($command, base_path(), timeout: 20);
        $b = new Process($command, base_path(), timeout: 20);
        $a->start();
        $b->start();
        $a->wait();
        $b->wait();
        $this->assertTrue($a->isSuccessful(), 'El primer despachador aislado debe completar.');
        $this->assertTrue($b->isSuccessful(), 'El segundo despachador aislado debe completar.');
        $this->assertSame(1, InboxNotice::count());
        $this->assertSame('delivered', EventReminder::sole()->internal_status);
    }
}
