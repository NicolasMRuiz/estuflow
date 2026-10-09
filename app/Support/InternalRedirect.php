<?php

namespace App\Support;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class InternalRedirect
{
    public static function back(Request $request, string $fallback = 'login'): RedirectResponse
    {
        $previous = $request->headers->get('referer');
        $base = rtrim((string) config('app.url'), '/');
        if (is_string($previous) && str_starts_with($previous, $base.'/') && ! str_contains($previous, '\\') && ! preg_match('/[\x00-\x1F\x7F]/', $previous)) {
            return redirect()->to($previous);
        }

        return redirect()->route($fallback);
    }

    public static function afterLogin(Request $request): RedirectResponse
    {
        $target = $request->session()->pull('url.intended');
        $base = rtrim((string) config('app.url'), '/');
        if (is_string($target) && str_starts_with($target, $base.'/') && ! str_contains($target, '\\') && ! preg_match('/[\x00-\x1F\x7F]/', $target)) {
            return redirect()->to($target);
        }

        return redirect()->route($request->user()?->hasVerifiedEmail() ? 'dashboard' : 'verification.notice');
    }
}
