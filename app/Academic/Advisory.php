<?php

namespace App\Academic;

use App\Models\AcademicEnrollment;
use App\Models\CourseProgress;
use App\Models\PlanCourse;
use Illuminate\Support\Collection;

class Advisory
{
    public const LABELS = ['complete' => 'Completo según lo declarado', 'missing' => 'Faltan condiciones declaradas', 'uncertain' => 'Por confirmar'];

    /** @var Collection<int, CourseProgress> */
    private Collection $rows;

    public function __construct(private AcademicEnrollment $enrollment)
    {
        $this->rows = $enrollment->progress->keyBy('plan_course_id');
    }

    public function evidence(PlanCourse $course, bool $final): string
    {
        $row = $this->rows->get($course->id);
        if ($final) {
            return $row?->status !== 'approved' ? 'missing' : ($row->approval_method === 'final' ? 'complete' : 'uncertain');
        }

        return in_array($row?->status, ['approved', 'regularized'], true) ? 'complete' : 'missing';
    }

    /** @param list<string> $states */
    public static function combine(array $states): string
    {
        return in_array('missing', $states, true) ? 'missing' : (in_array('uncertain', $states, true) ? 'uncertain' : 'complete');
    }

    /** @return array<string, mixed> */
    public function year(int $year): array
    {
        $rule = $this->enrollment->plan->yearRules->firstWhere('academic_year', $year)->source_data;
        if ($rule['kind'] === 'administrative_conditions') {
            return ['state' => 'uncertain', 'text' => $rule['source_text'], 'observed' => 'Documentación, ingreso y libreta: información administrativa sin constancia institucional en EstuFlow.'];
        }
        $previous = $this->enrollment->plan->courses->where('academic_year', $rule['previous_academic_year']);
        $definite = $possible = $specificDefinite = $specificPossible = 0;
        foreach ($previous as $course) {
            $state = $this->evidence($course, true);
            if ($state === 'complete') {
                $definite++;
                $specificDefinite += $course->field_type === 'E' ? 1 : 0;
            }
            if ($state !== 'missing') {
                $possible++;
                $specificPossible += $course->field_type === 'E' ? 1 : 0;
            }
        }
        $state = $definite >= $rule['minimum_final_passed_courses'] && $specificDefinite >= $rule['minimum_specific_final_passed_courses'] ? 'complete' : ($possible >= $rule['minimum_final_passed_courses'] && $specificPossible >= $rule['minimum_specific_final_passed_courses'] ? 'uncertain' : 'missing');

        return ['state' => $state, 'text' => $rule['source_text'], 'observed' => "Del año anterior: {$definite} finales declarados, {$specificDefinite} de formación específica E. Se evalúa a la fecha de inscripción del calendario académico; fecha no provista.", 'ambiguous' => $possible - $definite];
    }

    /** @return array<string, mixed> */
    public function course(PlanCourse $course): array
    {
        $columns = [];
        foreach (CatalogImporter::KINDS as $kind) {
            $conditions = [];
            foreach ($course->requirements->where('kind', $kind) as $requirement) {
                $required = $requirement->requiredCourse;
                $conditions[] = ['course' => $required, 'state' => $this->evidence($required, $kind !== 'to_enroll_requires_coursework')];
            }
            $columns[$kind] = ['state' => self::combine(array_column($conditions, 'state')), 'conditions' => $conditions];
        }
        $year = $this->year($course->academic_year);
        $specials = [];
        foreach ($course->specialRules as $special) {
            $others = $this->enrollment->plan->courses->reject(fn (PlanCourse $other): bool => $other->id === $course->id);
            $finals = $others->filter(fn (PlanCourse $other): bool => $this->evidence($other, true) === 'complete')->count();
            $ambiguous = $others->filter(fn (PlanCourse $other): bool => $this->evidence($other, true) === 'uncertain')->count();
            $specials[] = ['state' => 'uncertain', 'source' => $special->source_data, 'observed' => $finals.' de '.$others->count().' finales declarados; '.$ambiguous.' aprobaciones con método por confirmar. Se excluye esta práctica.'];
        }

        return ['columns' => $columns, 'year' => $year, 'specials' => $specials, 'enrollment' => self::combine([$year['state'], $columns['to_enroll_requires_coursework']['state'], $columns['to_enroll_requires_finals']['state']]), 'final' => self::combine([$columns['to_take_final_requires_finals']['state'], ...array_column($specials, 'state')])];
    }
}
