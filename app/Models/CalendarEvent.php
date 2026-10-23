<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property array<int, string> $reminder_options
 * @property CarbonImmutable|null $starts_at
 * @property CarbonImmutable|null $ends_at
 * @property CarbonImmutable $start_date
 * @property CarbonImmutable $end_date
 */
class CalendarEvent extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $attributes = ['schedule_version' => 1, 'all_day' => false, 'email_reminders' => false, 'reminder_options' => '[]'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['all_day' => 'boolean', 'email_reminders' => 'boolean', 'reminder_options' => 'array', 'starts_at' => 'immutable_datetime', 'ends_at' => 'immutable_datetime', 'start_date' => 'immutable_date', 'end_date' => 'immutable_date'];
    }

    /** @return BelongsTo<PlanCourse, $this> */
    public function course(): BelongsTo
    {
        return $this->belongsTo(PlanCourse::class, 'plan_course_id');
    }

    public function cutoff(): CarbonImmutable
    {
        return $this->all_day ? CarbonImmutable::parse($this->end_date->toDateString(), config('m04.timezone'))->endOfDay()->utc() : ($this->ends_at ?? $this->starts_at);
    }

    public function whenLabel(): string
    {
        return $this->all_day ? $this->start_date->format('d/m/Y').' · Día completo'.($this->end_date->ne($this->start_date) ? ' hasta '.$this->end_date->format('d/m/Y') : '') : $this->starts_at->setTimezone(config('m04.timezone'))->format('d/m/Y · H:i').($this->ends_at ? ' → '.$this->ends_at->setTimezone(config('m04.timezone'))->format('d/m H:i') : '');
    }
}
