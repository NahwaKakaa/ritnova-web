<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Peserta tidak boleh memakai fitur apa pun sebelum menyetujui consent versi aktif. */
class EnsureConsented
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->isParticipant() && ! $user->hasActiveConsent()) {
            return redirect()->route('consent.show');
        }

        return $next($request);
    }
}
