<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Password;

// Existence lookup and token generation happen off the public request path.
class RequestPasswordReset implements ShouldBeEncrypted, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 15;

    public function __construct(public string $email) {}

    public function handle(): void
    {
        Password::sendResetLink(['email' => $this->email]);
    }
}
