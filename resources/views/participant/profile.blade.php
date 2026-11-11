@extends('layouts.participant', ['title' => 'Profil'])

@section('content')
    <div class="mx-auto max-w-lg space-y-4">
        <h1 class="text-2xl font-bold">Profil & Keamanan</h1>

        <div class="card">
            <p class="text-sm text-ink/60">Kode peserta</p>
            <p class="text-xl font-bold">{{ $user->username }}</p>
            @if ($consent = $user->consents->whereNull('withdrawn_at')->first())
                <p class="mt-2 text-sm text-ink/60">Persetujuan {{ $consent->version->version }} disetujui {{ $consent->agreed_at->translatedFormat('d F Y, H.i') }} WITA</p>
            @endif
        </div>

        <form method="POST" action="{{ route('profile.password') }}" class="card space-y-3">
            @csrf
            @method('PUT')
            <p class="font-semibold">Ubah kata sandi</p>
            <div>
                <label class="label" for="current_password">Kata sandi saat ini</label>
                <input class="input" type="password" id="current_password" name="current_password" autocomplete="current-password" required>
                @error('current_password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="label" for="password">Kata sandi baru (min. 8 karakter)</label>
                <input class="input" type="password" id="password" name="password" autocomplete="new-password" required>
                @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="label" for="password_confirmation">Ulangi kata sandi baru</label>
                <input class="input" type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" required>
            </div>
            <button class="btn-secondary">Simpan kata sandi</button>
        </form>

        <details class="card">
            <summary class="cursor-pointer font-semibold text-red-700">Tarik persetujuan / mengundurkan diri</summary>
            <form method="POST" action="{{ route('consent.withdraw') }}" class="mt-3 space-y-3 text-sm">
                @csrf
                <p class="text-ink/70">Kamu berhak berhenti kapan saja tanpa konsekuensi. Setelah penarikan, akunmu dinonaktifkan. Penanganan data yang sudah terkumpul mengikuti ketentuan etik penelitian; hubungi tim peneliti untuk informasi lebih lanjut.</p>
                <label class="flex items-start gap-2"><input type="checkbox" name="confirm" value="1" class="mt-1"> Saya yakin ingin menarik persetujuan.</label>
                @error('confirm') <p class="text-red-600">{{ $message }}</p> @enderror
                <button class="rounded-xl bg-red-600 px-4 py-2 font-semibold text-white">Ajukan penarikan diri</button>
            </form>
        </details>
    </div>
@endsection
