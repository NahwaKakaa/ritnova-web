@extends('layouts.participant', ['title' => 'Pengaturan pengingat'])

@section('content')
    <div class="mx-auto grid max-w-4xl gap-8 lg:grid-cols-[1.2fr_0.8fr]">
        <div>
            <h1 class="page-title mb-2">Pengingat</h1>
            <p class="mb-8 muted">Membantumu mengikuti jadwal dan melengkapi catatan.</p>
            <form method="POST" action="{{ route('reminders.update') }}" class="card space-y-5 sm:p-6">
                @csrf
                @method('PUT')
                @include('participant.partials.reminder-fields', ['user' => $user])
                <button type="submit" class="btn-primary w-full">Simpan pengaturan</button>
            </form>
        </div>

        <aside class="lg:pt-24">
            <p class="mb-3 text-sm font-semibold muted">Contoh pesan</p>
            <div class="space-y-2.5">
                @foreach (['Sesi baru sudah tersedia untuk dipelajari.', 'Jangan lupa melengkapi catatan tidur.', 'Pengukuran terbuka sesuai jadwal penelitian.'] as $msg)
                    <div class="flex gap-3 rounded-2xl bg-surface p-3.5 shadow-[var(--shadow-card)] ring-1 ring-ink/5">
                        <x-logo class="[&>span:last-child]:hidden" />
                        <p class="text-sm"><b>RITNOVA</b><br>{{ $msg }}</p>
                    </div>
                @endforeach
            </div>
        </aside>
    </div>
@endsection
