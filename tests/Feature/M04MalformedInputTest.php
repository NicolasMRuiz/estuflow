<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class M04MalformedInputTest extends TestCase
{
    use RefreshDatabase;

    public function test_invalid_arrays_return_inline_errors_without_breaking_the_form(): void
    {
        $user = User::factory()->create();
        $this->configureAcademic($user);
        $this->actingAs($user);
        $this->from('/calendario/nuevo')->post('/calendario', ['title' => 'Examen', 'type' => 'exam', 'all_day' => 0, 'start_date' => '2026-12-10', 'start_time' => '18:00', 'description' => ['unexpected'], 'plan_course_id' => ['unexpected']])->assertSessionHasErrors(['description' => 'El campo descripción debe ser texto.', 'plan_course_id' => 'El campo materia debe ser un número entero.']);
        $this->get('/calendario/nuevo')->assertOk()->assertSee('Guardar evento');
        $this->from('/notas/nueva')->post('/notas', ['content' => ['unexpected']])->assertSessionHasErrors(['content' => 'El campo contenido debe ser texto.']);
        $this->get('/notas/nueva')->assertOk()->assertSee('Guardar nota');
    }
}
