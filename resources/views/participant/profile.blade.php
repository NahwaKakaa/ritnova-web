@extends('layouts.participant', ['title' => 'Profil'])

@section('content')
    <div class="mx-auto max-w-2xl space-y-6">
        <h1 class="page-title">Profil dan keamanan</h1>

        <section class="panel-night flex items-center gap-5 p-6">
            <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-white/10 text-lg font-bold num">{{ substr($user->username, -2) }}</span>
            <div>
                <p class="text-sm text-white/55">Kode peserta</p>
                <p class="text-2xl font-bold num">{{ $user->username }}</p>
                @if ($consent = $user->consents->whereNull('withdrawn_at')->first())
                    <p class="mt-1 text-sm text-white/55">Persetujuan {{ $consent->version->version }} disetujui {{ $consent->agreed_at->translatedFormat('d F Y') }}</p>
                @endif
            </div>
        </section>

        <form method="POST" action="{{ route('profile.password') }}" class="card space-y-4 sm:p-6">
            @csrf
            @method('PUT')
            <h2 class="text-lg font-bold">Ubah kata sandi</h2>
            <div>
                <label class="label" for="current_password">Kata sandi saat ini</label>
                <input class="input" type="password" id="current_password" name="current_password" autocomplete="current-password" required>
                @error('current_password') <p class="mt-1.5 text-sm text-rose-deep">{{ $message }}</p> @enderror
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="label" for="password">Kata sandi baru</label>
                    <input class="input" type="password" id="password" name="password" autocomplete="new-password" minlength="8" required>
                    @error('password') <p class="mt-1.5 text-sm text-rose-deep">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label" for="password_confirmation">Ulangi kata sandi baru</label>
                    <input class="input" type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" required>
                </div>
            </div>
            <p class="text-xs muted">Minimal 8 karakter.</p>
            <button type="submit" class="btn-secondary">Simpan kata sandi</button>
        </form>

        <details class="group card sm:p-6">
            <summary class="flex cursor-pointer list-none items-center justify-between font-semibold text-rose-deep">Tarik persetujuan dan berhenti dari program<x-icon name="plus" class="h-5 w-5 transition group-open:rotate-45" /></summary>
            <form method="POST" action="{{ route('consent.withdraw') }}" class="mt-4 space-y-4 text-sm">
                @csrf
                <p class="text-ink/70">Kamu berhak berhenti kapan saja tanpa konsekuensi. Setelah itu akunmu dinonaktifkan. Penanganan data yang sudah terkumpul mengikuti ketentuan etik penelitian; hubungi tim peneliti untuk informasi lebih lanjut.</p>
                <label class="choice"><input type="checkbox" name="confirm" value="1" class="h-4 w-4 accent-rose-deep">Saya yakin ingin menarik persetujuan.</label>
                @error('confirm') <p class="text-rose-deep">{{ $message }}</p> @enderror
                <button type="submit" class="btn bg-rose-deep text-white hover:bg-rose-deep/90">Tarik persetujuan</button>
            </form>
        </details>
    </div>
@endsection
