<?php

namespace App\Calendar;

use App\Jobs\SendReminderEmail;
use App\Models\CalendarEvent;
use App\Models\EventReminder;
use App\Models\InboxNotice;
use Illuminate\Support\Facades\DB;

class Reminders
{
    public function dispatchDue(): void
    {
        // A killed SMTP process is ambiguous: never automatically resend that attempt.
        EventReminder::where('email_status', 'sending')->where('email_claimed_at', '<', now()->subMinutes(5))->update(['email_status' => 'uncertain']);
        EventReminder::where('email_status', 'queued')->where('email_claimed_at', '<', now()->subMinutes(5))->update(['email_status' => 'pending']);
        EventReminder::where('due_at', '<=', now())->where(fn ($q) => $q->where('internal_status', 'pending')->orWhere('email_status', 'pending'))->orderBy('id')->chunkById(100, function ($rows): void {
            foreach ($rows as $row) {
                DB::transaction(function () use ($row): void {
                    $event = CalendarEvent::whereKey($row->calendar_event_id)->lockForUpdate()->first();
                    $r = EventReminder::whereKey($row->id)->lockForUpdate()->firstOrFail();
                    if (! $event || $event->user_id !== $r->user_id || $event->schedule_version !== $r->version || $r->useful_until->lte(now())) {
                        $r->update(['internal_status' => $r->internal_status === 'pending' ? 'expired' : $r->internal_status, 'email_status' => in_array($r->email_status, ['pending', 'queued']) ? 'expired' : $r->email_status]);

                        return;
                    }
                    if ($r->internal_status === 'pending') {
                        InboxNotice::firstOrCreate(['user_id' => $r->user_id, 'dedupe_key' => 'reminder:'.$r->id], ['type' => 'reminder', 'title' => $event->title, 'body' => $event->whenLabel(), 'target_id' => $event->id]);
                        $r->internal_status = 'delivered';
                    }
                    if ($r->email_status === 'pending') {
                        $r->email_status = 'queued';
                        $r->email_claimed_at = now();
                        SendReminderEmail::dispatch($r->id)->afterCommit();
                    }
                    $r->save();
                });
            }
        });
    }
}
