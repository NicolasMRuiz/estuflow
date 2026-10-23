<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    protected $redirectRoute = 'login';

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('email'))) {
            $this->merge(['email' => User::normalizeEmail($this->input('email'))]);
        }
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:254'],
            'password' => ['required', 'string', 'max:128'],
        ];
    }

    public function authenticate(): void
    {
        $key = hash('sha256', $this->string('email').'|'.$this->ip());
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'auth' => 'Demasiados intentos. Volvé a probar en '.RateLimiter::availableIn($key).' segundos.',
            ]);
        }

        if (! Auth::attempt($this->only('email', 'password'))) {
            RateLimiter::hit($key, 60);
            throw ValidationException::withMessages(['auth' => 'El email o la contraseña no son correctos.']);
        }

        RateLimiter::clear($key);
    }
}
