<?php

namespace App\Jobs;

use App\Models\CalendarEvent;
use App\Models\EventReminder;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendReminderEmail implements ShouldBeEncrypted, ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 25;

    public int $backoff = 30;

    public function __construct(public int $reminderId) {}

    public function handle(): void
    {
        $delivery = DB::transaction(function (): ?array {
            $row = EventReminder::find($this->reminderId);
            if (! $row) {
                return null;
            }
            $event = CalendarEvent::whereKey($row->calendar_event_id)->lockForUpdate()->first();
            $r = EventReminder::whereKey($row->id)->lockForUpdate()->firstOrFail();
            if ($r->email_status !== 'queued') {
                return null;
            }
            $user = User::find($r->user_id);
            if (! $event || $event->user_id !== $r->user_id || $event->schedule_version !== $r->version || ! $event->email_reminders || ! $user?->hasVerifiedEmail() || $r->due_at->gt(now()) || $r->useful_until->lte(now())) {
                $r->update(['email_status' => 'expired']);

                return null;
            }
            $day = now(config('m04.timezone'))->toDateString();
            DB::table('reminder_email_budgets')->insertOrIgnore(['day' => $day, 'reserved' => 0]);
            $budget = DB::table('reminder_email_budgets')->where('day', $day)->lockForUpdate()->first();
            if ($budget->reserved >= max(0, (int) config('m04.reminder_email_daily_limit'))) {
                $r->update(['email_status' => 'budget']);

                return null;
            }
            DB::table('reminder_email_budgets')->where('day', $day)->increment('reserved');
            $r->update(['email_status' => 'sending', 'email_claimed_at' => now(), 'email_attempts' => $r->email_attempts + 1]);

            return [$user->email, $event->title, $event->whenLabel(), $event->id];
        });
        if (! $delivery) {
            return;
        }
        try {
            $sent = Mail::raw("Tenés una actividad próxima: {$delivery[1]}\n{$delivery[2]} (Buenos Aires)\n".route('calendar.show', $delivery[3])."\nPodés revisar los avisos de este evento en EstuFlow.", fn ($m) => $m->to($delivery[0])->subject('Recordatorio · EstuFlow'));
            EventReminder::whereKey($this->reminderId)->update(['email_status' => $sent === null ? 'failed' : 'sent', 'email_sent_at' => $sent === null ? null : now()]);
        } catch (Throwable) {
            // SMTP may have accepted DATA before losing its response. Preserve uncertainty;
            // don't log addresses/content, don't claim delivery, don't retry ambiguous SMTP.
            EventReminder::whereKey($this->reminderId)->update(['email_status' => 'uncertain']);
        }
    }
}
