<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Mencegah kebocoran konten intervensi ke kelompok pembanding.
 * Pemakaian: ->middleware('group:intervention')
 */
class EnsureStudyGroup
{
    public function handle(Request $request, Closure $next, string ...$groups): Response
    {
        abort_unless(in_array($request->user()?->study_group, $groups, true), 404);

        return $next($request);
    }
}
