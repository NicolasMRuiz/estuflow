<?php

namespace App\Academic;

use App\Models\AcademicCareer;
use App\Models\AcademicYearRule;
use App\Models\CourseRequirement;
use App\Models\CourseSpecialRule;
use App\Models\Institution;
use App\Models\PlanCourse;
use App\Models\StudyPlan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CatalogImporter
{
    public const KINDS = ['to_take_final_requires_finals', 'to_enroll_requires_coursework', 'to_enroll_requires_finals'];

    public function file(?string $path = null): int
    {
        $data = json_decode(file_get_contents($path ?? database_path('catalogs/m03/catalog.json')), true, flags: JSON_THROW_ON_ERROR);
        if (! is_array($data)) {
            $this->invalid('El catálogo debe ser un objeto.');
        }

        return $this->import($data);
    }

    /** @param array<string, mixed> $data */
    public function import(array $data): int
    {
        $this->validate($data);

        return DB::transaction(function () use ($data): int {
            // Serializes imports, including the first import, on PostgreSQL.
            DB::select('SELECT pg_advisory_xact_lock(197031)');
            foreach ($data['plans'] as $plan) {
                $existing = StudyPlan::where('key', $plan['plan_key'])->first();
                if ($existing && $existing->content_hash !== $this->hash($plan)) {
                    $this->invalid('Un plan publicado es inmutable. Usá una nueva plan_key para otra versión.');
                }
            }
            $metadata = $data;
            unset($metadata['plans']);
            $institution = Institution::firstOrCreate(['key' => 'beltran-isft-197'], ['name' => $data['institution']['name'], 'metadata' => $metadata]);
            foreach ($data['plans'] as $plan) {
                if (StudyPlan::where('key', $plan['plan_key'])->exists()) {
                    continue;
                }
                $career = AcademicCareer::firstOrCreate(['institution_id' => $institution->id, 'key' => $plan['career_key']], ['name' => $plan['career_name']]);
                $source = $plan;
                unset($source['courses']);
                $stored = StudyPlan::create(['academic_career_id' => $career->id, 'key' => $plan['plan_key'], 'resolution' => $plan['resolution'], 'entry_cohort_from' => $plan['entry_cohort_from'], 'entry_cohort_to' => $plan['entry_cohort_to'], 'source_data' => $source, 'content_hash' => $this->hash($plan)]);
                $ids = [];
                foreach ($plan['courses'] as $course) {
                    $row = PlanCourse::create(['study_plan_id' => $stored->id, 'key' => $course['key'], 'code' => $course['code'], 'academic_year' => $course['academic_year'], 'catalog_order' => $course['catalog_order'], 'source_name' => $course['source_name'], 'display_name' => $course['display_name'], 'field_type' => $course['field_type'], 'accreditation_instance' => $course['accreditation_instance'], 'hours' => $course['hours'], 'free_exam_allowed' => $course['free_exam_allowed_by_field_type'], 'source_data' => $course]);
                    $ids[$course['code']] = $row->id;
                }
                foreach ($plan['courses'] as $course) {
                    foreach (self::KINDS as $kind) {
                        foreach ($course['requirements'][$kind] as $code) {
                            CourseRequirement::create(['study_plan_id' => $stored->id, 'plan_course_id' => $ids[$course['code']], 'required_course_id' => $ids[$code], 'kind' => $kind]);
                        }
                    }
                    foreach ($course['special_rules'] as $rule) {
                        CourseSpecialRule::create(['plan_course_id' => $ids[$course['code']], 'kind' => $rule['kind'], 'source_data' => $rule]);
                    }
                }
                foreach ($plan['academic_year_entry_rules'] as $rule) {
                    AcademicYearRule::create(['study_plan_id' => $stored->id, 'academic_year' => $rule['academic_year'], 'kind' => $rule['kind'], 'source_data' => $rule]);
                }
            }

            return StudyPlan::count();
        });
    }

    /** @param array<string, mixed> $data */
    private function validate(array $data): void
    {
        Validator::make($data, [
            'schema_version' => ['required', Rule::in(['1.0.0'])],
            'prepared_on' => ['required', 'date_format:Y-m-d'],
            'institution' => ['required', 'array:name,institute,location'],
            'institution.*' => ['required', 'string'],
            'scope' => ['required', 'array'], 'source_semantics' => ['required', 'array'],
            'plans' => ['required', 'array', 'min:1'],
            'plans.*.plan_key' => ['required', 'string', 'max:200', 'distinct:strict'],
            'plans.*.career_key' => ['required', 'string'], 'plans.*.career_name' => ['required', 'string', 'max:255'],
            'plans.*.resolution' => ['present', 'nullable', 'string'],
            'plans.*.entry_cohort_from' => ['required', 'integer', 'min:1900', 'max:9999'],
            'plans.*.entry_cohort_to' => ['present', 'nullable', 'integer', 'min:1900', 'max:9999'],
            'plans.*.entry_cohort_to_status' => ['required', 'string'],
            'plans.*.duration_in_academic_years' => ['required', 'integer', 'in:3'],
            'plans.*.source.filename' => ['required', 'string'],
            'plans.*.source.filename_year' => ['required', 'integer'],
            'plans.*.source.sha256' => ['required', 'regex:/^[a-f0-9]{64}$/'],
            'plans.*.source.page_count' => ['required', 'integer', 'min:1'],
            'plans.*.summary' => ['required', 'array'],
            'plans.*.academic_year_entry_rules' => ['required', 'array', 'size:3'],
            'plans.*.academic_year_entry_rules.*.academic_year' => ['required', 'integer', 'between:1,3'],
            'plans.*.academic_year_entry_rules.*.source_page' => ['required', 'integer', 'min:1'],
            'plans.*.academic_year_entry_rules.*.source_text' => ['required', 'string'],
            'plans.*.academic_year_entry_rules.*.evaluation' => ['required', 'string'],
            'plans.*.academic_year_entry_rules.*.kind' => ['required', Rule::in(['administrative_conditions', 'previous_year_final_passes'])],
            'plans.*.courses' => ['required', 'array', 'min:1'],
            'plans.*.courses.*.key' => ['required', 'string', 'max:255'],
            'plans.*.courses.*.code' => ['required', 'string', 'max:40'],
            'plans.*.courses.*.academic_year' => ['required', 'integer', 'between:1,3'],
            'plans.*.courses.*.catalog_order' => ['required', 'integer', 'min:1'],
            'plans.*.courses.*.source_name' => ['required', 'string', 'max:255'],
            'plans.*.courses.*.display_name' => ['required', 'string', 'max:255'],
            'plans.*.courses.*.field_type' => ['required', Rule::in(['G', 'E'])],
            'plans.*.courses.*.accreditation_instance' => ['required', Rule::in(['R', 'P'])],
            'plans.*.courses.*.hours' => ['required', 'integer', 'between:1,65535'],
            'plans.*.courses.*.free_exam_allowed_by_field_type' => ['present', 'boolean'],
            'plans.*.courses.*.editorial_corrections' => ['present', 'array'],
            'plans.*.courses.*.source_page' => ['required', 'integer', 'min:1'],
            'plans.*.courses.*.source_row' => ['required', 'integer', 'min:1'],
            'plans.*.courses.*.requirements' => ['required', 'array:'.implode(',', self::KINDS)],
            'plans.*.courses.*.requirements_source_cells' => ['required', 'array:'.implode(',', self::KINDS)],
            'plans.*.courses.*.requirements_source_cells.*' => ['present', 'string'],
            'plans.*.courses.*.special_rules' => ['present', 'array'],
            'plans.*.courses.*.special_rules.*.kind' => ['required', 'in:all_other_plan_courses_final_passed'],
            'plans.*.courses.*.special_rules.*.source_column' => ['required', 'in:to_take_final_requires_finals'],
            'plans.*.courses.*.special_rules.*.source_cell' => ['required', 'string'],
            'plans.*.courses.*.special_rules.*.source_note' => ['required', 'string'],
            'plans.*.courses.*.special_rules.*.scope' => ['required', 'in:all_courses_in_this_plan_except_this_course'],
            'plans.*.courses.*.special_rules.*.self_reference_excluded' => ['required', 'accepted'],
            'plans.*.courses.*.special_rules.*.application_moment' => ['required', 'string'],
            'plans.*.courses.*.special_rules.*.promotion_equivalence' => ['required', 'in:not_defined_by_source'],
            'plans.*.courses.*.special_rules.*.evaluation' => ['required', 'string'],
            ...collect(self::KINDS)->mapWithKeys(fn (string $kind): array => ['plans.*.courses.*.requirements.'.$kind => ['present', 'array'], 'plans.*.courses.*.requirements.'.$kind.'.*' => ['required', 'string']])->all(),
        ])->validate();
        foreach ($data['plans'] as $plan) {
            foreach (['entry_cohort_from', 'duration_in_academic_years'] as $field) {
                if (! is_int($plan[$field])) {
                    $this->invalid('Los números del catálogo deben ser enteros JSON.');
                }
            }
            if ($plan['entry_cohort_to'] !== null && (! is_int($plan['entry_cohort_to']) || $plan['entry_cohort_to'] < $plan['entry_cohort_from'])) {
                $this->invalid('Intervalo de ingreso inválido.');
            }
            $codes = array_column($plan['courses'], 'code');
            if (count(array_unique($codes)) !== count($codes) || count(array_unique(array_column($plan['courses'], 'catalog_order'))) !== count($codes)) {
                $this->invalid('Códigos y orden deben ser únicos dentro del plan.');
            }
            $years = array_column($plan['academic_year_entry_rules'], 'academic_year');
            sort($years);
            if ($years !== [1, 2, 3]) {
                $this->invalid('Se requiere una regla para cada año.');
            }
            foreach ($plan['academic_year_entry_rules'] as $rule) {
                if (! is_int($rule['academic_year']) || ! is_int($rule['source_page']) || $rule['source_page'] > $plan['source']['page_count']) {
                    $this->invalid('Año o página inválidos en regla general.');
                }
                if ($rule['academic_year'] === 1) {
                    Validator::make($rule, ['kind' => ['in:administrative_conditions'], 'conditions' => ['required', 'array', 'min:1'], 'conditions.*' => ['required', 'string']])->validate();
                } else {
                    Validator::make($rule, ['kind' => ['in:previous_year_final_passes'], 'previous_academic_year' => ['required', 'integer', 'in:'.($rule['academic_year'] - 1)], 'minimum_final_passed_courses' => ['required', 'integer', 'min:1'], 'minimum_specific_final_passed_courses' => ['required', 'integer', 'min:1'], 'measurement_time' => ['required', 'string'], 'promotion_equivalence' => ['required', 'in:not_defined_by_source']])->validate();
                }
            }
            $graph = [];
            foreach ($plan['courses'] as $course) {
                foreach (['academic_year', 'catalog_order', 'hours', 'source_page', 'source_row'] as $field) {
                    if (! is_int($course[$field])) {
                        $this->invalid('Los números de materias deben ser enteros JSON.');
                    }
                }
                if ($course['source_page'] > $plan['source']['page_count'] || count(array_unique(array_column($course['special_rules'], 'kind'))) !== count($course['special_rules'])) {
                    $this->invalid('Página inválida o regla especial duplicada.');
                }
                if ($course['key'] !== $plan['plan_key'].':'.$course['code'] || $course['free_exam_allowed_by_field_type'] !== ($course['field_type'] === 'G')) {
                    $this->invalid('Clave o modalidad libre incoherente.');
                }
                $graph[$course['code']] = [];
                foreach (self::KINDS as $kind) {
                    $references = $course['requirements'][$kind];
                    if (count(array_unique($references)) !== count($references)) {
                        $this->invalid('Correlativa duplicada.');
                    }
                    foreach ($references as $code) {
                        if ($code === $course['code'] || ! in_array($code, $codes, true)) {
                            $this->invalid('Correlativa ajena al plan o autorreferencia.');
                        }
                        $graph[$course['code']][] = $code;
                    }
                }
            }
            $visited = [];
            $visiting = [];
            foreach ($codes as $code) {
                $this->visit($code, $graph, $visited, $visiting);
            }
            if (($plan['summary']['course_count'] ?? null) !== count($codes) || ($plan['summary']['calculated_total_hours'] ?? null) !== array_sum(array_column($plan['courses'], 'hours'))) {
                $this->invalid('El resumen no coincide con las materias y horas.');
            }
            foreach ([1, 2, 3] as $year) {
                $courses = array_filter($plan['courses'], fn (array $course): bool => $course['academic_year'] === $year);
                $summary = null;
                foreach ($plan['summary']['by_academic_year'] ?? [] as $yearSummary) {
                    if (($yearSummary['academic_year'] ?? null) === $year) {
                        $summary = $yearSummary;
                    }
                }
                if (($summary['course_count'] ?? null) !== count($courses) || ($summary['calculated_hours'] ?? null) !== array_sum(array_column($courses, 'hours'))) {
                    $this->invalid('El resumen por año no coincide.');
                }
            }
        }
    }

    /** @param array<string, mixed> $plan */
    private function hash(array $plan): string
    {
        return hash('sha256', json_encode($plan, JSON_THROW_ON_ERROR));
    }

    /**
     * @param  array<string, list<string>>  $graph
     * @param  array<string, bool>  $visited
     * @param  array<string, bool>  $visiting
     */
    private function visit(string $code, array $graph, array &$visited, array &$visiting): void
    {
        if ($visiting[$code] ?? false) {
            $this->invalid('Ciclo en las correlativas explícitas.');
        }
        if ($visited[$code] ?? false) {
            return;
        }
        $visiting[$code] = true;
        foreach ($graph[$code] as $next) {
            $this->visit($next, $graph, $visited, $visiting);
        }
        $visiting[$code] = false;
        $visited[$code] = true;
    }

    private function invalid(string $message): never
    {
        throw ValidationException::withMessages(['catalog' => $message]);
    }
}
