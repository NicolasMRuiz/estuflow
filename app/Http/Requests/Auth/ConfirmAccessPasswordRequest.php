<?php

namespace App\Http\Requests\Auth;

use App\Account\Reauthentication;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class ConfirmAccessPasswordRequest extends FormRequest
{
    protected function getRedirectUrl(): string
    {
        return route(Reauthentication::destination($this));
    }

    public function authorize(): bool
    {
        $user = $this->user();

        return $user instanceof User && $user->hasVerifiedEmail();
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['password' => ['required', 'string', 'max:128']];
    }
}
