<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseRequirement extends Model
{
    protected $table = 'course_requirements';

    protected $guarded = [];

    public $timestamps = false;

    /** @return BelongsTo<PlanCourse, $this> */
    public function requiredCourse(): BelongsTo
    {
        return $this->belongsTo(PlanCourse::class, 'required_course_id');
    }
}
