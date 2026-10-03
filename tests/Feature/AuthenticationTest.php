<?php

namespace Tests\Feature;

use App\Livewire\Dashboard;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, string> */
    private function registration(): array
    {
        return [
            'name' => 'Valentina Prueba',
            'email' => 'valentina@example.test',
            'password' => 'clave-ficticia-de-prueba',
            'password_confirmation' => 'clave-ficticia-de-prueba',
        ];
    }

    public function test_registration_normalizes_email_hashes_password_and_regenerates_session(): void
    {
        $this->withSession(['sentinel' => 'test']);
        $oldSession = session()->getId();
        $data = array_replace($this->registration(), ['email' => '  VALENTINA@Example.Test  ']);
        $this->post('/registro', $data)->assertRedirect('/dashboard');
        $user = User::sole();
        $this->assertSame('valentina@example.test', $user->email);
        $this->assertNotSame($data['password'], $user->password);
        $this->assertTrue(Hash::check($data['password'], $user->password));
        $this->assertAuthenticatedAs($user);
        $this->assertNotSame($oldSession, session()->getId());
        $this->assertDatabaseHas('sessions', ['user_id' => $user->id]);
        $this->assertSame('database', config('session.driver'));
    }

    public function test_invalid_registration_has_spanish_server_errors(): void
    {
        $this->post('/registro', ['name' => '', 'email' => 'incorrecto', 'password' => 'corta', 'password_confirmation' => 'otra'])
            ->assertSessionHasErrors(['name', 'email', 'password'])
            ->assertSessionHas('errors', fn ($errors) => str_contains($errors->first('name'), 'obligatorio'));
        $this->assertDatabaseCount('users', 0);
        $this->assertGuest();
    }

    public function test_duplicate_email_is_rejected_after_normalization(): void
    {
        User::factory()->create(['email' => 'valentina@example.test']);
        $this->post('/registro', array_replace($this->registration(), ['email' => 'VALENTINA@EXAMPLE.TEST']))
            ->assertSessionHasErrors('email');
        $this->assertDatabaseCount('users', 1);
    }

    public function test_model_also_normalizes_email_outside_forms(): void
    {
        $user = User::factory()->create(['email' => '  PERSONA@Example.Test ']);
        $this->assertSame('persona@example.test', $user->fresh()->email);
    }

    public function test_password_confirmation_is_required(): void
    {
        $this->post('/registro', array_replace($this->registration(), ['password_confirmation' => 'no-coincide']))
            ->assertSessionHasErrors('password');
        $this->assertDatabaseCount('users', 0);
    }

    public function test_correct_login_normalizes_email_and_regenerates_session(): void
    {
        $user = User::factory()->create(['email' => 'valentina@example.test', 'password' => 'clave-ficticia-de-prueba']);
        $this->withSession(['sentinel' => 'test']);
        $oldSession = session()->getId();
        $this->post('/login', ['email' => ' VALENTINA@EXAMPLE.TEST ', 'password' => 'clave-ficticia-de-prueba'])
            ->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
        $this->assertNotSame($oldSession, session()->getId());
    }

    public function test_incorrect_credentials_are_rejected_without_password_flashing(): void
    {
        User::factory()->create(['email' => 'valentina@example.test']);
        $this->post('/login', ['email' => 'valentina@example.test', 'password' => 'incorrecta'])
            ->assertSessionHasErrors('email')
            ->assertSessionMissing('_old_input.password');
        $this->assertGuest();
    }

    public function test_login_rate_limit_blocks_even_correct_credentials_after_five_failures(): void
    {
        User::factory()->create(['email' => 'limit@example.test', 'password' => 'clave-ficticia-de-prueba']);
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => 'limit@example.test', 'password' => 'incorrecta'])->assertSessionHasErrors('email');
        }
        $this->post('/login', ['email' => 'LIMIT@EXAMPLE.TEST', 'password' => 'clave-ficticia-de-prueba'])
            ->assertSessionHasErrors('email')
            ->assertSessionHas('errors', fn ($errors) => str_contains($errors->first('email'), 'Demasiados intentos'));
        $this->assertGuest();
    }

    public function test_anonymous_dashboard_redirects_to_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_two_accounts_only_see_their_own_identity_even_with_url_input(): void
    {
        $first = User::factory()->create(['name' => 'Primera Persona']);
        $second = User::factory()->create(['name' => 'Segunda Persona']);
        $this->actingAs($first)->get('/dashboard?name=Segunda%20Persona&user_id='.$second->id)
            ->assertOk()->assertSee('Primera Persona')->assertDontSee('Segunda Persona')->assertSee('Sin datos');
        $this->actingAs($second)->get('/dashboard')->assertOk()->assertSee('Segunda Persona')->assertDontSee('Primera Persona');
    }

    public function test_livewire_dashboard_always_reads_the_authenticated_user(): void
    {
        $user = User::factory()->create(['name' => 'Persona Livewire']);
        Livewire::actingAs($user)->test(Dashboard::class)->assertSee('Persona Livewire');
    }

    public function test_logout_invalidates_session_regenerates_csrf_and_blocks_dashboard(): void
    {
        $this->actingAs(User::factory()->create())->withSession(['private_marker' => 'test']);
        $oldSession = session()->getId();
        $oldToken = session()->token();
        $this->post('/logout')->assertRedirect('/login')->assertSessionMissing('private_marker');
        $this->assertGuest();
        $this->assertNotSame($oldSession, session()->getId());
        $this->assertNotSame($oldToken, session()->token());
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_logout_cannot_be_performed_with_get(): void
    {
        $this->actingAs(User::factory()->create())->get('/logout')->assertStatus(405);
        $this->assertAuthenticated();
    }

    public function test_guest_forms_have_labels_csrf_and_no_unimplemented_flows(): void
    {
        $this->get('/login')->assertOk()->assertSee('name="_token"', false)
            ->assertSee('for="email"', false)->assertDontSee('forgot-password')->assertDontSee('WorkOS');
        $this->get('/registro')->assertOk()->assertSee('Confirmar contraseña');
    }
}
