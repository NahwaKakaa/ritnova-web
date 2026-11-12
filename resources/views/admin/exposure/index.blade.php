@extends('layouts.admin', ['title' => 'Paparan sesi'])

@php
    $stats = [
        ['Dijadwalkan', $data['scheduled'].' sesi', 'Sudah dirilis minggu ini'],
        ['Selesai', $data['avg_done'] === null ? '—' : str_replace('.', ',', (string) round($data['avg_done'], 1)).' rerata', 'Sesi selesai per peserta'],
        ['Terlambat', $data['late'].' ID', 'Mulai lebih dari 24 jam atau lewat batas'],
        ['Anomali', $data['anomalies'].' log', 'Selesai di bawah 2 menit aktif atau deviasi'],
    ];
@endphp

@section('content')
    <div class="mb-7 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        @include('admin.partials.page-head', ['heading' => 'Paparan intervensi, minggu '.$week, 'sub' => 'Dosis yang ditawarkan dibanding yang benar-benar diselesaikan. Hanya kelompok intervensi dengan persetujuan berlaku.'])
        <nav class="flex gap-1 rounded-full bg-surface p-1 ring-1 ring-ink/5" aria-label="Pilih minggu">
            @foreach (range(1, 4) as $w)
                <a href="{{ route('admin.exposure.index', ['minggu' => $w]) }}" @class(['rounded-full px-4 py-2 text-sm font-semibold transition', 'bg-primary text-white' => $w === $week, 'text-ink/60 hover:bg-lavender hover:text-brand' => $w !== $week]) @if ($w === $week) aria-current="page" @endif>
                    M{{ $w }}@if ($w === $currentWeek)<span class="sr-only"> (minggu ini)</span><span class="ml-1 inline-block h-1.5 w-1.5 rounded-full {{ $w === $week ? 'bg-white' : 'bg-secondary' }} align-middle"></span>@endif
                </a>
            @endforeach
        </nav>
    </div>

    <section class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($stats as [$label, $value, $note])
            <div class="card">
                <p class="stat-label mb-3">{{ $label }}</p>
                <p class="mb-2 text-2xl font-bold num">{{ $value }}</p>
                <p class="text-xs muted">{{ $note }}</p>
            </div>
        @endforeach
    </section>

    @if ($data['schedules']->isNotEmpty())
        <div class="mb-4 flex flex-wrap gap-2">
            @foreach ($data['schedules'] as $s)
                <span class="pill-open">Sesi {{ str_pad($s->learningSession->number, 2, '0', STR_PAD_LEFT) }}, rilis {{ $s->release_at->translatedFormat('D H.i') }}</span>
            @endforeach
        </div>
    @endif

    <div class="card overflow-hidden p-0">
        <div class="overflow-x-auto">
            <table class="data-table">
                <thead><tr><th>Peserta</th><th>Sesi</th><th>Mulai</th><th class="w-48">Penyelesaian</th><th class="text-right">Waktu aktif</th><th>Status</th></tr></thead>
                <tbody>
                    @forelse ($data['rows'] as $r)
                        <tr>
                            <td><a href="{{ route('admin.participants.show', $r['user']) }}" class="font-semibold text-brand hover:underline">{{ $r['user']->username }}</a></td>
                            <td class="num"><span class="font-semibold">{{ $r['done'] }}</span><span class="muted">/{{ $r['released'] }}</span></td>
                            <td class="num whitespace-nowrap">{{ $r['start'] ?? '—' }}</td>
                            <td>
                                @if ($r['pct'] === null)<span class="muted">—</span>@else
                                    <div class="flex items-center gap-3">
                                        <div class="track flex-1"><span @class(['block h-full rounded-full', 'bg-mint-deep' => $r['pct'] >= 1, 'bg-primary' => $r['pct'] < 1]) style="width: {{ round($r['pct'] * 100) }}%"></span></div>
                                        <span class="w-10 text-right text-xs font-semibold num">{{ round($r['pct'] * 100) }}%</span>
                                    </div>
                                @endif
                            </td>
                            <td class="text-right num">{{ $r['active_minutes'] }} mnt</td>
                            <td class="whitespace-nowrap">
                                <span class="pill-{{ $r['status'][1] }}">{{ $r['status'][0] }}</span>
                                @if ($r['late'])<span class="pill-alert ml-1">Terlambat</span>@endif
                                @if ($r['anomalies'])<span class="pill-warn ml-1" data-tip="Selesai terlalu cepat atau ditandai deviasi">{{ $r['anomalies'] }} anomali</span>@endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="py-12 text-center muted">Belum ada peserta intervensi dengan persetujuan berlaku.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <p class="mt-3 text-xs muted">Kolom mulai: jam mulai sesi pertama minggu ini bila di hari rilis, atau jarak harinya. Ekspor event lengkap (rilis, buka pertama, mulai, selesai, detik aktif) tersedia di menu Ekspor dataset.</p>
@endsection
