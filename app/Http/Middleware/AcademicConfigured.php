<?php

namespace App\Http\Middleware;

use App\Models\AcademicEnrollment;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AcademicConfigured
{
    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next): Response
    {
        $enrollment = AcademicEnrollment::where('user_id', $request->user()->id)->where('is_active', true)->first();
        if (! $enrollment?->completed_at) {
            return redirect()->route($enrollment ? 'academic.draft' : 'academic.choose');
        }

        return $next($request);
    }
}
