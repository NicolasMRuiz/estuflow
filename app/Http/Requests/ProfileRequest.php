<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

class ProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() instanceof User && $this->user()->hasVerifiedEmail();
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
        return match ($this->route()->getName()) {
            'profile.name' => ['name' => ['required', 'string', 'max:100']],
            'profile.email.request' => ['email' => ['required', 'string', 'email', 'max:254']],
            'profile.password.update' => [
                'current_password' => [$this->user()->password === null ? 'prohibited' : 'required', 'string', 'max:128'],
                'password' => ['required', 'string', 'max:128', Password::min(10)],
                'password_confirmation' => ['required', 'string', 'same:password'],
            ],
            default => [],
        };
    }

    /** @return list<\Closure> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if (array_diff(array_keys($this->all()), [...array_keys($this->rules()), '_token']) !== []) {
                $validator->errors()->add('account', 'La solicitud contiene campos que no se pueden modificar aquí.');
            }
        }];
    }

    protected function getRedirectUrl(): string
    {
        return route(match ($this->route()->getName()) {
            'profile.email.request' => 'profile.email', 'profile.password.update' => 'profile.password', default => 'profile.index',
        });
    }
}
