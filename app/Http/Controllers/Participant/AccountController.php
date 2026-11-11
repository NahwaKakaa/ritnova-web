<?php

namespace App\Http\Controllers\Participant;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function menu(): View
    {
        return view('participant.menu');
    }

    /** Layar 16 — preferensi pengingat: opt-in, kanal, jam pengiriman wajar. */
    public function reminders(Request $request): View
    {
        return view('participant.reminders', ['user' => $request->user()]);
    }

    public function updateReminders(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'reminder_channel' => ['required', 'in:web,whatsapp,both'],
            'reminder_time' => ['required', 'date_format:H:i', 'after_or_equal:06:00', 'before_or_equal:21:00'],
        ], [
            'reminder_time.after_or_equal' => 'Pilih jam antara 06.00 dan 21.00.',
            'reminder_time.before_or_equal' => 'Pilih jam antara 06.00 dan 21.00.',
        ]);

        $request->user()->update([...$data, 'reminder_opt_in' => $request->boolean('reminder_opt_in')]);

        return back()->with('status', 'Pengaturan pengingat tersimpan.');
    }

    /** Layar 17 — bantuan & privasi. */
    public function help(): View
    {
        return view('participant.help');
    }

    public function profile(Request $request): View
    {
        return view('participant.profile', ['user' => $request->user()->load('consents.version')]);
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ], [
            'current_password.current_password' => 'Kata sandi saat ini salah.',
        ]);

        $request->user()->update(['password' => Hash::make($request->string('password'))]);
        AuditLog::record('password_changed', $request->user());

        return back()->with('status', 'Kata sandi berhasil diubah.');
    }
}
