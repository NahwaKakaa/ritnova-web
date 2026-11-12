<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Pemakaian: ->middleware('role:researcher,admin') */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if ($user && ! in_array($user->role, $roles, true)) {
            // Akun tim yang membuka halaman peserta: arahkan ke panel, jangan tampilkan 403.
            if ($user->isStaff() && $roles === [User::ROLE_PARTICIPANT] && $request->isMethod('GET')) {
                return redirect()->route('admin.dashboard')->with('status',
                    'Halaman itu khusus peserta. Untuk melihatnya, keluar lalu masuk dengan kode peserta (contoh: RV-001), atau pakai jendela penyamaran.');
            }
        }

        abort_unless($user && in_array($user->role, $roles, true), 403);

        return $next($request);
    }
}
