<?php

namespace Tests\Feature;

use App\Http\Controllers\Auth\PasswordResetController;
use App\Jobs\RequestPasswordReset;
use App\Jobs\SendEmailChange;
use App\Livewire\Dashboard;
use App\Models\User;
use App\Notifications\PasswordChanged;
use App\Notifications\ResetPassword;
use App\Notifications\VerifyEmail;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\MailManager;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Tests\TestCase;

class RecoveryVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
    }

    private function verification(User $user, int $minutes = 60): string
    {
        return URL::temporarySignedRoute('verification.verify', now()->addMinutes($minutes), ['id' => $user->id, 'hash' => sha1($user->email)]);
    }

    /** @return array<string, string> */
    private function resetData(User $user, string $token): array
    {
        return ['email' => $user->email, 'token' => $token, 'password' => 'nueva-clave-ficticia', 'password_confirmation' => 'nueva-clave-ficticia'];
    }

    public function test_registration_dispatches_verification_and_blocks_private_pages(): void
    {
        $this->post('/registro', ['name' => 'Prueba', 'email' => 'new@example.test', 'password' => 'clave-ficticia-segura', 'password_confirmation' => 'clave-ficticia-segura', 'email_verified_at' => now()])->assertRedirect('/verificar-email');
        $user = User::sole();
        $this->assertNull($user->email_verified_at);
        Notification::assertSentTo($user, VerifyEmail::class);
        $this->get('/dashboard')->assertRedirect('/verificar-email');
        $this->get('/metodos-de-acceso')->assertRedirect('/verificar-email');
        $this->get('/verificar-email')->assertOk()->assertSee($user->email);
    }

    public function test_unverified_livewire_component_is_blocked(): void
    {
        Livewire::actingAs(User::factory()->unverified()->create())->test(Dashboard::class)->assertForbidden();
    }

    public function test_livewire_update_rechecks_verification_after_initial_render(): void
    {
        $user = User::factory()->create();
        $this->configureAcademic($user);
        $component = Livewire::actingAs($user)->test(Dashboard::class)->assertOk();
        $user->forceFill(['email_verified_at' => null])->save();
        auth()->setUser($user->fresh());
        $component->call('$refresh')->assertForbidden();
    }

    public function test_existing_unverified_account_login_goes_to_notice(): void
    {
        $user = User::factory()->unverified()->create(['password' => 'clave-ficticia-segura']);
        $this->post('/login', ['email' => $user->email, 'password' => 'clave-ficticia-segura'])->assertRedirect('/verificar-email');
        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_valid_verification_is_idempotent_and_emits_one_event(): void
    {
        Event::fake([Verified::class]);
        $user = User::factory()->unverified()->create();
        $link = $this->verification($user);
        $this->actingAs($user)->get($link)->assertRedirect('/dashboard');
        $this->get($link)->assertRedirect('/dashboard');
        $this->assertNotNull($user->fresh()->email_verified_at);
        Event::assertDispatchedTimes(Verified::class, 1);
    }

    public function test_tampered_expired_wrong_user_and_wrong_email_verification_are_rejected(): void
    {
        $user = User::factory()->unverified()->create();
        $other = User::factory()->unverified()->create();
        $link = $this->verification($user);
        $this->actingAs($other)->get($link)->assertForbidden()->assertSee('enlace');
        $this->actingAs($user)->get($link.'tampered')->assertForbidden();
        $this->get($this->verification($user, -1))->assertForbidden();
        $wrongEmail = URL::temporarySignedRoute('verification.verify', now()->addHour(), ['id' => $user->id, 'hash' => sha1('wrong@example.test')]);
        $this->get($wrongEmail)->assertForbidden();
        $this->assertNull($user->fresh()->email_verified_at);
        $this->assertNull($other->fresh()->email_verified_at);
    }

    public function test_guest_verification_resumes_safe_intended_url_after_login(): void
    {
        $user = User::factory()->unverified()->create(['password' => 'clave-ficticia-segura']);
        $link = $this->verification($user);
        $this->get($link)->assertRedirect('/login');
        $this->post('/login', ['email' => $user->email, 'password' => 'clave-ficticia-segura'])->assertRedirect($link);
        $this->get($link)->assertRedirect('/dashboard');
    }

    public function test_login_discards_external_intended_url(): void
    {
        $user = User::factory()->create(['password' => 'clave-ficticia-segura']);
        $this->withSession(['url.intended' => 'https://hostile.test/login'])->post('/login', ['email' => $user->email, 'password' => 'clave-ficticia-segura'])->assertRedirect('/dashboard');
    }

    public function test_verification_resend_has_visible_cooldown(): void
    {
        $user = User::factory()->unverified()->create();
        $this->actingAs($user)->post('/verificar-email/reenviar')->assertSessionHas('status');
        $this->post('/verificar-email/reenviar')->assertSessionHasErrors('auth');
        Notification::assertSentToTimes($user, VerifyEmail::class, 1);
        $this->get('/verificar-email')->assertSee('segundos');
        RateLimiter::clear('verify:'.$user->id);
    }

    public function test_recovery_existing_and_missing_account_have_same_queued_public_response(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        foreach ([$user->email, 'missing-'.uniqid().'@example.test'] as $email) {
            $this->post('/olvide-mi-contrasena', ['email' => strtoupper($email)])->assertSessionHas('status', PasswordResetController::RESPONSE)->assertSessionHasNoErrors();
        }
        Queue::assertPushed(RequestPasswordReset::class, 2);
        Queue::assertPushed(RequestPasswordReset::class, fn ($job) => $job->email === $user->email);
        Notification::assertNothingSent();
    }

    public function test_recovery_limits_normalized_missing_email_and_ip(): void
    {
        Queue::fake();
        $email = 'missing-limit-'.uniqid().'@example.test';
        $this->post('/olvide-mi-contrasena', ['email' => $email])->assertSessionHasNoErrors();
        $this->post('/olvide-mi-contrasena', ['email' => '  '.strtoupper($email).'  '])->assertSessionHasErrors('auth');
        for ($i = 0; $i < 9; $i++) {
            $this->post('/olvide-mi-contrasena', ['email' => uniqid().'@example.test']);
        }
        $this->post('/olvide-mi-contrasena', ['email' => uniqid().'@example.test'])->assertSessionHasErrors('auth');
    }

    public function test_worker_uses_broker_hash_expiration_and_throttle(): void
    {
        $user = User::factory()->create();
        (new RequestPasswordReset($user->email))->handle();
        $raw = '';
        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use (&$raw) {
            $raw = $notification->token;

            return true;
        });
        $stored = DB::table('password_reset_tokens')->where('email', $user->email)->value('token');
        $this->assertNotSame($raw, $stored);
        $this->assertTrue(Hash::check($raw, $stored));
        (new RequestPasswordReset($user->email))->handle();
        Notification::assertSentToTimes($user, ResetPassword::class, 1);
        $this->assertSame(60, config('auth.passwords.users.expire'));
        $this->assertSame(60, config('auth.passwords.users.throttle'));
    }

    public function test_reset_changes_hash_rotates_remember_token_and_invalidates_only_own_sessions(): void
    {
        $user = User::factory()->unverified()->create(['password' => 'anterior-clave-ficticia', 'remember_token' => 'old']);
        $other = User::factory()->create();
        foreach (['session-first' => $user, 'session-second' => $user, 'session-other' => $other] as $id => $owner) {
            DB::table('sessions')->insert(['id' => $id, 'user_id' => $owner->id, 'payload' => 'fictitious', 'last_activity' => time()]);
        }
        $token = Password::createToken($user);
        $this->post('/restablecer-contrasena', $this->resetData($user, $token))->assertRedirect('/login')->assertSessionHas('status');
        $this->assertGuest();
        $user->refresh();
        $this->assertTrue(Hash::check('nueva-clave-ficticia', $user->password));
        $this->assertNotSame('old', $user->remember_token);
        $this->assertNull($user->email_verified_at);
        $this->assertDatabaseMissing('sessions', ['user_id' => $user->id]);
        $this->assertDatabaseHas('sessions', ['id' => 'session-other']);
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);
        Notification::assertSentTo($user, PasswordChanged::class);
        $this->post('/login', ['email' => $user->email, 'password' => 'anterior-clave-ficticia'])->assertSessionHasErrors('auth');
        $this->post('/login', ['email' => $user->email, 'password' => 'nueva-clave-ficticia'])->assertRedirect('/verificar-email');
    }

    public function test_invalid_expired_reused_replaced_and_mismatched_reset_tokens_do_not_work(): void
    {
        $user = User::factory()->create(['password' => 'anterior-clave-ficticia']);
        $other = User::factory()->create();
        $token = Password::createToken($user);
        $this->post('/restablecer-contrasena', $this->resetData($user, $token.'bad'))->assertSessionHasErrors('token');
        $this->post('/restablecer-contrasena', $this->resetData($other, $token))->assertSessionHasErrors('token');
        DB::table('password_reset_tokens')->where('email', $user->email)->update(['created_at' => now()->subMinutes(61)]);
        $this->post('/restablecer-contrasena', $this->resetData($user, $token))->assertSessionHasErrors('token');
        $replacement = Password::createToken($user);
        $this->post('/restablecer-contrasena', $this->resetData($user, $token))->assertSessionHasErrors('token');
        $this->post('/restablecer-contrasena', $this->resetData($user, $replacement))->assertRedirect('/login');
        $this->post('/restablecer-contrasena', $this->resetData($user, $replacement))->assertSessionHasErrors('token');
    }

    public function test_reset_validation_does_not_flash_passwords_or_token(): void
    {
        $this->post('/restablecer-contrasena', ['email' => 'invalid', 'token' => 'sensitive-token', 'password' => 'short', 'password_confirmation' => 'different'])
            ->assertSessionHasErrors(['email', 'password'])->assertSessionMissing('_old_input.token')->assertSessionMissing('_old_input.password')->assertSessionMissing('_old_input.password_confirmation');
    }

    public function test_security_notification_urls_ignore_untrusted_host_and_are_spanish(): void
    {
        $user = User::factory()->unverified()->create();
        $this->withServerVariables(['HTTP_HOST' => 'hostile.test'])->get('/login');
        $message = (new ResetPassword('private-test-token'))->toMail($user);
        $this->assertStringStartsWith(config('app.url').'/', $message->actionUrl);
        $this->assertStringNotContainsString('hostile.test', $message->actionUrl);
        $verify = (new VerifyEmail)->toMail($user);
        $this->assertStringStartsWith(config('app.url').'/', $verify->actionUrl);
        $this->assertSame('Verificá tu email en EstuFlow', $verify->subject);
        $this->assertNotSame($message->actionUrl, $verify->actionUrl);
    }

    public function test_rate_limit_rejects_external_referrer_and_flashes_no_secrets(): void
    {
        Queue::fake();
        $email = 'referrer-'.uniqid().'@example.test';
        $this->withHeaders(['Referer' => 'https://hostile.test/'])->post('/olvide-mi-contrasena', ['email' => $email]);
        $this->withHeaders(['Referer' => 'https://hostile.test/'])->post('/olvide-mi-contrasena', ['email' => $email, 'password' => 'private-fictitious', 'token' => 'private-token'])
            ->assertRedirect('/login')->assertSessionHasErrors('auth')->assertSessionMissing('_old_input.password')->assertSessionMissing('_old_input.token');
    }

    public function test_oauth_only_account_can_start_recovery_from_access_methods_after_logout(): void
    {
        $user = User::factory()->create(['password' => null]);
        $this->actingAs($user)->get('/metodos-de-acceso')->assertRedirect(route('profile.index'));
        $this->get('/mi-perfil')->assertOk()->assertSee('Establecer contraseña');
        $this->post('/logout', ['recover' => '1'])->assertRedirect('/olvide-mi-contrasena');
        $this->assertGuest();
    }

    public function test_database_queue_encrypts_request_email_and_notification_tokens(): void
    {
        config(['queue.connections.database.after_commit' => false]);
        $queue = Queue::connection('database');
        $email = 'private-queue@example.test';
        $queue->push(new RequestPasswordReset($email));
        $payload = DB::table('jobs')->value('payload');
        $this->assertStringNotContainsString($email, $payload);
        $job = json_decode($payload, true, flags: JSON_THROW_ON_ERROR);
        $this->assertStringContainsString($email, Crypt::decryptString($job['data']['command']));
        DB::table('jobs')->delete();
        $queue->push(new SendEmailChange((string) Str::uuid(), 'current', 'private-email-change-token'));
        $this->assertStringNotContainsString('private-email-change-token', DB::table('jobs')->value('payload'));
        DB::table('jobs')->delete();
        $user = User::factory()->create();
        $queue->push(new SendQueuedNotifications($user, new ResetPassword('private-raw-token'), ['mail']));
        $this->assertStringNotContainsString('private-raw-token', DB::table('jobs')->value('payload'));
    }

    public function test_empty_mail_url_preserves_the_isolated_smtp_transport(): void
    {
        $this->assertNull(config('mail.mailers.smtp.url'));
        $transport = $this->app->make(MailManager::class)->mailer('smtp')->getSymfonyTransport();
        $this->assertInstanceOf(EsmtpTransport::class, $transport);
        $this->assertSame('test-mailpit', $transport->getStream()->getHost());
    }
}
