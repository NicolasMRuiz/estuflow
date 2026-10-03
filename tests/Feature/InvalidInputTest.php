<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvalidInputTest extends TestCase
{
    use RefreshDatabase;

    public function test_array_email_is_a_validation_error_for_registration(): void
    {
        $this->post('/registro', ['name' => 'Prueba', 'email' => ['invalido'], 'password' => 'clave-ficticia-larga', 'password_confirmation' => 'clave-ficticia-larga'])
            ->assertSessionHasErrors('email');
        $this->assertDatabaseCount('users', 0);
    }

    public function test_array_email_is_a_validation_error_for_login(): void
    {
        $this->post('/login', ['email' => ['invalido'], 'password' => 'clave-ficticia-larga'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }
}
