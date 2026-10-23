<?php

namespace Tests\Feature;

use App\Academic\CatalogImporter;
use App\Models\CourseRequirement;
use App\Models\CourseSpecialRule;
use App\Models\Institution;
use App\Models\PlanCourse;
use App\Models\StudyPlan;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CatalogTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, mixed> */
    private function catalog(): array
    {
        return json_decode(file_get_contents(database_path('catalogs/m03/catalog.json')), true, flags: JSON_THROW_ON_ERROR);
    }

    public function test_exact_catalog_can_be_reconstructed_and_sources_have_matching_hashes(): void
    {
        $data = $this->catalog();
        app(CatalogImporter::class)->file();
        $this->assertDatabaseCount('institutions', 1);
        $this->assertDatabaseCount('academic_careers', 3);
        $this->assertDatabaseCount('study_plans', 3);
        $this->assertDatabaseCount('plan_courses', 71);
        $this->assertDatabaseCount('academic_year_rules', 9);
        $this->assertDatabaseCount('course_special_rules', 3);
        $metadata = $data;
        unset($metadata['plans']);
        $this->assertEquals($metadata, Institution::sole()->metadata);
        foreach ($data['plans'] as $source) {
            $plan = StudyPlan::where('key', $source['plan_key'])->sole();
            $restored = $plan->source_data;
            $restored['courses'] = $plan->courses()->orderBy('catalog_order')->get()->pluck('source_data')->all();
            $this->assertEquals($source, $restored);
            $this->assertSame($source['summary']['calculated_total_hours'], (int) $plan->courses()->sum('hours'));
            $this->assertSame($source['source']['sha256'], hash_file('sha256', base_path('docs/sources/m03/'.$source['source']['filename'])));
        }
        $this->assertSame(356, PlanCourse::where('code', '342')->sole()->hours);
        $this->assertNull(StudyPlan::where('key', 'TSHSA-INGRESO-2026')->sole()->resolution);
        $this->assertStringContainsString('Seguirdad', PlanCourse::where('code', '604')->sole()->source_name);
        $this->assertStringContainsString('Seguridad', PlanCourse::where('code', '604')->sole()->display_name);
        $this->assertSame(0, CourseRequirement::whereColumn('plan_course_id', 'required_course_id')->count());
        foreach (CourseSpecialRule::all() as $rule) {
            $this->assertTrue($rule->source_data['self_reference_excluded']);
        }
    }

    public function test_import_is_idempotent_and_preserves_progress_and_ids(): void
    {
        $user = User::factory()->create();
        $enrollment = $this->configureAcademic($user);
        $row = $enrollment->progress->first();
        $row->update(['status' => 'approved', 'approval_method' => 'promotion']);
        $ids = PlanCourse::pluck('id', 'key')->all();
        app(CatalogImporter::class)->file();
        app(CatalogImporter::class)->file();
        $this->assertSame($ids, PlanCourse::pluck('id', 'key')->all());
        $this->assertSame('promotion', $row->fresh()->approval_method);
        $this->assertNotNull($enrollment->fresh()->completed_at);
        $this->assertDatabaseCount('plan_courses', 71);
    }

    public static function invalidCases(): array
    {
        return array_map(fn (string $name): array => [$name], ['duplicate', 'foreign', 'self', 'cycle', 'invalid_type', 'missing_column', 'invalid_year_rule', 'duplicate_order', 'numeric_string', 'wrong_summary']);
    }

    #[DataProvider('invalidCases')]
    public function test_invalid_input_cannot_partially_persist(string $case): void
    {
        $data = $this->catalog();
        $course = &$data['plans'][2]['courses'][0];
        match ($case) {
            'duplicate' => $data['plans'][2]['courses'][] = $course,
            'foreign' => $course['requirements']['to_enroll_requires_finals'] = ['294'],
            'self' => $course['requirements']['to_enroll_requires_finals'] = ['604'],
            'cycle' => [$course['requirements']['to_enroll_requires_finals'] = ['605'], $data['plans'][2]['courses'][1]['requirements']['to_take_final_requires_finals'] = ['604']],
            'invalid_type' => $course['field_type'] = 'X',
            'missing_column' => $course['requirements'] = [],
            'invalid_year_rule' => $data['plans'][2]['academic_year_entry_rules'][2]['previous_academic_year'] = 1,
            'duplicate_order' => $course['catalog_order'] = 2,
            'numeric_string' => $course['hours'] = '96',
            'wrong_summary' => $data['plans'][2]['summary']['calculated_total_hours'] = 999,
        };
        try {
            app(CatalogImporter::class)->import($data);
            $this->fail('Debe rechazar el catálogo inválido.');
        } catch (ValidationException) {
            $this->assertDatabaseCount('institutions', 0);
            $this->assertDatabaseCount('study_plans', 0);
            $this->assertDatabaseCount('plan_courses', 0);
        }
    }

    public function test_published_plan_is_immutable_and_new_plan_version_keeps_previous_progress(): void
    {
        $enrollment = $this->configureAcademic(User::factory()->create());
        $data = $this->catalog();
        $data['plans'][0]['courses'][0]['hours'] = 65;
        $data['plans'][0]['summary']['calculated_total_hours']++;
        $data['plans'][0]['summary']['by_academic_year'][0]['calculated_hours']++;
        try {
            app(CatalogImporter::class)->import($data);
            $this->fail('No puede sobrescribir un plan publicado.');
        } catch (ValidationException) {
            $this->assertSame(64, PlanCourse::where('code', '294')->sole()->hours);
        }
        $plan = $this->catalog()['plans'][0];
        $plan['plan_key'] .= '-REVISION-2';
        foreach ($plan['courses'] as &$course) {
            $course['key'] = $plan['plan_key'].':'.$course['code'];
        }
        unset($course);
        $data = $this->catalog();
        $data['plans'] = [$plan];
        app(CatalogImporter::class)->import($data);
        $this->assertDatabaseCount('study_plans', 4);
        $this->assertDatabaseCount('academic_careers', 3);
        $this->assertSame(23, $enrollment->fresh()->progress()->count());
        $this->assertDatabaseCount('plan_courses', 94);
    }

    public function test_database_refuses_cross_plan_dependencies(): void
    {
        app(CatalogImporter::class)->file();
        $one = PlanCourse::where('code', '294')->sole();
        $other = PlanCourse::where('code', '215')->sole();
        $this->expectException(QueryException::class);
        DB::transaction(fn () => CourseRequirement::create(['study_plan_id' => $one->study_plan_id, 'plan_course_id' => $one->id, 'required_course_id' => $other->id, 'kind' => 'to_enroll_requires_finals']));
    }
}
