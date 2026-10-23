<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** @property array<string, mixed> $source_data */
class StudyPlan extends Model
{
    protected $table = 'study_plans';

    protected $guarded = [];

    public $timestamps = false;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['source_data' => 'array'];
    }

    /** @return BelongsTo<AcademicCareer, $this> */
    public function career(): BelongsTo
    {
        return $this->belongsTo(AcademicCareer::class, 'academic_career_id');
    }

    /** @return HasMany<PlanCourse, $this> */
    public function courses(): HasMany
    {
        return $this->hasMany(PlanCourse::class, 'study_plan_id');
    }

    /** @return HasMany<AcademicYearRule, $this> */
    public function yearRules(): HasMany
    {
        return $this->hasMany(AcademicYearRule::class, 'study_plan_id');
    }
}
