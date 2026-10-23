<?php

namespace App\Account;

use App\Models\User;
use Illuminate\Http\Request;

class Reauthentication
{
    public static function confirm(Request $request, User $user, bool $provider = false): void
    {
        $request->session()->regenerate(true);
        $request->session()->put('auth.password_confirmed_at', now()->timestamp);
        $request->session()->put('oauth.reauthenticated_user_id', $user->id);
        if ($provider) {
            $request->session()->put('profile.provider_confirmation', ['user_id' => $user->id, 'at' => now()->timestamp]);
        }
    }

    public static function recent(Request $request, bool $provider = false): bool
    {
        $at = $provider ? $request->session()->get('profile.provider_confirmation.at') : $request->session()->get('auth.password_confirmed_at');
        $id = $request->session()->get($provider ? 'profile.provider_confirmation.user_id' : 'oauth.reauthenticated_user_id');

        return is_int($at) && $at > now()->subMinutes(10)->timestamp && $at <= now()->timestamp && $id === $request->user()?->id;
    }

    // Only named local operations; no browser-controlled return URL.
    public static function destination(Request $request): string
    {
        return match ($request->session()->get('profile.operation')) {
            'email' => 'profile.email', 'password' => 'profile.password', 'access' => 'profile.index',
            default => 'access-methods.index',
        };
    }
}
