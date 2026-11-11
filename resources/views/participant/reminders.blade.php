@extends('layouts.participant', ['title' => 'Pengaturan Pengingat'])

@section('content')
    <div class="mx-auto max-w-lg">
        <h1 class="mb-1 text-2xl font-bold">Pengingat Aktivitas</h1>
        <p class="mb-6 text-ink/60">Membantumu mengikuti jadwal dan menyelesaikan pengisian.</p>

        <form method="POST" action="{{ route('reminders.update') }}" class="card space-y-4">
            @csrf
            @method('PUT')
            @include('participant.partials.reminder-fields', ['user' => $user])
            <button class="btn-primary w-full">Simpan pengaturan</button>
        </form>

        <div class="card mt-4 text-sm">
            <p class="mb-2 font-semibold">Contoh pengingat</p>
            <ul class="space-y-2 text-ink/70">
                <li class="rounded-xl bg-canvas p-3"><b>RITNOVA</b> · Sesi baru sudah tersedia untuk dipelajari.</li>
                <li class="rounded-xl bg-canvas p-3"><b>RITNOVA</b> · Jangan lupa melengkapi catatan tidur.</li>
                <li class="rounded-xl bg-canvas p-3"><b>RITNOVA</b> · Pengukuran terbuka sesuai jadwal penelitian.</li>
            </ul>
        </div>
    </div>
@endsection
