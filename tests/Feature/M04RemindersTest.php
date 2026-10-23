<?php

namespace Tests\Feature;

use App\Calendar\Events;
use App\Calendar\Reminders;
use App\Jobs\SendReminderEmail;
use App\Models\EventReminder;
use App\Models\InboxNotice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\SentMessage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class M04RemindersTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setDate(2026, 10, 23)->setTime(12, 0));
        $this->user = User::factory()->create();
        Queue::fake();
        Mail::fake();
    }

    /** @return array<string, mixed> */
    private function input(array $replace = []): array
    {
        return array_replace(['title' => 'Examen', 'type' => 'exam', 'all_day' => 0, 'start_date' => '2026-10-24', 'start_time' => '09:00', 'reminder_options' => ['day', 'hour'], 'email_reminders' => 0], $replace);
    }

    public function test_offsets_and_boundary_are_exact_and_dispatch_is_durably_idempotent(): void
    {
        $e = app(Events::class)->save($this->user, $this->input());
        $this->assertSame(['2026-10-23 12:00', '2026-10-24 11:00'], EventReminder::orderBy('due_at')->get()->map(fn ($r) => $r->due_at->format('Y-m-d H:i'))->all());
        app(Reminders::class)->dispatchDue();
        app(Reminders::class)->dispatchDue();
        $this->assertDatabaseCount('inbox_notices', 1);
        $this->assertSame('delivered', EventReminder::where('offset', 'day')->sole()->internal_status);
        $this->travelTo(now()->addHours(23));
        app(Reminders::class)->dispatchDue();
        app(Reminders::class)->dispatchDue();
        $this->assertDatabaseCount('inbox_notices', 2);
        $this->assertSame(2, EventReminder::where('calendar_event_id', $e->id)->count());
    }

    public function test_all_day_both_nine_am_and_end_of_last_local_day(): void
    {
        $e = app(Events::class)->save($this->user, $this->input(['all_day' => 1, 'end_date' => '2026-10-25', 'reminder_options' => ['previous_morning', 'same_morning']]));
        $this->assertSame(['2026-10-23 12:00', '2026-10-24 12:00'], EventReminder::orderBy('due_at')->get()->map(fn ($r) => $r->due_at->format('Y-m-d H:i'))->all());
        $this->assertSame('2026-10-26 02:59', $e->cutoff()->format('Y-m-d H:i'));
    }

    public function test_text_edit_keeps_version_while_reschedule_cancels_old_jobs_and_deletion_cancels_new(): void
    {
        $service = app(Events::class);
        $e = $service->save($this->user, $this->input(['email_reminders' => 1]));
        app(Reminders::class)->dispatchDue();
        $old = EventReminder::where('offset', 'day')->sole();
        $e = $service->save($this->user, $this->input(['title' => 'Corrección', 'email_reminders' => 1]), $e->id);
        $this->assertSame(1, $e->schedule_version);
        app(Reminders::class)->dispatchDue();
        $this->assertDatabaseCount('inbox_notices', 1);
        $e = $service->save($this->user, $this->input(['start_date' => '2026-10-25', 'email_reminders' => 1]), $e->id);
        $this->assertSame(2, $e->schedule_version);
        (new SendReminderEmail($old->id))->handle();
        Mail::assertNothingSent();
        $this->assertSame('cancelled', EventReminder::where('version', 1)->where('offset', 'hour')->sole()->internal_status);
        $service->delete($this->user, $e->id);
        $this->assertSame(0, EventReminder::where('internal_status', 'pending')->count());
        $this->assertSame(0, EventReminder::whereIn('email_status', ['pending', 'queued'])->count());
    }

    public function test_old_options_and_recovery_do_not_burst_expired_notices(): void
    {
        app(Events::class)->save($this->user, $this->input(['start_date' => '2026-10-22']));
        $this->assertDatabaseCount('event_reminders', 0);
        app(Events::class)->save($this->user, $this->input());
        $this->travelTo(now()->addDays(2));
        app(Reminders::class)->dispatchDue();
        $this->assertDatabaseCount('inbox_notices', 0);
        $this->assertSame(2, EventReminder::where('internal_status', 'expired')->count());
    }

    public function test_still_useful_recovery_and_disabled_email(): void
    {
        app(Events::class)->save($this->user, $this->input());
        $this->travelTo(now()->addHours(23)->addMinutes(10));
        app(Reminders::class)->dispatchDue();
        $this->assertDatabaseCount('inbox_notices', 2);
        $this->assertSame(2, EventReminder::where('email_status', 'off')->count());
        Mail::assertNothingSent();
    }

    public function test_email_budget_is_reserved_durably_and_preserves_internal_delivery(): void
    {
        config(['m04.reminder_email_daily_limit' => 0]);
        app(Events::class)->save($this->user, $this->input(['email_reminders' => 1]));
        app(Reminders::class)->dispatchDue();
        $r = EventReminder::where('offset', 'day')->sole();
        (new SendReminderEmail($r->id))->handle();
        $this->assertSame('budget', $r->fresh()->email_status);
        $this->assertDatabaseCount('inbox_notices', 1);
        Mail::assertNothingSent();
    }

    public function test_email_is_sent_once_and_ambiguous_failure_is_terminal(): void
    {
        app(Events::class)->save($this->user, $this->input(['email_reminders' => 1]));
        app(Reminders::class)->dispatchDue();
        $r = EventReminder::where('offset', 'day')->sole();
        Mail::shouldReceive('raw')->once()->andThrow(new \RuntimeException('SMTP ambiguous'));
        (new SendReminderEmail($r->id))->handle();
        (new SendReminderEmail($r->id))->handle();
        $this->assertSame('uncertain', $r->fresh()->email_status);
        $this->assertNull($r->fresh()->email_sent_at);
        $this->assertSame(1, DB::table('reminder_email_budgets')->sum('reserved'));
    }

    public function test_successful_email_and_job_overlap_do_not_resend(): void
    {
        app(Events::class)->save($this->user, $this->input(['email_reminders' => 1]));
        app(Reminders::class)->dispatchDue();
        $r = EventReminder::where('offset', 'day')->sole();
        Mail::shouldReceive('raw')->once()->andReturn(\Mockery::mock(SentMessage::class));
        (new SendReminderEmail($r->id))->handle();
        (new SendReminderEmail($r->id))->handle();
        $this->assertSame('sent', $r->fresh()->email_status);
        $this->assertNotNull($r->fresh()->email_sent_at);
    }

    public function test_lost_queued_work_can_recover_but_interrupted_smtp_becomes_uncertain(): void
    {
        app(Events::class)->save($this->user, $this->input(['email_reminders' => 1]));
        app(Reminders::class)->dispatchDue();
        $r = EventReminder::where('offset', 'day')->sole();
        $r->update(['email_claimed_at' => now()->subMinutes(6)]);
        app(Reminders::class)->dispatchDue();
        $this->assertSame('queued', $r->fresh()->email_status);
        $r = $r->fresh();
        $r->update(['email_status' => 'sending', 'email_claimed_at' => now()->subMinutes(6)]);
        app(Reminders::class)->dispatchDue();
        $this->assertSame('uncertain', $r->fresh()->email_status);
        $this->assertSame(1, InboxNotice::count());
    }

    public function test_exact_cutoff_is_expired_and_deleted_queued_email_cannot_send(): void
    {
        $events = app(Events::class);
        $event = $events->save($this->user, $this->input(['email_reminders' => 1]));
        app(Reminders::class)->dispatchDue();
        $reminder = EventReminder::where('offset', 'day')->sole();
        $events->delete($this->user, $event->id);
        (new SendReminderEmail($reminder->id))->handle();
        Mail::assertNothingSent();
        $events->save($this->user, $this->input());
        $this->travelTo(now()->addDay());
        app(Reminders::class)->dispatchDue();
        $this->assertSame(1, InboxNotice::count());
        $this->assertSame(2, EventReminder::where('internal_status', 'expired')->count());
    }

    public function test_budget_allows_one_attempt_and_keeps_both_internal_notices(): void
    {
        config(['m04.reminder_email_daily_limit' => 1]);
        app(Events::class)->save($this->user, $this->input(['email_reminders' => 1]));
        app(Events::class)->save($this->user, $this->input(['email_reminders' => 1, 'title' => 'Segundo']));
        app(Reminders::class)->dispatchDue();
        Mail::shouldReceive('raw')->once()->andReturn(\Mockery::mock(SentMessage::class));
        foreach (EventReminder::where('offset', 'day')->get() as $r) {
            (new SendReminderEmail($r->id))->handle();
        }
        $this->assertSame(1, EventReminder::where('email_status', 'sent')->count());
        $this->assertSame(1, EventReminder::where('email_status', 'budget')->count());
        $this->assertDatabaseCount('inbox_notices', 2);
    }

    public function test_transport_without_a_sent_message_is_not_recorded_as_delivered(): void
    {
        app(Events::class)->save($this->user, $this->input(['email_reminders' => 1]));
        app(Reminders::class)->dispatchDue();
        $r = EventReminder::where('offset', 'day')->sole();
        Mail::shouldReceive('raw')->once()->andReturn(null);
        (new SendReminderEmail($r->id))->handle();
        $this->assertSame('failed', $r->fresh()->email_status);
        $this->assertNull($r->fresh()->email_sent_at);
    }
}
