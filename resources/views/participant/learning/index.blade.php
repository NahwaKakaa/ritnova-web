@extends('layouts.participant', ['title' => 'Belajar'])

@section('content')
    <h1 class="mb-1 text-2xl font-bold">Perjalanan Belajarmu</h1>
    <p class="mb-6 text-ink/60">{{ $completedCount }} dari 12 sesi selesai · 3 sesi per minggu, rilis Senin/Rabu/Jumat 19.00 WITA</p>

    {{-- Peta 4 minggu --}}
    <div class="mb-8 grid grid-cols-2 gap-3 md:grid-cols-4">
        @foreach (config('ritnova.weeks') as $week => $theme)
            @php($rows = $byWeek->get($week, collect()))
            @php($done = $rows->filter(fn ($r) => $r['progress']?->completed_at)->count())
            <div class="card p-4">
                <p class="text-xs font-semibold text-secondary">M{{ $week }}</p>
                <p class="font-semibold">{{ $theme }}</p>
                <p class="mb-2 text-xs text-ink/50">Sesi {{ str_pad(($week - 1) * 3 + 1, 2, '0', STR_PAD_LEFT) }}–{{ str_pad($week * 3, 2, '0', STR_PAD_LEFT) }}</p>
                <div class="h-1.5 rounded-full bg-lavender"><div class="h-1.5 rounded-full bg-primary" style="width: {{ $rows->count() ? round($done / $rows->count() * 100) : 0 }}%"></div></div>
            </div>
        @endforeach
    </div>

    {{-- Sesi yang tersedia hari ini --}}
    @if ($available->isNotEmpty())
        <h2 class="mb-3 font-semibold">Sesi yang tersedia sekarang</h2>
        <div class="mb-8 grid gap-3 md:grid-cols-2">
            @foreach ($available as $row)
                <a href="{{ route('learning.show', $row['session']) }}" class="card flex items-center justify-between gap-4 bg-lavender hover:ring-primary/40">
                    <div>
                        <p class="text-xs font-semibold text-primary">SESI {{ str_pad($row['session']->number, 2, '0', STR_PAD_LEFT) }}{{ $row['decision']->isLate ? ' · terlambat' : '' }}</p>
                        <p class="font-semibold">{{ $row['session']->title }}</p>
                        <p class="text-sm text-ink/60">{{ config('ritnova.media_labels')[$row['session']->media_type] ?? $row['session']->media_type }} + aksi · estimasi {{ $row['session']->estimated_minutes ?? 8 }} menit</p>
                    </div>
                    <span class="btn-primary px-4 py-2 text-sm">Mulai</span>
                </a>
            @endforeach
        </div>
    @endif

    {{-- Semua sesi per minggu --}}
    @foreach ($byWeek as $week => $rows)
        <h2 class="mb-3 font-semibold">Minggu {{ $week }} · {{ config('ritnova.weeks')[$week] ?? '' }}</h2>
        <div class="mb-6 grid gap-3 sm:grid-cols-3">
            @foreach ($rows as $row)
                @php($d = $row['decision'])
                <div class="card p-4">
                    <p class="text-xs font-semibold text-ink/50">SESI {{ str_pad($row['session']->number, 2, '0', STR_PAD_LEFT) }} · {{ config('ritnova.media_labels')[$row['session']->media_type] ?? '' }}</p>
                    <p class="mb-3 font-semibold">{{ $row['session']->title }}</p>
                    @include('participant.learning.partials.status-badge', ['row' => $row])
                    @if ($d->canStart() || $d->status->value === 'completed')
                        <a href="{{ route('learning.show', $row['session']) }}" class="mt-3 block text-sm font-semibold text-primary">{{ $d->canStart() ? 'Mulai sesi →' : 'Buka kembali →' }}</a>
                    @elseif ($d->status->value === 'locked_time')
                        <p class="mt-2 text-xs text-ink/50">{{ $row['schedule']->release_at->translatedFormat('d M · H.i') }} WITA</p>
                    @endif
                </div>
            @endforeach
        </div>
    @endforeach

    @if ($sessions->isEmpty())
        <div class="card text-ink/60">Jadwal sesi untuk kohortmu belum ditetapkan. Hubungi tim peneliti bila ini berlangsung lama.</div>
    @endif
@endsection
