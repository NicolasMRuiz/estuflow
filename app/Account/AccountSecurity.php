<?php

namespace App\Account;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AccountSecurity
{
    // Called under the user's row lock, in the same transaction as the change.
    public function revokeOthers(User $user, Request $request, string $resetEmail): void
    {
        $user->forceFill(['remember_token' => Str::random(60)])->save();
        DB::table('sessions')->where('user_id', $user->id)->where('id', '!=', $request->session()->getId())->delete();
        DB::table('password_reset_tokens')->whereIn('email', array_unique([$resetEmail, $user->email]))->delete();
        $request->session()->forget(['profile.provider_confirmation', 'oauth.attempt', 'state', 'code_verifier', 'oauth.nonce']);
    }

    public function renew(Request $request): void
    {
        Reauthentication::confirm($request, $request->user());
    }
}
