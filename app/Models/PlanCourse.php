<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** @property array<string, mixed> $source_data */
class PlanCourse extends Model
{
    protected $table = 'plan_courses';

    protected $guarded = [];

    public $timestamps = false;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['source_data' => 'array', 'free_exam_allowed' => 'boolean'];
    }

    /** @return BelongsTo<StudyPlan, $this> */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(StudyPlan::class, 'study_plan_id');
    }

    /** @return HasMany<CourseRequirement, $this> */
    public function requirements(): HasMany
    {
        return $this->hasMany(CourseRequirement::class, 'plan_course_id');
    }

    /** @return HasMany<CourseSpecialRule, $this> */
    public function specialRules(): HasMany
    {
        return $this->hasMany(CourseSpecialRule::class, 'plan_course_id');
    }
}
