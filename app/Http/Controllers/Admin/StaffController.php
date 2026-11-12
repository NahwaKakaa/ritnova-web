<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use App\Support\TempPassword;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Akun tim peneliti (hanya admin). */
class StaffController extends Controller
{
    public function index(): View
    {
        return view('admin.staff.index', [
            'staff' => User::whereIn('role', [User::ROLE_RESEARCHER, User::ROLE_ADMIN])->orderBy('role')->orderBy('username')->get(),
            'credentials' => session('credentials'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'username' => ['required', 'string', 'min:3', 'max:40', 'regex:/^[a-z0-9._-]+$/i', Rule::unique('users', 'username')],
            'name' => ['required', 'string', 'max:120'],
            'role' => ['required', Rule::in([User::ROLE_RESEARCHER, User::ROLE_ADMIN])],
        ], ['username.regex' => 'Username hanya huruf, angka, titik, garis bawah, atau tanda hubung.'], ['name' => 'nama', 'role' => 'peran']);

        $password = TempPassword::make(5, 5);
        $user = User::create([...$data, 'username' => Str::lower($data['username']), 'status' => 'active', 'password' => $password]);
        AuditLog::record('staff_created', $user, null, ['username' => $user->username, 'role' => $user->role]);

        return back()->with('status', 'Akun '.$user->username.' dibuat.')->with('credentials', [[$user->username, $password]]);
    }

    public function resetPassword(User $staff): RedirectResponse
    {
        abort_unless($staff->isStaff(), 404);
        $password = TempPassword::make(5, 5);
        $staff->update(['password' => $password]);
        DB::table('sessions')->where('user_id', $staff->id)->delete();
        AuditLog::record('password_reset', $staff);

        return back()->with('status', 'Kata sandi baru untuk '.$staff->username.' dibuat.')->with('credentials', [[$staff->username, $password]]);
    }

    public function updateStatus(Request $request, User $staff): RedirectResponse
    {
        abort_unless($staff->isStaff(), 404);
        abort_if($staff->is($request->user()), 422, 'Tidak bisa menonaktifkan akun sendiri.');
        $status = $staff->status === 'active' ? 'inactive' : 'active';
        $staff->update(['status' => $status]);
        if ($status === 'inactive') {
            DB::table('sessions')->where('user_id', $staff->id)->delete();
        }
        AuditLog::record('staff_status', $staff, null, ['status' => $status]);

        return back()->with('status', 'Akun '.$staff->username.($status === 'active' ? ' diaktifkan.' : ' dinonaktifkan.'));
    }
}
