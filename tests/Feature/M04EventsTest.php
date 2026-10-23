<?php

namespace Tests\Feature;

use App\Academic\AcademicProgress;
use App\Calendar\Events;
use App\Models\CalendarEvent;
use App\Models\EventReminder;
use App\Models\StudyPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class M04EventsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        $this->travelTo(now()->setDate(2026, 10, 23)->setTime(12, 0));
    }

    /** @return array<string, mixed> */
    private function input(array $replace = []): array
    {
        return array_replace(['title' => 'Examen de prueba', 'type' => 'exam', 'all_day' => '0', 'start_date' => '2026-12-31', 'start_time' => '23:30', 'reminder_options' => ['day', 'hour']], $replace);
    }

    public function test_http_crud_persists_cancel_does_not_write_and_context_returns(): void
    {
        $user = User::factory()->create();
        $enrollment = $this->configureAcademic($user);
        $this->actingAs($user);
        $this->post('/calendario', $this->input(['plan_course_id' => $enrollment->plan->courses->first()->id, 'month' => '2026-12', 'view' => 'agenda', 'type' => 'exam']))->assertRedirect();
        $event = CalendarEvent::sole();
        $this->assertSame('2027-01-01 02:30', $event->starts_at->format('Y-m-d H:i'));
        $this->get('/calendario/'.$event->id)->assertOk()->assertSee('31/12/2026 · 23:30');
        $this->get('/calendario/'.$event->id.'/editar?month=2026-12&view=agenda')->assertOk()->assertSee('Cancelar');
        $this->get('/calendario/'.$event->id)->assertOk();
        $this->assertSame('Examen de prueba', $event->fresh()->title);
        $this->post('/calendario/'.$event->id, $this->input(['title' => 'Título corregido', 'month' => '2026-12', 'view' => 'agenda']))->assertRedirectContains('month=2026-12');
        $this->assertSame('Título corregido', $event->fresh()->title);
        $this->get('/calendario/'.$event->id.'/eliminar')->assertOk()->assertSee('Cancelar');
        $this->assertFalse($event->fresh()->trashed());
        $this->post('/calendario/'.$event->id.'/eliminar', ['confirm' => 1])->assertRedirect();
        $this->assertSoftDeleted($event);
        $this->assertSame(0, EventReminder::where('internal_status', 'pending')->count());
    }

    public function test_fields_dates_end_and_arbitrary_owner_are_rejected(): void
    {
        $user = User::factory()->create();
        $this->configureAcademic($user);
        $this->actingAs($user);
        foreach ([['title' => ''], ['title' => str_repeat('a', 161)], ['type' => 'fake'], ['start_date' => '2026-02-30'], ['start_time' => ''], ['end_time' => '23:00'], ['end_date' => '2026-12-30'], ['user_id' => 99], ['all_day' => []], ['reminder_options' => ['same_morning']]] as $invalid) {
            $this->post('/calendario', $this->input($invalid))->assertSessionHasErrors();
        }
        $this->assertDatabaseCount('calendar_events', 0);
    }

    public function test_all_day_dates_are_inclusive_and_timed_overlap_crosses_year(): void
    {
        $u = User::factory()->create();
        $this->configureAcademic($u);
        $event = app(Events::class)->save($u, $this->input(['all_day' => 1, 'start_date' => '2026-12-30', 'end_date' => '2027-01-02', 'start_time' => null, 'reminder_options' => ['previous_morning', 'same_morning']]));
        $this->assertNull($event->starts_at);
        $this->assertSame('2027-01-03 02:59', $event->cutoff()->format('Y-m-d H:i'));
        $this->actingAs($u)->get('/calendario?month=2027-01&view=agenda')->assertOk()->assertSee($event->title);
        $this->actingAs($u)->get('/calendario?month=2027-03&view=agenda')->assertOk()->assertDontSee($event->title);
        $event2 = app(Events::class)->save($u, $this->input(['title' => 'Cruce medianoche', 'end_date' => '2027-01-01', 'end_time' => '01:00']));
        $this->assertSame('2027-01-01 04:00', $event2->ends_at->format('Y-m-d H:i'));
    }

    public function test_other_account_ids_and_non_active_subject_are_rejected(): void
    {
        $a = User::factory()->create();
        $b = User::factory()->create();
        $this->configureAcademic($a);
        $this->configureAcademic($b);
        $e = app(Events::class)->save($a, $this->input());
        $this->actingAs($b);
        foreach (['', '/editar', '/eliminar'] as $suffix) {
            $this->get('/calendario/'.$e->id.$suffix)->assertNotFound();
        }
        $this->post('/calendario/'.$e->id, $this->input())->assertNotFound();
        $this->post('/calendario/'.$e->id.'/eliminar', ['confirm' => 1])->assertNotFound();
        $otherCourse = StudyPlan::where('key', '!=', 'TSAS-6790-19-INGRESO-2020')->first()->courses->first();
        $this->post('/calendario', $this->input(['plan_course_id' => $otherCourse->id]))->assertSessionHasErrors('plan_course_id');
    }

    public function test_changing_career_preserves_events_and_previous_subject_but_not_new_old_subjects(): void
    {
        $u = User::factory()->create();
        $old = $this->configureAcademic($u);
        $service = app(Events::class);
        $event = $service->save($u, $this->input(['plan_course_id' => $old->plan->courses->first()->id]));
        $progress = app(AcademicProgress::class);
        $progress->select($u, StudyPlan::whereKeyNot($old->study_plan_id)->first()->id);
        $progress->finish($u);
        $this->actingAs($u)->get('/calendario?month=2026-12')->assertOk()->assertSee($event->title);
        $this->post('/calendario/'.$event->id, $this->input(['title' => 'Conservado', 'plan_course_id' => $event->plan_course_id]))->assertRedirect();
        $this->post('/calendario/'.$event->id, $this->input(['plan_course_id' => $old->plan->courses->last()->id]))->assertSessionHasErrors('plan_course_id');
    }

    public function test_navigation_and_login_only_consult_local_data(): void
    {
        Http::fake();
        $u = User::factory()->create();
        $this->configureAcademic($u);
        $this->actingAs($u);
        foreach (['/dashboard', '/calendario', '/notificaciones', '/cartelera', '/notas'] as $url) {
            $this->get($url)->assertOk();
        }
        Http::assertNothingSent();
    }

    public function test_failed_edit_is_atomic_and_filter_context_is_independent_of_event_type(): void
    {
        $user = User::factory()->create();
        $this->configureAcademic($user);
        $event = app(Events::class)->save($user, $this->input());
        $this->actingAs($user);
        $this->post('/calendario/'.$event->id, $this->input(['title' => 'No guardar', 'end_time' => '22:00']))->assertSessionHasErrors('end_time');
        $this->assertSame('Examen de prueba', $event->fresh()->title);
        $this->assertSame(1, $event->fresh()->schedule_version);
        $this->assertDatabaseCount('event_reminders', 2);
        $this->post('/calendario/'.$event->id, $this->input(['type' => 'class', 'filter_type' => 'exam', 'view' => 'agenda', 'month' => '2026-12']))->assertRedirectContains('filter_type=exam');
        $this->assertSame('class', $event->fresh()->type);
    }

    public function test_testing_control_rejects_any_smtp_other_than_test_mailpit_before_waiting(): void
    {
        config(['mail.mailers.smtp.host' => 'smtp.invalid']);
        $this->artisan('testing:m04-control')->assertFailed();
    }
}
