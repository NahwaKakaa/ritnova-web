@extends('layouts.participant', ['title' => 'Menu'])

@php
    $user = auth()->user();
    $intervention = $user->isIntervention();
    $groups = [
        'Program' => array_filter([
            $intervention ? ['learning.index', 'book', 'Belajar', '12 sesi dalam 4 minggu'] : null,
            $intervention ? ['progress', 'chart', 'Progres', 'Status sesi dan perkembangan SJL'] : null,
            $intervention ? ['goals.index', 'target', 'Target minggu ini', 'Goal dan rencana aksi'] : null,
            $intervention ? ['reflection.index', 'sparkle', 'Refleksi mingguan', 'Evaluasi singkat, kurang dari 2 menit'] : null,
        ]),
        'Tidur' => array_filter([
            ['diary.index', 'moon', 'Sleep Tracker', 'Catatan dan riwayat tidur'],
            $intervention ? ['sjl', 'clock', 'SJL Check', 'Kalkulator social jetlag'] : null,
        ]),
        'Penelitian' => [
            ['assessments.index', 'clipboard', 'Pengukuran', 'T0, T1, dan T2'],
            ['onboarding', 'check', 'Persiapan awal', 'Langkah sebelum program'],
        ],
        'Akun' => [
            ['notifications.index', 'bell', 'Notifikasi', 'Pengingat di website'],
            ['reminders.edit', 'clock', 'Pengaturan pengingat', 'Kanal dan jam pengingat'],
            ['profile', 'user', 'Profil dan keamanan', 'Kata sandi dan penarikan diri'],
            ['help', 'help', 'Bantuan dan privasi', 'FAQ, panduan, kontak tim'],
        ],
    ];
@endphp

@section('content')
    <div class="mb-8 flex items-center gap-4">
        <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-primary text-lg font-bold text-white num">{{ substr($user->username, -2) }}</span>
        <div>
            <h1 class="text-2xl font-bold num">{{ $user->username }}</h1>
            <p class="muted">Peserta RITNOVA</p>
        </div>
    </div>

    <div class="grid gap-8 md:grid-cols-2">
        @foreach ($groups as $heading => $items)
            @continue(empty($items))
            <section>
                <h2 class="mb-3 text-sm font-semibold muted">{{ $heading }}</h2>
                <div class="card overflow-hidden p-0">
                    @foreach ($items as [$route, $icon, $label, $hint])
                        <a href="{{ route($route) }}" class="flex items-center gap-4 border-b border-night/5 px-5 py-4 last:border-0 hover:bg-canvas">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-lavender text-primary"><x-icon :name="$icon" /></span>
                            <span class="flex-1"><span class="block font-semibold">{{ $label }}</span><span class="text-sm muted">{{ $hint }}</span></span>
                            <x-icon name="arrow-right" class="h-4 w-4 text-night/25" />
                        </a>
                    @endforeach
                </div>
            </section>
        @endforeach
    </div>

    <form method="POST" action="{{ route('logout') }}" class="mt-8">
        @csrf
        <button type="submit" class="btn-ghost"><x-icon name="logout" class="h-4 w-4" />Keluar</button>
    </form>
@endsection
