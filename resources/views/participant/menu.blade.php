@extends('layouts.participant', ['title' => 'Menu'])

@php
    $intervention = auth()->user()->isIntervention();
    $groups = [
        'Program' => array_filter([
            $intervention ? ['learning.index', '▤', 'Belajar', '12 sesi dalam 4 minggu'] : null,
            $intervention ? ['progress', '↗', 'Progres & Insights', 'Status sesi dan perkembangan SJL'] : null,
            $intervention ? ['goals.index', '◎', 'Target minggu ini', 'Goal dan rencana aksi'] : null,
            $intervention ? ['reflection.index', '✦', 'Refleksi mingguan', 'Evaluasi singkat < 2 menit'] : null,
        ]),
        'Tidur' => array_filter([
            ['diary.index', '☽', 'Sleep Tracker', 'Catatan & riwayat tidur'],
            $intervention ? ['sjl', '◷', 'SJL Check', 'Kalkulator social jetlag'] : null,
        ]),
        'Penelitian' => [
            ['assessments.index', '✎', 'Pengukuran T0/T1/T2', 'Instrumen penelitian terjadwal'],
            ['onboarding', '①', 'Persiapan awal', 'Langkah onboarding'],
        ],
        'Akun' => [
            ['notifications.index', '🔔', 'Notifikasi', 'Pengingat di website'],
            ['reminders.edit', '⏰', 'Pengaturan pengingat', 'Kanal dan jam pengingat'],
            ['profile', '◉', 'Profil & keamanan', 'Kode peserta, kata sandi, penarikan diri'],
            ['help', '?', 'Bantuan & privasi', 'FAQ, panduan, kontak tim'],
        ],
    ];
@endphp

@section('content')
    <h1 class="mb-6 text-2xl font-bold">Menu</h1>

    <div class="space-y-6">
        @foreach ($groups as $heading => $items)
            @continue(empty($items))
            <section>
                <h2 class="mb-2 text-xs font-semibold tracking-wide text-ink/50 uppercase">{{ $heading }}</h2>
                <div class="grid gap-3 sm:grid-cols-2">
                    @foreach ($items as [$route, $icon, $label, $hint])
                        <a href="{{ route($route) }}" class="card flex items-center gap-4 p-4 hover:ring-primary/30">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-lavender text-primary">{{ $icon }}</span>
                            <span><span class="block font-semibold">{{ $label }}</span><span class="text-sm text-ink/60">{{ $hint }}</span></span>
                        </a>
                    @endforeach
                </div>
            </section>
        @endforeach

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="btn-secondary w-full">Keluar</button>
        </form>
    </div>
@endsection
