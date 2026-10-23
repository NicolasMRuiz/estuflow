<?php

namespace Tests\Feature;

use App\Academic\AcademicProgress;
use App\Academic\Advisory;
use App\Academic\CatalogImporter;
use App\Livewire\CareerBrowser;
use App\Livewire\CourseEditor;
use App\Livewire\Dashboard;
use App\Models\AcademicEnrollment;
use App\Models\PlanCourse;
use App\Models\StudyPlan;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use Tests\TestCase;

class AcademicTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        app(CatalogImporter::class)->file();
    }

    private function plan(string $prefix = 'TSAS'): StudyPlan
    {
        return StudyPlan::where('key', 'like', $prefix.'%')->sole();
    }

    private function course(string $code): PlanCourse
    {
        return PlanCourse::where('code', $code)->sole();
    }

    private function setState(User $user, string $code, string $status, string $method = 'unspecified'): void
    {
        app(AcademicProgress::class)->save($user, [$this->course($code)->id => ['status' => $status, 'approval_method' => $method]]);
    }

    private function confirmSelection(User $user): void
    {
        $this->get(route('academic.summary'))->assertOk();
        $this->post(route('academic.finish'), ['candidate_id' => app(AcademicProgress::class)->candidate($user)->id])->assertRedirect('/dashboard');
    }

    public function test_existing_verified_account_requires_explicit_choice_then_review_before_dashboard(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/dashboard')->assertRedirect(route('academic.choose'));
        $this->get(route('academic.choose'))->assertOk()->assertSee('Enfermería')->assertSee('2020')->assertDontSee('checked', false);
        $this->post(route('academic.select'), [])->assertSessionHasErrors('plan_id');
        $this->post(route('academic.select'), ['plan_id' => $this->plan()->id])->assertRedirect(route('academic.draft'));
        $this->get('/dashboard')->assertRedirect(route('academic.draft'));
        $this->assertNull(app(AcademicProgress::class)->active($user)->completed_at);
        $this->get(route('academic.summary'))->assertSee('Pendiente')->assertSee('Revisá tu recorrido');
        $this->post(route('academic.finish'))->assertSessionHasErrors('candidate_id');
        $this->confirmSelection($user);
        $this->get('/dashboard')->assertOk()->assertSee('0 de 23 materias aprobadas')->assertSee('0 %');
    }

    public function test_local_registration_and_verification_prioritize_email_then_academic_configuration(): void
    {
        $this->post('/registro', ['name' => 'Persona Prueba', 'email' => 'academic@example.test', 'password' => 'clave-ficticia-segura', 'password_confirmation' => 'clave-ficticia-segura'])->assertRedirect('/verificar-email');
        foreach (['/dashboard', '/configuracion-academica', '/mi-carrera'] as $path) {
            $this->get($path)->assertRedirect('/verificar-email');
        }
        $user = User::sole();
        $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(10), ['id' => $user->id, 'hash' => sha1($user->email)]);
        $this->get($url)->assertRedirect('/dashboard');
        $this->actingAs($user->fresh());
        $this->get('/dashboard')->assertRedirect(route('academic.choose'));
        $this->get('/metodos-de-acceso')->assertRedirect(route('profile.index'));
        $this->get('/mi-perfil')->assertOk();
        $this->post('/logout')->assertRedirect('/login');
    }

    public function test_draft_survives_logout_and_login_and_finishing_does_not_require_approvals(): void
    {
        $user = User::factory()->create(['password' => 'clave-ficticia-segura']);
        $this->actingAs($user)->post(route('academic.select'), ['plan_id' => $this->plan()->id]);
        $id = $this->course('294')->id;
        $this->post(route('academic.save'), ['entries' => [$id => ['status' => 'approved', 'approval_method' => 'promotion']]])->assertSessionHas('status');
        $this->post('/logout');
        $this->post('/login', ['email' => $user->email, 'password' => 'clave-ficticia-segura'])->assertRedirect('/dashboard');
        $this->get('/dashboard')->assertRedirect(route('academic.draft'));
        $this->get(route('academic.draft'))->assertSee('value="promotion" selected', false);
        $this->confirmSelection($user);
        $this->get('/dashboard')->assertSee('1 de 23 materias aprobadas')->assertSee('4 %');
    }

    public function test_foreign_course_invalid_methods_and_forged_account_ids_are_rejected_atomically(): void
    {
        $user = User::factory()->create();
        $this->configureAcademic($user);
        $this->actingAs($user);
        $id = $this->course('294')->id;
        $foreign = $this->course('215')->id;
        $this->post(route('academic.save'), ['entries' => [$id => ['status' => 'approved'], $foreign => ['status' => 'approved']]])->assertForbidden();
        $this->assertDatabaseHas('course_progress', ['plan_course_id' => $id, 'status' => 'pending']);
        $this->post(route('career.update', $foreign), ['status' => 'approved'])->assertNotFound();
        $this->post(route('career.update', $this->course('296')->id), ['status' => 'approved', 'approval_method' => 'promotion'])->assertSessionHasErrors();
        $this->post(route('career.update', $id), ['status' => 'invented'])->assertSessionHasErrors();
        $this->post(route('career.update', $id), ['status' => 'approved', 'user_id' => 999])->assertSessionHasErrors('user_id');
        $this->post(route('academic.select'), ['plan_id' => $this->plan('TECEN')->id])->assertRedirect(route('academic.draft'));
        $this->assertSame($this->plan()->id, app(AcademicProgress::class)->active($user)->study_plan_id);
    }

    public function test_livewire_owns_course_and_rechecks_account_plan_and_verification_on_update(): void
    {
        $one = User::factory()->create();
        $two = User::factory()->create();
        $this->configureAcademic($one);
        $this->configureAcademic($two);
        $id = $this->course('294')->id;
        Livewire::actingAs($one)->test(CourseEditor::class, ['courseId' => $id])->set('status', 'approved')->set('approvalMethod', 'promotion')->call('save')->assertRedirect(route('career.detail', $id));
        $this->assertSame('pending', app(AcademicProgress::class)->active($two)->progress->firstWhere('plan_course_id', $id)->status);
        Livewire::actingAs($one)->test(CourseEditor::class, ['courseId' => $this->course('215')->id])->assertNotFound();
        $component = Livewire::actingAs($one)->test(CourseEditor::class, ['courseId' => $id]);
        app(AcademicProgress::class)->select($one, $this->plan('TECEN')->id);
        app(AcademicProgress::class)->finish($one);
        $component->set('status', 'regularized')->call('save')->assertNotFound();
        Livewire::actingAs($one)->test(Dashboard::class)->assertOk();
        $one->forceFill(['email_verified_at' => null])->save();
        auth()->setUser($one->fresh());
        Livewire::test(CourseEditor::class, ['courseId' => $id])->assertForbidden();
    }

    public function test_livewire_rejects_tampering_with_locked_course_id(): void
    {
        $user = User::factory()->create();
        $this->configureAcademic($user);
        $this->expectException(CannotUpdateLockedPropertyException::class);
        Livewire::actingAs($user)->test(CourseEditor::class, ['courseId' => $this->course('294')->id])->set('courseId', $this->course('215')->id);
    }

    public function test_livewire_rejects_promotion_for_r_then_allows_unspecified_approval(): void
    {
        $user = User::factory()->create();
        $this->configureAcademic($user);
        $course = $this->course('296');
        $component = Livewire::actingAs($user)->test(CourseEditor::class, ['courseId' => $course->id])
            ->set('status', 'approved')->set('approvalMethod', 'promotion')->call('save')
            ->assertHasErrors('entry.approval_method')->assertSee('aria-invalid="true"', false);
        $this->assertDatabaseHas('course_progress', ['plan_course_id' => $course->id, 'status' => 'pending', 'approval_method' => null]);
        $component->set('approvalMethod', 'unspecified')->call('save')->assertHasNoErrors()->assertRedirect(route('career.detail', $course->id));
        $this->assertDatabaseHas('course_progress', ['plan_course_id' => $course->id, 'status' => 'approved', 'approval_method' => 'unspecified']);
    }

    public function test_change_and_return_preserves_inactive_progress_and_excludes_it_from_dashboard(): void
    {
        $user = User::factory()->create();
        $original = $this->configureAcademic($user);
        $this->setState($user, '294', 'approved', 'promotion');
        $this->actingAs($user)->post(route('academic.select'), ['plan_id' => $this->plan('TECEN')->id, 'confirm_change' => 1])->assertRedirect(route('academic.draft'));
        $this->confirmSelection($user);
        $this->get('/dashboard')->assertSee('0 de 24 materias aprobadas')->assertDontSee('1 de 23');
        $this->post(route('academic.select'), ['plan_id' => $original->study_plan_id, 'confirm_change' => 1]);
        $this->confirmSelection($user);
        $this->get('/dashboard')->assertSee('1 de 23 materias aprobadas');
        $this->assertSame(1, AcademicEnrollment::where('user_id', $user->id)->where('is_active', true)->count());
        $this->assertDatabaseCount('course_progress', 47);
    }

    public function test_dashboard_zero_intermediate_full_and_editing_clears_approval_method(): void
    {
        $user = User::factory()->create();
        $enrollment = $this->configureAcademic($user);
        $this->actingAs($user)->get('/dashboard')->assertSee('0 %');
        $this->setState($user, '294', 'approved', 'promotion');
        $this->setState($user, '295', 'regularized');
        $this->setState($user, '298', 'in_progress');
        $this->get('/dashboard')->assertSee('4 %')->assertSee('Tenés 1 materia en curso.')->assertSee('Algoritmos y Estructuras de Datos I');
        $this->get('/mi-carrera?year=1&status=in_progress')->assertSee('Algoritmos y Estructuras de Datos I')->assertDontSee('Inglés I');
        $this->post(route('career.update', $this->course('294')->id), ['status' => 'pending', 'approval_method' => 'promotion']);
        $this->assertDatabaseHas('course_progress', ['academic_enrollment_id' => $enrollment->id, 'plan_course_id' => $this->course('294')->id, 'approval_method' => null]);
        foreach ($enrollment->plan->courses as $course) {
            $this->setState($user, $course->code, 'approved', 'final');
        }
        $this->get('/dashboard')->assertSee('100 %')->assertSee('23 de 23 materias aprobadas');
    }

    public function test_final_and_coursework_columns_remain_separate_and_promotions_are_uncertain(): void
    {
        $user = User::factory()->create();
        $this->configureAcademic($user);
        $this->setState($user, '296', 'regularized');
        $guidance = (new Advisory(app(AcademicProgress::class)->active($user)))->course($this->course('303')->load(['requirements.requiredCourse', 'specialRules']));
        $this->assertSame('complete', $guidance['columns']['to_enroll_requires_coursework']['state']);
        $this->assertSame('missing', $guidance['columns']['to_take_final_requires_finals']['state']);
        $this->assertCount(2, $guidance['columns']['to_take_final_requires_finals']['conditions']);
        $this->assertCount(1, $guidance['columns']['to_enroll_requires_coursework']['conditions']);
        $this->setState($user, '294', 'approved', 'promotion');
        $course = $this->course('310')->load(['requirements.requiredCourse', 'specialRules']);
        $guidance = (new Advisory(app(AcademicProgress::class)->active($user)))->course($course);
        $this->assertSame('uncertain', $guidance['columns']['to_enroll_requires_finals']['state']);
        $this->setState($user, '294', 'approved', 'final');
        $this->assertSame('complete', (new Advisory(app(AcademicProgress::class)->active($user)))->evidence($this->course('294'), true));
        $this->setState($user, '294', 'approved');
        $this->assertSame('uncertain', (new Advisory(app(AcademicProgress::class)->active($user)))->evidence($this->course('294'), true));
    }

    public function test_year_rule_counts_previous_year_finals_and_specific_formation_only(): void
    {
        $user = User::factory()->create();
        $this->configureAcademic($user);
        $this->setState($user, '296', 'approved', 'final');
        $this->setState($user, '297', 'approved', 'final');
        $advisory = fn () => new Advisory(app(AcademicProgress::class)->active($user));
        $this->assertSame('missing', $advisory()->year(2)['state']);
        $this->setState($user, '298', 'approved', 'final');
        $this->assertSame('complete', $advisory()->year(2)['state']);
        $this->assertSame('missing', $advisory()->year(3)['state']);
        $this->setState($user, '302', 'approved', 'promotion');
        $this->setState($user, '305', 'approved', 'final');
        $this->assertSame('uncertain', $advisory()->year(3)['state']);
        $this->setState($user, '302', 'approved', 'final');
        $this->assertSame('complete', $advisory()->year(3)['state']);
        $this->assertSame('uncertain', $advisory()->year(1)['state']);
    }

    public function test_todas_excludes_self_and_keeps_timing_uncertain_even_with_all_other_finals(): void
    {
        $user = User::factory()->create();
        $enrollment = $this->configureAcademic($user);
        foreach ($enrollment->plan->courses->where('code', '!=', '316') as $course) {
            $this->setState($user, $course->code, 'approved', 'final');
        }
        $course = $this->course('316')->load(['requirements.requiredCourse', 'specialRules']);
        $result = (new Advisory(app(AcademicProgress::class)->active($user)))->course($course);
        $this->assertSame('uncertain', $result['final']);
        $this->assertStringContainsString('22 de 22', $result['specials'][0]['observed']);
        $this->assertStringContainsString('finales de todas', $result['specials'][0]['source']['source_note']);
        $this->setState($user, '316', 'approved', 'final');
        $this->assertSame('approved', app(AcademicProgress::class)->active($user)->progress->firstWhere('plan_course_id', $course->id)->status);
    }

    public function test_tshsa_621_final_column_is_empty_and_enrollment_final_column_is_not(): void
    {
        $user = User::factory()->create();
        app(AcademicProgress::class)->select($user, $this->plan('TSHSA')->id);
        app(AcademicProgress::class)->finish($user);
        $course = $this->course('621')->load(['requirements.requiredCourse', 'specialRules']);
        $result = (new Advisory(app(AcademicProgress::class)->active($user)))->course($course);
        $this->assertSame('complete', $result['final']);
        $this->assertSame('missing', $result['columns']['to_enroll_requires_finals']['state']);
        $this->assertSame(['610', '611'], array_map(fn ($condition) => $condition['course']->code, $result['columns']['to_enroll_requires_finals']['conditions']));
    }

    public function test_list_and_detail_use_bounded_queries_without_course_count_dependent_reads(): void
    {
        $user = User::factory()->create();
        $this->configureAcademic($user);
        DB::enableQueryLog();
        $this->actingAs($user)->get('/mi-carrera')->assertOk();
        $this->assertLessThan(25, count(DB::getQueryLog()));
        DB::flushQueryLog();
        $this->get(route('career.detail', $this->course('316')->id))->assertOk()->assertSee('Las demás materias del plan')->assertSee('Por confirmar');
        $this->assertLessThan(65, count(DB::getQueryLog()));
        DB::disableQueryLog();
    }

    public function test_cancel_and_interruption_of_switch_preserve_confirmed_selection_and_candidate_draft(): void
    {
        $user = User::factory()->create(['password' => 'clave-ficticia-segura']);
        $original = $this->configureAcademic($user);
        $this->setState($user, '294', 'approved', 'promotion');
        $completed = $original->fresh()->completed_at;
        $this->actingAs($user)->post(route('academic.select'), ['plan_id' => $this->plan('TECEN')->id]);
        $candidate = app(AcademicProgress::class)->candidate($user);
        $id = $this->course('215')->id;
        $this->post(route('academic.save'), ['entries' => [$id => ['status' => 'approved', 'approval_method' => 'promotion']]])->assertSessionHas('status');
        $this->get('/dashboard')->assertOk()->assertSee('1 de 23 materias aprobadas');
        $this->assertEquals($completed, $original->fresh()->completed_at);
        $this->assertFalse($candidate->fresh()->is_active);
        $this->post('/logout');
        $this->post('/login', ['email' => $user->email, 'password' => 'clave-ficticia-segura']);
        $this->get('/dashboard')->assertOk()->assertSee('1 de 23 materias aprobadas');
        $this->get(route('academic.draft'))->assertOk()->assertSee('value="promotion" selected', false);
        $this->post(route('academic.cancel'))->assertRedirect(route('career.index'));
        $this->assertNull(app(AcademicProgress::class)->candidate($user));
        $this->assertSame($original->id, app(AcademicProgress::class)->active($user)->id);
        $this->assertEquals($completed, $original->fresh()->completed_at);
        $this->post(route('academic.select'), ['plan_id' => $this->plan('TECEN')->id]);
        $this->confirmSelection($user);
        $this->get('/dashboard')->assertSee('1 de 24 materias aprobadas');
    }

    public function test_selecting_same_confirmed_plan_has_no_side_effects(): void
    {
        $user = User::factory()->create();
        $original = $this->configureAcademic($user);
        $before = $original->fresh()->getAttributes();
        $this->actingAs($user)->post(route('academic.select'), ['plan_id' => $original->study_plan_id])->assertRedirect(route('career.index'));
        $this->assertSame($before, $original->fresh()->getAttributes());
        $this->assertNull(app(AcademicProgress::class)->candidate($user));
        $this->assertDatabaseCount('academic_enrollments', 1);
    }

    public function test_final_confirmation_requires_current_owned_reviewed_draft(): void
    {
        $user = User::factory()->create();
        $this->configureAcademic($user);
        $other = User::factory()->create();
        $this->configureAcademic($other);
        $this->actingAs($user)->post(route('academic.select'), ['plan_id' => $this->plan('TECEN')->id]);
        $candidate = app(AcademicProgress::class)->candidate($user);
        $this->post(route('academic.finish'), ['candidate_id' => $candidate->id])->assertSessionHasErrors('academic');
        $this->get(route('academic.summary'));
        $this->post(route('academic.finish'), ['candidate_id' => app(AcademicProgress::class)->active($other)->id])->assertSessionHasErrors('academic');
        app(AcademicProgress::class)->save($user, [$this->course('215')->id => ['status' => 'regularized']], draft: true);
        $this->post(route('academic.finish'), ['candidate_id' => $candidate->id])->assertSessionHasErrors('academic');
        $this->assertSame($this->plan()->id, app(AcademicProgress::class)->active($user)->study_plan_id);
        $this->confirmSelection($user);
    }

    public function test_candidate_changes_cannot_touch_confirmed_or_foreign_plan_and_initial_onboarding_is_required(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $this->configureAcademic($user);
        $this->configureAcademic($other);
        $this->actingAs($user)->post(route('academic.select'), ['plan_id' => $this->plan('TECEN')->id]);
        $this->post(route('academic.save'), ['entries' => [$this->course('215')->id => ['status' => 'approved'], $this->course('294')->id => ['status' => 'approved']]])->assertNotFound();
        $this->assertDatabaseHas('course_progress', ['plan_course_id' => $this->course('215')->id, 'status' => 'pending']);
        $this->post(route('academic.save'), ['entries' => [$this->course('215')->id => ['status' => 'approved']], 'user_id' => $other->id])->assertSessionHasErrors('user_id');
        $this->assertNull(app(AcademicProgress::class)->candidate($other));
        $this->assertSame(0, app(AcademicProgress::class)->summary(app(AcademicProgress::class)->active($other))['approved']);
        $new = User::factory()->create();
        $this->actingAs($new)->post(route('academic.select'), ['plan_id' => $this->plan()->id]);
        $this->post(route('academic.cancel'));
        $this->get('/dashboard')->assertRedirect(route('academic.draft'));
        $this->assertNull(app(AcademicProgress::class)->active($new)->completed_at);
    }

    public function test_inline_save_cancel_and_filter_removal_keep_context_and_ownership(): void
    {
        $user = User::factory()->create();
        $this->configureAcademic($user);
        $this->setState($user, '294', 'in_progress');
        $id = $this->course('294')->id;
        $component = Livewire::actingAs($user)->test(CareerBrowser::class, ['year' => '1', 'statusFilter' => 'in_progress'])
            ->call('edit', $id)->set('status', 'approved')->set('approvalMethod', 'promotion')->call('cancel');
        $this->assertDatabaseHas('course_progress', ['plan_course_id' => $id, 'status' => 'in_progress']);
        $component->call('edit', $id)->set('status', 'regularized')->call('save')
            ->assertSet('year', '1')->assertSet('statusFilter', 'in_progress')->assertSee('ya no coincide con el filtro')->assertDontSee('Editar estado de Inglés I');
        $this->actingAs($user)->post(route('career.update', $id), ['status' => 'in_progress', 'return_to' => 'career', 'year' => 1, 'filter_status' => 'approved'])
            ->assertRedirect(route('career.index', ['year' => 1, 'status' => 'approved']).'#resultados');
        Livewire::actingAs($user)->test(CareerBrowser::class)->call('edit', $this->course('215')->id)->assertNotFound();
        $user->forceFill(['email_verified_at' => null])->save();
        auth()->setUser($user->fresh());
        Livewire::test(CareerBrowser::class)->assertForbidden();
    }

    public function test_additive_migration_recovers_an_interrupted_legacy_switch_without_losing_progress(): void
    {
        $user = User::factory()->create();
        $original = $this->configureAcademic($user);
        $this->setState($user, '294', 'approved', 'promotion');
        $completed = $original->fresh()->completed_at;
        $draft = app(AcademicProgress::class)->select($user, $this->plan('TECEN')->id);
        // Reproduce the old M03 state, exclusively inside the test transaction.
        $original->update(['is_active' => false]);
        $draft->update(['is_active' => true]);
        DB::statement('DROP INDEX one_candidate_plan_per_user');
        Schema::table('academic_enrollments', fn (Blueprint $table) => $table->dropColumn('is_candidate'));
        $migration = require database_path('migrations/2026_10_23_000001_distinguish_candidate_from_confirmed_plan.php');
        $migration->up();
        $this->assertTrue($original->fresh()->is_active);
        $this->assertEquals($completed, $original->fresh()->completed_at);
        $this->assertFalse($draft->fresh()->is_active);
        $this->assertTrue($draft->fresh()->is_candidate);
        $this->assertSame(23, $original->progress()->count());
        $this->assertSame(24, $draft->progress()->count());
        $this->assertSame('promotion', $original->progress()->where('plan_course_id', $this->course('294')->id)->sole()->approval_method);
    }
}
