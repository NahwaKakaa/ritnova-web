@extends('layouts.guest', ['title' => 'Masuk'])

@section('content')
    <div class="card">
        <h1 class="mb-1 text-xl font-bold">Selamat datang di RITNOVA</h1>
        <p class="mb-6 text-sm text-ink/60">Masuk dengan kode peserta dari tim peneliti.</p>

        <form method="POST" action="{{ route('login') }}" class="space-y-4">
            @csrf
            <div>
                <label class="label" for="username">Kode peserta</label>
                <input class="input" id="username" name="username" value="{{ old('username') }}" placeholder="RV-XXXX" autocomplete="username" required autofocus>
                @error('username') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="label" for="password">Kata sandi</label>
                <input class="input" id="password" type="password" name="password" autocomplete="current-password" required>
            </div>
            <button class="btn-primary w-full">Masuk ke dashboard</button>
        </form>
        <p class="mt-4 text-xs text-ink/50">Lupa kata sandi? Hubungi tim peneliti untuk reset.</p>
    </div>
@endsection
