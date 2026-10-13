<?php

namespace App\Http\Requests\Auth;

use Illuminate\Validation\Rules\Password;

class ResetPasswordRequest extends EmailRequest
{
    protected function getRedirectUrl(): string
    {
        $token = $this->input('token');

        return is_string($token) && $token !== '' && strlen($token) <= 256
            ? route('password.reset', ['token' => $token]) : route('password.request');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return parent::rules() + [
            'token' => ['required', 'string', 'max:256'],
            'password' => ['required', 'string', 'max:128', Password::min(10)],
            'password_confirmation' => ['required', 'string', 'same:password'],
        ];
    }
}
