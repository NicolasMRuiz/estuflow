<?php

namespace App\Auth;

use App\Models\OAuthIdentity;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;

class OAuthAccounts
{
    public const EMAIL_CONFLICT = 'Ese email ya tiene una cuenta. Ingresá con tu método actual o recuperá el acceso; después podrás vincular el proveedor desde Métodos de acceso.';

    public function find(OAuthProfile $profile): ?OAuthIdentity
    {
        return OAuthIdentity::query()->where($profile->identity())->first();
    }

    public function login(OAuthProfile $profile): User
    {
        $created = false;
        try {
            $user = DB::transaction(function () use ($profile, &$created): User {
                $identity = $this->find($profile);
                if ($identity) {
                    return $identity->user;
                }
                if ($profile->email === null || User::query()->where('email', $profile->email)->exists()) {
                    throw new OAuthAccountException(self::EMAIL_CONFLICT);
                }
                $user = User::query()->create(['name' => $profile->name, 'email' => $profile->email, 'password' => null]);
                OAuthIdentity::query()->create([...$profile->identity(), 'user_id' => $user->id]);
                $created = true;

                return $user;
            });
        } catch (UniqueConstraintViolationException) {
            // A simultaneous callback may already have committed this exact identity.
            $identity = $this->find($profile);
            if (! $identity) {
                throw new OAuthAccountException(self::EMAIL_CONFLICT);
            }
            $user = $identity->user;
        }
        if ($created) {
            RateLimiter::hit('verify:'.$user->id, 60);
            event(new Registered($user));
        }

        return $user;
    }

    public function link(User $user, OAuthProfile $profile): void
    {
        try {
            DB::transaction(function () use ($user, $profile): void {
                $identity = OAuthIdentity::query()->where($profile->identity())->lockForUpdate()->first();
                if ($identity) {
                    if ($identity->user_id !== $user->id) {
                        throw new OAuthAccountException('Ese proveedor ya está vinculado a otra cuenta. No cambiamos tu sesión ni trasladamos el vínculo.');
                    }

                    return;
                }
                OAuthIdentity::query()->create([...$profile->identity(), 'user_id' => $user->id]);
            });
        } catch (UniqueConstraintViolationException) {
            $identity = $this->find($profile);
            if (! $identity || $identity->user_id !== $user->id) {
                throw new OAuthAccountException('No pudimos vincular esa identidad: ya existe un vínculo para ese proveedor.');
            }
        }
    }
}
