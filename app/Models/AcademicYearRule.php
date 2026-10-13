<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** @property array<string, mixed> $source_data */
class AcademicYearRule extends Model
{
    protected $table = 'academic_year_rules';

    protected $guarded = [];

    public $timestamps = false;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['source_data' => 'array'];
    }
}
