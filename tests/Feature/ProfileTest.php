<?php

namespace Tests\Feature;

use App\Academic\AcademicProgress;
use App\Auth\OAuthAccounts;
use App\Auth\OAuthProfile;
use App\Calendar\Reminders;
use App\Jobs\SendEmailChange;
use App\Jobs\SendReminderEmail;
use App\Models\EventReminder;
use App\Models\InboxNotice;
use App\Models\PendingEmailChange;
use App\Models\StudyPlan;
use App\Models\User;
use App\Notifications\EmailChanged;
use App\Notifications\PasswordChanged;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        Notification::fake();
        Mail::fake();
        $this->user = User::factory()->create(['email' => 'current@example.test', 'password' => 'current-fictitious-password']);
        foreach (['profile:', 'email-change:', 'email-change-hour:'] as $key) {
            RateLimiter::clear(md5(($key === 'profile:' ? 'profile' : 'email-change').$key.$this->user->id));
        }
    }

    private function recent(bool $provider = false): static
    {
        return $this->actingAs($this->user)->withSession(['auth.password_confirmed_at' => now()->timestamp, 'oauth.reauthenticated_user_id' => $this->user->id, 'profile.provider_confirmation' => $provider ? ['user_id' => $this->user->id, 'at' => now()->timestamp] : null]);
    }

    private function requestChange(string $email = 'new@example.test'): PendingEmailChange
    {
        $this->recent()->post(route('profile.email.request'), ['email' => $email])->assertRedirect(route('profile.email'))->assertSessionHasNoErrors();

        return PendingEmailChange::sole();
    }

    private function link(PendingEmailChange $change, string $purpose): string
    {
        $job = Queue::pushed(SendEmailChange::class)->first(fn ($job) => $job->changeId === $change->id && $job->purpose === $purpose);
        $this->assertNotNull($job);

        return route('profile.email.confirm', ['change' => $change->id, 'purpose' => $purpose, 'token' => $job->token]);
    }

    public function test_profile_is_verified_but_independent_of_academic_onboarding(): void
    {
        $this->get('/mi-perfil')->assertRedirect(route('login'));
        $this->actingAs(User::factory()->unverified()->create())->get('/mi-perfil')->assertRedirect(route('verification.notice'));
        $this->actingAs($this->user)->get('/mi-perfil')->assertOk()->assertSee('Completar mi carrera')->assertSee('Email verificado');
        $this->get('/dashboard')->assertRedirect(route('academic.choose'));
        $this->get('/metodos-de-acceso')->assertRedirect(route('profile.index'));
    }

    public function test_name_persists_and_updates_dashboard_and_initial_without_javascript(): void
    {
        $this->configureAcademic($this->user);
        $this->actingAs($this->user)->post(route('profile.name'), ['name' => 'Ángela María Ruiz'])->assertRedirect(route('profile.index'));
        $this->get('/mi-perfil')->assertOk()->assertSee('Ángela María Ruiz')->assertSee('Á');
        $this->get('/dashboard')->assertOk()->assertSee('Ángela María Ruiz');
        $this->assertSame('Ángela María Ruiz', $this->user->fresh()->name);
    }

    public static function badNames(): array
    {
        return [[''], [str_repeat('a', 101)], [['otro']], [23]];
    }

    #[DataProvider('badNames')]
    public function test_invalid_name_is_rejected(mixed $name): void
    {
        $before = $this->user->name;
        $this->actingAs($this->user)->post(route('profile.name'), ['name' => $name])->assertSessionHasErrors('name')->assertRedirect(route('profile.index'));
        $this->get('/mi-perfil')->assertOk();
        $this->assertSame($before, $this->user->fresh()->name);
    }

    public function test_name_rejects_foreign_or_sensitive_attributes_and_cancel_get_is_inert(): void
    {
        $other = User::factory()->create();
        $before = $this->user->fresh()->getAttributes();
        foreach (['user_id' => $other->id, 'email' => 'forged@example.test', 'email_verified_at' => now()->toISOString(), 'password' => 'forged-password', 'role' => 'admin', 'study_plan_id' => 1] as $key => $value) {
            $this->actingAs($this->user)->post(route('profile.name'), ['name' => 'Cambio indebido', $key => $value])->assertSessionHasErrors('account');
        }
        $this->get('/mi-perfil')->assertOk();
        $this->assertSame($before, $this->user->fresh()->getAttributes());
    }

    public static function badEmails(): array
    {
        return [['bad'], [['bad']], ['current@example.test'], [' CURRENT@EXAMPLE.TEST '], ['occupied@example.test']];
    }

    #[DataProvider('badEmails')]
    public function test_email_validation_is_non_destructive(mixed $email): void
    {
        User::factory()->create(['email' => 'occupied@example.test']);
        $this->recent()->post(route('profile.email.request'), ['email' => $email])->assertSessionHasErrors('email');
        $this->get('/mi-perfil/email')->assertOk();
        $this->assertDatabaseCount('pending_email_changes', 0);
        $this->assertSame('current@example.test', $this->user->fresh()->email);
    }

    public function test_email_requires_recent_same_account_reauthentication_and_is_limited(): void
    {
        $this->actingAs($this->user)->post(route('profile.email.request'), ['email' => 'new@example.test'])->assertSessionHasErrors('account');
        $this->post(route('profile.email.resend'))->assertSessionHasErrors('auth');
        $this->assertDatabaseCount('pending_email_changes', 0);
        $this->travel(61)->seconds();
        RateLimiter::clear(md5('email-changeemail-change:'.$this->user->id));
        $this->withSession(['auth.password_confirmed_at' => now()->timestamp, 'oauth.reauthenticated_user_id' => $this->user->id + 1])->post(route('profile.email.request'), ['email' => 'new@example.test'])->assertSessionHasErrors('account');
    }

    public function test_pending_email_is_normalized_and_tokens_only_exist_as_hashes_in_database(): void
    {
        $change = $this->requestChange(' NEW@Example.Test ');
        $this->assertSame('new@example.test', $change->proposed_email);
        $this->assertSame('current@example.test', $this->user->fresh()->email);
        $this->assertTrue($this->user->fresh()->hasVerifiedEmail());
        Queue::assertPushed(SendEmailChange::class, 2);
        foreach (Queue::pushed(SendEmailChange::class) as $job) {
            $this->assertTrue($change->accepts($this->user, $job->purpose, $job->token));
            $this->assertStringNotContainsString($job->token, json_encode($change->getAttributes()));
        }
        $this->get($this->link($change, 'current'))->assertOk()->assertSee('Confirmar este correo');
        $this->assertNull($change->fresh()->current_confirmed_at);
    }

    public static function orders(): array
    {
        return [['current', 'proposed'], ['proposed', 'current']];
    }

    #[DataProvider('orders')]
    public function test_both_confirmations_are_required_in_either_order_and_reuse_is_denied(string $first, string $last): void
    {
        $change = $this->requestChange();
        $one = $this->link($change, $first);
        $two = $this->link($change, $last);
        $this->post($one)->assertRedirect(route('profile.email'))->assertSessionHasNoErrors();
        $this->assertSame('current@example.test', $this->user->fresh()->email);
        $this->post($one)->assertSessionHasErrors('confirmation');
        $this->get($one)->assertOk()->assertDontSee('Confirmar este correo');
        $this->post($two)->assertRedirect(route('profile.email'));
        $this->assertSame('new@example.test', $this->user->fresh()->email);
        $this->assertTrue($this->user->fresh()->hasVerifiedEmail());
        $this->assertDatabaseCount('pending_email_changes', 0);
        $this->post($two)->assertSessionHasErrors('confirmation');
        Notification::assertSentOnDemand(EmailChanged::class, fn ($n, $channels, $recipient) => $recipient->routes['mail'] === 'current@example.test');
    }

    public function test_wrong_account_purpose_tampered_and_expired_tokens_cannot_confirm(): void
    {
        $change = $this->requestChange();
        $link = $this->link($change, 'current');
        $other = User::factory()->create();
        $this->actingAs($other)->get($link)->assertOk()->assertDontSee('Confirmar este correo');
        $this->post($link)->assertSessionHasErrors('confirmation');
        $this->actingAs($this->user)->post(str_replace('/current/', '/proposed/', $link))->assertSessionHasErrors('confirmation');
        $this->post(substr($link, 0, -1).'!')->assertSessionHasErrors('confirmation');
        $this->travel(60)->minutes();
        $this->post($link)->assertSessionHasErrors('confirmation');
        $this->assertNull($change->fresh()->current_confirmed_at);
        $this->assertSame('current@example.test', $this->user->fresh()->email);
    }

    public function test_resend_new_request_and_cancel_invalidate_old_jobs_and_links(): void
    {
        $old = $this->requestChange();
        $link = $this->link($old, 'current');
        $job = Queue::pushed(SendEmailChange::class)->first();
        $this->travel(61)->seconds();
        RateLimiter::clear(md5('email-changeemail-change:'.$this->user->id));
        $this->post(route('profile.email.resend'))->assertRedirect(route('profile.email'))->assertSessionHasNoErrors();
        $new = PendingEmailChange::sole();
        $this->assertNotSame($old->id, $new->id);
        $job->handle();
        Mail::assertNothingSent();
        $this->post($link)->assertSessionHasErrors('confirmation');
        $this->travel(61)->seconds();
        RateLimiter::clear(md5('email-changeemail-change:'.$this->user->id));
        $this->post(route('profile.email.request'), ['email' => 'another@example.test'])->assertRedirect(route('profile.email'));
        $this->assertSame('another@example.test', PendingEmailChange::sole()->proposed_email);
        $this->post(route('profile.email.cancel'))->assertRedirect(route('profile.email'));
        $this->assertDatabaseCount('pending_email_changes', 0);
        $this->assertSame('current@example.test', $this->user->fresh()->email);
    }

    public function test_smtp_failure_and_suppression_are_explicit_and_leave_email_active(): void
    {
        $change = $this->requestChange();
        Mail::shouldReceive('raw')->once()->andThrow(new \RuntimeException('fictitious failure'));
        Queue::pushed(SendEmailChange::class)->first()->handle();
        $this->assertSame('failed', $change->fresh()->current_delivery);
        $this->get('/mi-perfil/email')->assertOk()->assertSee('No pudimos confirmar el envío');
        $this->assertSame('current@example.test', $this->user->fresh()->email);
        Mail::shouldReceive('raw')->once()->andReturn(null);
        Queue::pushed(SendEmailChange::class)->last()->handle();
        $this->assertSame('failed', $change->fresh()->proposed_delivery);
    }

    public function test_email_collision_after_request_has_no_500_or_partial_commit(): void
    {
        $change = $this->requestChange();
        $this->post($this->link($change, 'current'))->assertRedirect();
        User::factory()->create(['email' => 'new@example.test']);
        $this->post($this->link($change, 'proposed'))->assertSessionHasErrors('confirmation');
        $this->assertSame('current@example.test', $this->user->fresh()->email);
        $this->assertNull($change->fresh()->proposed_confirmed_at);
    }

    private function sessions(): User
    {
        $other = User::factory()->create();
        foreach (['other-own-session' => $this->user->id, 'unrelated-session' => $other->id] as $id => $userId) {
            DB::table('sessions')->insert(['id' => $id, 'user_id' => $userId, 'payload' => '', 'last_activity' => time()]);
        }
        foreach ([$this->user->email, $other->email] as $email) {
            DB::table('password_reset_tokens')->insert(['email' => $email, 'token' => 'fictitious-hash', 'created_at' => now()]);
        }

        return $other;
    }

    public function test_email_changes_revoke_only_own_other_sessions_and_old_reset_without_losing_records(): void
    {
        $enrollment = $this->configureAcademic($this->user);
        $counts = DB::table('course_progress')->count();
        $this->postDataFixture();
        $reminder = EventReminder::firstOrFail();
        $reminder->update(['due_at' => now()->subSecond()]);
        app(Reminders::class)->dispatchDue();
        $notice = InboxNotice::firstOrFail();
        $notice->update(['read_at' => now()]);
        $readAt = $notice->fresh()->read_at->toISOString();
        $other = $this->sessions();
        $oldRemember = $this->user->remember_token;
        $oldVerification = URL::temporarySignedRoute('verification.verify', now()->addHour(), ['id' => $this->user->id, 'hash' => sha1($this->user->email)]);
        $change = $this->requestChange();
        foreach (['current', 'proposed'] as $purpose) {
            $this->post($this->link($change, $purpose))->assertRedirect(route('profile.email'));
        }
        $this->assertDatabaseMissing('sessions', ['id' => 'other-own-session']);
        $this->assertDatabaseHas('sessions', ['id' => 'unrelated-session']);
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => 'current@example.test']);
        $this->assertDatabaseHas('password_reset_tokens', ['email' => $other->email]);
        $this->assertNotSame($oldRemember, $this->user->fresh()->remember_token);
        $this->assertSame($counts, DB::table('course_progress')->count());
        $this->assertDatabaseHas('academic_enrollments', ['id' => $enrollment->id, 'is_active' => true]);
        $this->assertDatabaseCount('calendar_events', 1);
        $this->assertDatabaseCount('personal_notes', 1);
        $this->assertSame($readAt, $notice->fresh()->read_at->toISOString());
        $this->assertSame($reminder->version, $reminder->fresh()->version);
        Mail::shouldReceive('raw')->once()->withArgs(function ($body, $callback): bool {
            $message = \Mockery::mock(Message::class);
            $message->shouldReceive('to')->once()->with('new@example.test')->andReturnSelf();
            $message->shouldReceive('subject')->once()->andReturnSelf();
            $callback($message);

            return true;
        })->andReturn(\Mockery::mock(SentMessage::class));
        (new SendReminderEmail($reminder->id))->handle();
        $this->assertSame('sent', $reminder->fresh()->email_status);
        $this->get($oldVerification)->assertSee('enlace');
        $this->post('/logout');
        $this->post('/login', ['email' => 'current@example.test', 'password' => 'current-fictitious-password'])->assertSessionHasErrors('auth');
        $this->post('/login', ['email' => 'new@example.test', 'password' => 'current-fictitious-password'])->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($this->user);
    }

    private function postDataFixture(): void
    {
        $this->actingAs($this->user)->post(route('notes.store'), ['title' => 'Nota persistente', 'content' => 'Contenido'])->assertRedirect();
        $this->post(route('calendar.store'), ['title' => 'Evento persistente', 'type' => 'exam', 'all_day' => 1, 'start_date' => now()->addDays(3)->toDateString(), 'reminder_options' => ['same_morning'], 'email_reminders' => 1])->assertRedirect();
    }

    public function test_password_rules_current_check_cancel_and_secrets_never_flash(): void
    {
        $this->actingAs($this->user)->post(route('profile.password.update'), ['current_password' => 'incorrect', 'password' => 'new-fictitious-password', 'password_confirmation' => 'new-fictitious-password'])->assertSessionHasErrors('current_password')->assertSessionMissing('_old_input.current_password')->assertSessionMissing('_old_input.password');
        $this->post(route('profile.password.update'), ['current_password' => 'current-fictitious-password', 'password' => 'short', 'password_confirmation' => 'other'])->assertSessionHasErrors(['password', 'password_confirmation']);
        $this->get('/mi-perfil')->assertOk();
        $this->assertTrue(Hash::check('current-fictitious-password', $this->user->fresh()->password));
    }

    public function test_password_change_keeps_current_login_revokes_only_own_others_and_notifies(): void
    {
        $other = $this->sessions();
        $this->actingAs($this->user)->post(route('profile.password.update'), ['current_password' => 'current-fictitious-password', 'password' => 'new-fictitious-password', 'password_confirmation' => 'new-fictitious-password'])->assertRedirect(route('profile.index'));
        $this->assertAuthenticatedAs($this->user);
        $this->assertTrue(Hash::check('new-fictitious-password', $this->user->fresh()->password));
        $this->assertDatabaseMissing('sessions', ['id' => 'other-own-session']);
        $this->assertDatabaseHas('sessions', ['id' => 'unrelated-session']);
        $this->assertDatabaseHas('password_reset_tokens', ['email' => $other->email]);
        Notification::assertSentTo($this->user, PasswordChanged::class);
        $this->post('/logout');
        $this->post('/login', ['email' => $this->user->email, 'password' => 'current-fictitious-password'])->assertSessionHasErrors('auth');
        $this->post('/login', ['email' => $this->user->email, 'password' => 'new-fictitious-password'])->assertRedirect();
        $this->assertAuthenticatedAs($this->user);
    }

    public function test_oauth_only_establishes_password_only_after_linked_provider_confirmation(): void
    {
        $this->user->forceFill(['password' => null])->save();
        app(OAuthAccounts::class)->link($this->user, new OAuthProfile('google', 'https://accounts.google.com', 'stable', 'Proveedor', 'other@example.test'));
        $input = ['password' => 'new-fictitious-password', 'password_confirmation' => 'new-fictitious-password'];
        $this->recent()->post(route('profile.password.update'), $input)->assertSessionHasErrors('account');
        $this->recent(provider: true)->post(route('profile.password.update'), $input)->assertRedirect(route('profile.index'));
        $this->assertDatabaseHas('oauth_identities', ['user_id' => $this->user->id, 'subject' => 'stable']);
        $this->assertTrue(Hash::check('new-fictitious-password', $this->user->fresh()->password));
    }

    public function test_profile_career_cancel_interrupt_and_confirm_preserve_data_and_return_to_profile(): void
    {
        $original = $this->configureAcademic($this->user);
        $this->postDataFixture();
        $this->get('/mi-perfil/carrera')->assertRedirect(route('academic.choose'));
        $next = StudyPlan::whereKeyNot($original->study_plan_id)->first();
        $this->post(route('academic.select'), ['plan_id' => $next->id])->assertRedirect(route('academic.draft'));
        $this->get('/mi-perfil')->assertSee('Borrador sin confirmar')->assertSee('Cambiar carrera');
        $this->post(route('academic.cancel'))->assertRedirect(route('profile.index'));
        $this->assertSame($original->id, app(AcademicProgress::class)->active($this->user)->id);
        $this->get('/mi-perfil/carrera');
        $this->post(route('academic.select'), ['plan_id' => $next->id]);
        $this->get(route('academic.summary'))->assertOk();
        $candidate = app(AcademicProgress::class)->candidate($this->user);
        $this->post(route('academic.finish'), ['candidate_id' => $candidate->id])->assertRedirect(route('profile.index'));
        $this->assertSame($next->id, app(AcademicProgress::class)->active($this->user)->study_plan_id);
        $this->assertDatabaseHas('academic_enrollments', ['id' => $original->id]);
        $this->assertDatabaseCount('calendar_events', 1);
        $this->assertDatabaseCount('personal_notes', 1);
        $this->get('/mi-carrera')->assertOk()->assertDontSee('Cambiar carrera');
    }
}
