<?php

namespace Tests\Feature;

use App\Account\EmailChanges;
use App\Jobs\SendEmailChange;
use App\Models\PendingEmailChange;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class ProfileConcurrencyTest extends TestCase
{
    use DatabaseMigrations;

    public function test_two_real_postgresql_connections_competing_for_email_leave_one_winner_without_500(): void
    {
        Queue::fake();
        $people = [User::factory()->create(), User::factory()->create()];
        $directory = storage_path('framework/testing-m05-'.Str::uuid());
        mkdir($directory, 0700);
        $processes = [];
        try {
            foreach ($people as $index => $user) {
                app(EmailChanges::class)->request($user, 'contended@example.test');
                $change = PendingEmailChange::where('user_id', $user->id)->sole();
                $change->forceFill(['current_confirmed_at' => now(), 'current_token_hash' => null])->save();
                $job = Queue::pushed(SendEmailChange::class)->first(fn ($job) => $job->changeId === $change->id && $job->purpose === 'proposed');
                $file = $directory.'/'.$index.'.json';
                file_put_contents($file, json_encode(['user' => $user->id, 'change' => $change->id, 'token' => $job->token]));
                chmod($file, 0600);
                $code = <<<'CHILD'
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
App\Support\TestingDatabase::assertSafe();
Illuminate\Support\Facades\Notification::fake();
$data = json_decode(file_get_contents($argv[1]), true);
$user = App\Models\User::findOrFail($data['user']);
$request = Illuminate\Http\Request::create('/mi-perfil/email', 'POST');
$request->setUserResolver(fn () => $user);
$session = new Illuminate\Session\Store('testing', new Illuminate\Session\ArraySessionHandler(120));
$session->start(); $request->setLaravelSession($session);
file_put_contents($argv[1].'.ready', 'ready');
$deadline = microtime(true) + 10;
while (count(glob(dirname($argv[1]).'/*.ready')) < 2) {
    if (microtime(true) > $deadline) exit(2);
    usleep(10000);
}
try {
    $done = app(App\Account\EmailChanges::class)->confirm($request, $data['change'], 'proposed', $data['token']);
    echo $done ? 'completed' : 'incomplete';
} catch (Illuminate\Validation\ValidationException) { echo 'conflict'; }
CHILD;
                $processes[] = new Process([PHP_BINARY, '-r', $code, $file], base_path(), timeout: 20);
            }
            foreach ($processes as $process) {
                $process->start();
            }
            $outcomes = [];
            foreach ($processes as $process) {
                $process->wait();
                $this->assertTrue($process->isSuccessful(), 'La conexión concurrente debe terminar sin excepción no controlada.');
                $outcomes[] = $process->getOutput();
            }
            sort($outcomes);
            $this->assertSame(['completed', 'conflict'], $outcomes);
            $this->assertSame(1, User::where('email', 'contended@example.test')->count());
            $loser = collect($people)->first(fn ($user) => $user->fresh()->email !== 'contended@example.test');
            $this->assertNotNull($loser);
            $this->assertSame($loser->email, $loser->fresh()->email);
            $this->assertSame(1, PendingEmailChange::count());
        } finally {
            foreach (glob($directory.'/*') as $file) {
                unlink($file);
            }
            rmdir($directory);
        }
    }
}
