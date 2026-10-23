<?php

namespace App\Academic;

use App\Models\StudyPlan;
use Illuminate\Support\Str;

class Presentation
{
    public const PLURALS = ['pending' => 'pendientes', 'in_progress' => 'cursando', 'regularized' => 'con final pendiente', 'approved' => 'aprobadas'];

    public static function career(StudyPlan $plan): string
    {
        return Str::after($plan->career->name, 'Tecnicatura Superior en ');
    }

    public static function plan(StudyPlan $plan): string
    {
        return 'Plan para ingresos desde '.$plan->entry_cohort_from;
    }

    public static function countLabel(string $state, int $count): string
    {
        return $count === 1 ? ['pending' => 'pendiente', 'in_progress' => 'cursando', 'regularized' => 'con final pendiente', 'approved' => 'aprobada'][$state] : self::PLURALS[$state];
    }
}
