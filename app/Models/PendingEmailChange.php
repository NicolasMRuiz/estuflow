<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;

/**
 * @property CarbonImmutable $expires_at
 * @property CarbonImmutable|null $current_confirmed_at
 * @property CarbonImmutable|null $proposed_confirmed_at
 */
#[Guarded([])]
#[Hidden(['current_token_hash', 'proposed_token_hash'])]
class PendingEmailChange extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected function casts(): array
    {
        return ['expires_at' => 'immutable_datetime', 'current_confirmed_at' => 'immutable_datetime', 'proposed_confirmed_at' => 'immutable_datetime'];
    }

    public function tokenHash(string $purpose, string $token): string
    {
        return hash('sha256', $this->id.'|'.$purpose.'|'.$this->current_email.'|'.$this->proposed_email.'|'.$token);
    }

    public function accepts(User $user, string $purpose, string $token): bool
    {
        return in_array($purpose, ['current', 'proposed'], true)
            && $this->user_id === $user->id && $this->current_email === $user->email
            && $this->expires_at->isFuture() && $this->getAttribute($purpose.'_confirmed_at') === null
            && is_string($hash = $this->getAttribute($purpose.'_token_hash'))
            && strlen($token) === 64 && hash_equals($hash, $this->tokenHash($purpose, $token));
    }
}
