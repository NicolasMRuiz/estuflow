<?php

namespace App\Calendar;

use App\Models\AcademicEnrollment;
use App\Models\CalendarEvent;
use App\Models\EventReminder;
use App\Models\PlanCourse;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class Events
{
    public const TYPES = ['exam' => 'Examen', 'assignment' => 'Entrega', 'class' => 'Clase', 'other' => 'Otro'];

    public const TIMED = ['day' => '24 horas antes', 'hour' => '1 hora antes'];

    public const ALL_DAY = ['previous_morning' => 'El día anterior a las 09:00', 'same_morning' => 'Ese día a las 09:00'];

    public function own(User $user, int $id): CalendarEvent
    {
        return CalendarEvent::where('user_id', $user->id)->findOrFail($id);
    }

    /** @param array<string, mixed> $input */
    public function save(User $user, array $input, ?int $id = null): CalendarEvent
    {
        return DB::transaction(function () use ($user, $input, $id): CalendarEvent {
            User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $event = $id ? CalendarEvent::where('user_id', $user->id)->lockForUpdate()->findOrFail($id) : new CalendarEvent(['user_id' => $user->id]);
            $v = Validator::make($input, [
                'title' => ['required', 'string', 'max:160'], 'type' => ['required', Rule::in(array_keys(self::TYPES))], 'description' => ['nullable', 'string', 'max:4000'], 'place' => ['nullable', 'string', 'max:200'],
                'all_day' => ['required', 'boolean'], 'start_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:1900-01-01', 'before_or_equal:2199-12-31'], 'end_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:start_date', 'before_or_equal:2199-12-31'],
                'start_time' => ['nullable', 'required_if:all_day,0', 'date_format:H:i'], 'end_time' => ['nullable', 'date_format:H:i'], 'plan_course_id' => ['nullable', 'integer'],
                'reminder_options' => ['sometimes', 'array', 'max:2'], 'reminder_options.*' => ['string', 'distinct', Rule::in(array_keys($input['all_day'] ?? false ? self::ALL_DAY : self::TIMED))], 'email_reminders' => ['sometimes', 'boolean'],
                'user_id' => ['prohibited'], 'schedule_version' => ['prohibited'],
            ], ['start_time.required_if' => 'Indicá la hora de inicio.', 'end_date.after_or_equal' => 'La fecha final debe ser igual o posterior al inicio.'])->validate();
            $courseId = empty($v['plan_course_id']) ? null : (int) $v['plan_course_id'];
            if ($courseId) {
                $course = PlanCourse::find($courseId);
                $owned = $course && AcademicEnrollment::where('user_id', $user->id)->where('study_plan_id', $course->study_plan_id)->whereNotNull('completed_at')->where(fn ($q) => $q->where('is_active', true)->when($event->plan_course_id === $courseId, fn ($q) => $q->orWhere('study_plan_id', $course->study_plan_id)))->exists();
                if (! $owned) {
                    throw ValidationException::withMessages(['plan_course_id' => 'Elegí una materia de tu carrera activa. Podés conservar la asociación anterior de este evento.']);
                }
            }
            $allDay = (bool) $v['all_day'];
            $endDate = $v['end_date'] ?? $v['start_date'];
            $starts = $allDay ? null : CarbonImmutable::createFromFormat('!Y-m-d H:i', $v['start_date'].' '.$v['start_time'], config('m04.timezone'))->utc();
            $ends = $allDay || empty($v['end_time']) ? null : CarbonImmutable::createFromFormat('!Y-m-d H:i', $endDate.' '.$v['end_time'], config('m04.timezone'))->utc();
            if ($ends && $ends->lte($starts)) {
                throw ValidationException::withMessages(['end_time' => 'El fin debe ser posterior al inicio.']);
            }
            if (! $allDay && ! $ends && $endDate !== $v['start_date']) {
                throw ValidationException::withMessages(['end_time' => 'Indicá la hora final o dejá la fecha final vacía.']);
            }
            $options = $v['reminder_options'] ?? [];
            sort($options);
            $email = (bool) ($v['email_reminders'] ?? false);
            if ($email && ! $user->hasVerifiedEmail()) {
                throw ValidationException::withMessages(['email_reminders' => 'Verificá tu email antes de activar este canal.']);
            }
            $event->fill(['title' => $v['title'], 'type' => $v['type'], 'description' => $v['description'] ?? null, 'place' => $v['place'] ?? null, 'plan_course_id' => $courseId, 'all_day' => $allDay, 'start_date' => $v['start_date'], 'end_date' => $allDay || $ends ? $endDate : $v['start_date'], 'starts_at' => $starts, 'ends_at' => $ends, 'reminder_options' => $options, 'email_reminders' => $email]);
            $changed = ! $event->exists || $event->isDirty(['all_day', 'start_date', 'end_date', 'starts_at', 'ends_at', 'reminder_options', 'email_reminders']);
            if ($event->exists && $changed) {
                $event->schedule_version++;
            }
            $event->save();
            if ($changed) {
                EventReminder::where('calendar_event_id', $event->id)->where('internal_status', 'pending')->update(['internal_status' => 'cancelled']);
                EventReminder::where('calendar_event_id', $event->id)->whereIn('email_status', ['pending', 'queued'])->update(['email_status' => 'cancelled']);
                foreach ($options as $offset) {
                    $due = $this->due($event, $offset);
                    if ($due->lt(now()) || $event->cutoff()->lte(now())) {
                        continue;
                    }
                    EventReminder::create(['calendar_event_id' => $event->id, 'user_id' => $user->id, 'version' => $event->schedule_version, 'offset' => $offset, 'due_at' => $due, 'useful_until' => $event->cutoff(), 'email_status' => $email ? 'pending' : 'off']);
                }
            }

            return $event;
        });
    }

    public function due(CalendarEvent $event, string $offset): CarbonImmutable
    {
        if ($event->all_day) {
            $morning = CarbonImmutable::parse($event->start_date->toDateString().' 09:00', config('m04.timezone'));

            return ($offset === 'previous_morning' ? $morning->subDay() : $morning)->utc();
        }

        return $offset === 'day' ? $event->starts_at->subDay() : $event->starts_at->subHour();
    }

    public function delete(User $user, int $id): void
    {
        DB::transaction(function () use ($user, $id): void {
            $event = CalendarEvent::where('user_id', $user->id)->lockForUpdate()->findOrFail($id);
            $event->increment('schedule_version');
            $event->delete();
            EventReminder::where('calendar_event_id', $id)->where('internal_status', 'pending')->update(['internal_status' => 'cancelled']);
            EventReminder::where('calendar_event_id', $id)->whereIn('email_status', ['pending', 'queued'])->update(['email_status' => 'cancelled']);
        });
    }
}
