<?php

namespace App\Http\Controllers\Participant;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Consent;
use App\Models\ConsentVersion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ConsentController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        if ($request->user()->hasActiveConsent()) {
            return redirect()->route('dashboard');
        }

        return view('participant.consent', ['version' => ConsentVersion::current()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate(['agree' => ['accepted']], ['agree.accepted' => 'Centang persetujuan untuk melanjutkan.']);

        $version = ConsentVersion::current();
        abort_if($version === null, 503, 'Dokumen persetujuan belum diterbitkan.');

        Consent::firstOrCreate(
            ['user_id' => $request->user()->id, 'consent_version_id' => $version->id],
            ['agreed_at' => now(), 'ip_address' => $request->ip(), 'user_agent' => $request->userAgent()],
        );

        return redirect()->route($request->user()->onboarded_at ? 'dashboard' : 'onboarding');
    }

    /**
     * Mekanisme tarik persetujuan (Layar 03 & 17). Data yang sudah terkumpul tetap
     * disimpan; penanganannya mengikuti protokol etik penelitian.
     */
    public function withdraw(Request $request): RedirectResponse
    {
        $request->validate(
            ['confirm' => ['accepted']],
            ['confirm.accepted' => 'Centang konfirmasi untuk mengajukan penarikan diri.'],
        );

        $user = $request->user();

        $user->consents()->whereNull('withdrawn_at')->update(['withdrawn_at' => now()]);
        $user->forceFill(['status' => 'withdrawn'])->save();
        AuditLog::record('consent_withdrawn', $user);

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'Penarikan diri tercatat. Terima kasih atas partisipasimu.');
    }
}
