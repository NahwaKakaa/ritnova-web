@extends('layouts.guest', ['title' => 'Masuk'])

@section('content')
    <h1 class="mb-2 text-[28px] font-bold">Masuk ke RITNOVA</h1>
    <p class="mb-8 muted">Gunakan kode peserta dan kata sandi dari tim peneliti.</p>

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf
        <div>
            <label class="label" for="username">Kode peserta</label>
            <input class="input uppercase placeholder:normal-case" id="username" name="username" value="{{ old('username') }}" placeholder="RV-XXXX" autocomplete="username" required autofocus aria-describedby="username-error">
            @error('username') <p id="username-error" class="mt-1.5 flex items-center gap-1.5 text-sm text-rose-deep"><x-icon name="alert" class="h-4 w-4" />{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="label" for="password">Kata sandi</label>
            <input class="input" id="password" type="password" name="password" autocomplete="current-password" required>
        </div>
        <button type="submit" class="btn-primary w-full py-3.5 text-[15px]">Masuk ke dashboard</button>
    </form>

    <div class="mt-8 space-y-2 border-t border-ink/5 pt-6 text-sm muted">
        <p>Lupa kata sandi? Hubungi tim peneliti untuk mengatur ulang.</p>
        <p>Demi keamanan, kamu otomatis keluar setelah {{ config('session.lifetime') }} menit tidak aktif.</p>
    </div>
@endsection
