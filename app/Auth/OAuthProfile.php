<?php

namespace App\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Validator;

final readonly class OAuthProfile
{
    public function __construct(
        public string $provider,
        public string $issuer,
        public string $subject,
        public string $name,
        public ?string $email,
    ) {}

    /** @return array{provider: string, issuer: string, subject: string} */
    public function identity(): array
    {
        return ['provider' => $this->provider, 'issuer' => $this->issuer, 'subject' => $this->subject];
    }

    public static function usableEmail(mixed $email): ?string
    {
        if (! is_string($email)) {
            return null;
        }

        $email = User::normalizeEmail($email);

        return Validator::make(['email' => $email], ['email' => ['required', 'email', 'max:254']])->passes() ? $email : null;
    }
}
