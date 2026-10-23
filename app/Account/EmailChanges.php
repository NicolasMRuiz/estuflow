<?php

namespace App\Account;

use App\Jobs\SendEmailChange;
use App\Models\PendingEmailChange;
use App\Models\User;
use App\Notifications\EmailChanged;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class EmailChanges
{
    public function request(User $actor, string $email): void
    {
        DB::transaction(function () use ($actor, $email): void {
            $user = User::whereKey($actor->id)->lockForUpdate()->firstOrFail();
            if ($email === $user->email || User::where('email', $email)->exists()) {
                throw ValidationException::withMessages(['email' => 'Elegí otro email disponible.']);
            }
            PendingEmailChange::where('user_id', $user->id)->delete();
            $change = new PendingEmailChange(['id' => (string) Str::uuid(), 'user_id' => $user->id, 'current_email' => $user->email, 'proposed_email' => $email, 'expires_at' => now()->addMinutes(60)]);
            foreach (['current', 'proposed'] as $purpose) {
                $tokens[$purpose] = Str::random(64);
                $change->setAttribute($purpose.'_token_hash', $change->tokenHash($purpose, $tokens[$purpose]));
            }
            $change->save();
            foreach ($tokens as $purpose => $token) {
                SendEmailChange::dispatch($change->id, $purpose, $token)->afterCommit();
            }
        });
    }

    public function cancel(User $actor): void
    {
        DB::transaction(function () use ($actor): void {
            User::whereKey($actor->id)->lockForUpdate()->firstOrFail();
            PendingEmailChange::where('user_id', $actor->id)->delete();
        });
    }

    public function confirm(Request $request, string $id, string $purpose, string $token): bool
    {
        try {
            $completed = DB::transaction(function () use ($request, $id, $purpose, $token): bool {
                $user = User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
                $change = PendingEmailChange::whereKey($id)->lockForUpdate()->first();
                if (! $change?->accepts($user, $purpose, $token)) {
                    throw ValidationException::withMessages(['confirmation' => 'Este enlace no es válido o venció. Revisá el cambio pendiente en tu perfil.']);
                }
                $change->setAttribute($purpose.'_confirmed_at', now());
                $change->setAttribute($purpose.'_token_hash', null);
                $change->save();
                if (! $change->current_confirmed_at || ! $change->proposed_confirmed_at) {
                    return false;
                }
                if (User::where('email', $change->proposed_email)->exists()) {
                    throw ValidationException::withMessages(['confirmation' => 'El email ya no está disponible. Cancelá este cambio y elegí otro.']);
                }
                $oldEmail = $user->email;
                $user->forceFill(['email' => $change->proposed_email, 'email_verified_at' => now()])->save();
                app(AccountSecurity::class)->revokeOthers($user, $request, $oldEmail);
                $change->delete();
                Notification::route('mail', $oldEmail)->notify((new EmailChanged)->afterCommit());

                return true;
            });
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['confirmation' => 'El email ya no está disponible. Cancelá este cambio y elegí otro.']);
        }
        if ($completed) {
            $request->user()->refresh();
            app(AccountSecurity::class)->renew($request);
        }

        return $completed;
    }
}
