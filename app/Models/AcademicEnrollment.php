<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcademicEnrollment extends Model
{
    protected $table = 'academic_enrollments';

    protected $guarded = [];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'is_candidate' => 'boolean', 'completed_at' => 'datetime'];
    }

    /** @return BelongsTo<StudyPlan, $this> */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(StudyPlan::class, 'study_plan_id');
    }

    /** @return HasMany<CourseProgress, $this> */
    public function progress(): HasMany
    {
        return $this->hasMany(CourseProgress::class, 'academic_enrollment_id');
    }
}
