<?php

namespace Tests\Feature;

use App\Academic\AcademicProgress;
use App\Livewire\NotificationBell;
use App\Models\InboxNotice;
use App\Models\PersonalNote;
use App\Models\StudyPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class M04NotesInboxTest extends TestCase
{
    use RefreshDatabase;

    public function test_notes_full_crud_escape_and_persist_after_session_and_career_change(): void
    {
        $u = User::factory()->create();
        $old = $this->configureAcademic($u);
        $this->actingAs($u);
        $this->post('/notas', ['content' => ''])->assertSessionHasErrors('content');
        $this->post('/notas', ['title' => 'Apunte', 'content' => "<script>alert(1)</script>\nSegunda línea"])->assertRedirect();
        $note = PersonalNote::sole();
        $this->get('/notas/'.$note->id)->assertSee('&lt;script&gt;', false)->assertDontSee('<script>alert(1)</script>', false)->assertSee('Segunda línea');
        $this->get('/notas/'.$note->id.'/editar')->assertOk();
        $this->assertSame('Apunte', $note->fresh()->title);
        $this->post('/notas/'.$note->id, ['title' => 'Editada', 'content' => "Nuevo\nTexto"])->assertRedirect();
        $this->post('/logout')->assertRedirect();
        $this->actingAs($u);
        $this->get('/notas/'.$note->id)->assertSee('Editada');
        $p = app(AcademicProgress::class);
        $p->select($u, StudyPlan::whereKeyNot($old->study_plan_id)->first()->id);
        $p->finish($u);
        $this->get('/dashboard')->assertSee('Editada');
        $this->get('/notas/'.$note->id.'/eliminar')->assertSee('Cancelar');
        $this->assertDatabaseCount('personal_notes', 1);
        $this->post('/notas/'.$note->id.'/eliminar', ['confirm' => 1])->assertRedirect();
        $this->assertDatabaseCount('personal_notes', 0);
    }

    public function test_other_account_note_ids_are_inaccessible_and_dashboard_is_bounded(): void
    {
        $a = User::factory()->create();
        $b = User::factory()->create();
        $this->configureAcademic($a);
        $this->configureAcademic($b);
        for ($i = 0; $i < 5; $i++) {
            PersonalNote::create(['user_id' => $a->id, 'title' => 'Nota '.$i, 'content' => 'Privada']);
        }
        $n = PersonalNote::first();
        $this->actingAs($b);
        foreach (['', '/editar', '/eliminar'] as $suffix) {
            $this->get('/notas/'.$n->id.$suffix)->assertNotFound();
        }
        $this->post('/notas/'.$n->id, ['content' => 'Ajena'])->assertNotFound();
        $this->post('/notas/'.$n->id.'/eliminar', ['confirm' => 1])->assertNotFound();
        $this->get('/notas')->assertDontSee('Privada');
        $this->actingAs($a)->get('/dashboard')->assertSee('Nota 4')->assertDontSee('Nota 0');
    }

    public function test_notification_filters_pagination_counts_own_read_flags_and_deleted_target(): void
    {
        $a = User::factory()->create();
        $b = User::factory()->create();
        $this->configureAcademic($a);
        $this->configureAcademic($b);
        for ($i = 0; $i < 17; $i++) {
            InboxNotice::create(['user_id' => $a->id, 'dedupe_key' => 'test:'.$i, 'type' => $i % 2 ? 'board' : 'reminder', 'title' => 'Aviso '.$i, 'body' => 'Texto ficticio', 'target_id' => 9999]);
        }
        $other = InboxNotice::create(['user_id' => $b->id, 'dedupe_key' => 'other', 'type' => 'reminder', 'title' => 'Ajeno', 'body' => 'Privado', 'target_id' => 9999]);
        $this->actingAs($a);
        Livewire::test(NotificationBell::class)->assertSee('17');
        $this->get('/notificaciones')->assertSee('Aviso 16')->assertDontSee('Aviso 0')->assertDontSee('Privado');
        $this->get('/notificaciones?type=board')->assertSee('Aviso 15')->assertDontSee('Aviso 16');
        $own = InboxNotice::where('user_id', $a->id)->where('type', 'reminder')->first();
        $this->get('/notificaciones/'.$own->id)->assertSee('ya no está disponible');
        $this->assertNull($own->fresh()->read_at);
        $this->post('/notificaciones/'.$own->id.'/leida')->assertRedirect();
        $this->get('/notificaciones?state=read')->assertSee($own->title);
        $this->get('/notificaciones/'.$other->id)->assertNotFound();
        $this->post('/notificaciones/'.$other->id.'/leida')->assertNotFound();
        $this->post('/notificaciones/leidas')->assertRedirect();
        $this->assertSame(0, InboxNotice::where('user_id', $a->id)->whereNull('read_at')->count());
        $this->assertNull($other->fresh()->read_at);
        Livewire::test(NotificationBell::class)->assertDontSee('unread-count');
    }
}
