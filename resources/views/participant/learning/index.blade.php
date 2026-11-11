@extends('layouts.participant', ['title' => 'Belajar'])

@php
    $currentWeek = auth()->user()->programWeek();
    $mediaIcon = ['video' => 'play', 'infographic' => 'image', 'podcast' => 'headphones'];
@endphp

@section('content')
    <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="page-title">Perjalanan belajarmu</h1>
            <p class="mt-2 muted">Tiga sesi setiap minggu, dibuka Senin, Rabu, dan Jumat pukul 19.00 WITA.</p>
        </div>
        <div class="w-full max-w-xs">
            <div class="mb-2 flex justify-between text-sm"><span class="muted">Selesai</span><span class="font-semibold num">{{ $completedCount }} dari 12</span></div>
            <div class="track"><span style="width: {{ round($completedCount / 12 * 100) }}%"></span></div>
        </div>
    </div>

    {{-- Peta 4 minggu --}}
    <ol class="mb-10 grid grid-cols-2 gap-3 md:grid-cols-4">
        @foreach (config('ritnova.weeks') as $week => $theme)
            @php($rows = $byWeek->get($week, collect()))
            @php($done = $rows->filter(fn ($r) => $r['progress']?->completed_at)->count())
            <li @class(['rounded-[var(--radius-card)] p-4', 'bg-night text-white' => $week === $currentWeek, 'bg-white ring-1 ring-night/5' => $week !== $currentWeek])>
                <p @class(['mb-1 text-sm font-semibold', 'text-lavender' => $week === $currentWeek, 'text-secondary' => $week !== $currentWeek])>Minggu {{ $week }}{{ $week === $currentWeek ? ' · sekarang' : '' }}</p>
                <p class="mb-3 font-bold">{{ $theme }}</p>
                <div class="flex gap-1.5">
                    @foreach (range(1, 3) as $i)
                        <span @class(['h-1.5 flex-1 rounded-full', 'bg-primary' => $i <= $done && $week !== $currentWeek, 'bg-lavender' => $i <= $done && $week === $currentWeek, 'bg-night/10' => $i > $done && $week !== $currentWeek, 'bg-white/15' => $i > $done && $week === $currentWeek])></span>
                    @endforeach
                </div>
            </li>
        @endforeach
    </ol>

    {{-- Sesi yang tersedia --}}
    @foreach ($available as $row)
        <a href="{{ route('learning.show', $row['session']) }}" class="panel-lavender mb-4 flex flex-col gap-5 transition hover:bg-lavender-deep sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-4">
                <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-primary text-white"><x-icon :name="$mediaIcon[$row['session']->media_type] ?? 'book'" class="h-6 w-6" /></span>
                <div>
                    <p class="text-sm font-semibold text-primary num">Sesi {{ str_pad($row['session']->number, 2, '0', STR_PAD_LEFT) }} tersedia{{ $row['decision']->isLate ? ' (terlambat)' : '' }}</p>
                    <p class="text-xl font-bold">{{ $row['session']->title }}</p>
                    <p class="text-sm text-night/60">{{ config('ritnova.media_labels')[$row['session']->media_type] ?? '' }} dan satu aksi, sekitar {{ $row['session']->estimated_minutes ?? 8 }} menit</p>
                </div>
            </div>
            <span class="btn-primary shrink-0">{{ $row['progress'] ? 'Lanjutkan sesi' : 'Mulai sesi' }}</span>
        </a>
    @endforeach

    {{-- Semua sesi per minggu --}}
    <div class="mt-10 grid gap-8 lg:grid-cols-2">
        @foreach ($byWeek as $week => $rows)
            <section>
                <h2 class="mb-3 font-bold">Minggu {{ $week }} <span class="font-normal muted">{{ config('ritnova.weeks')[$week] ?? '' }}</span></h2>
                <div class="card overflow-hidden p-0">
                    @foreach ($rows as $row)
                        @php($d = $row['decision'])
                        @php($open = $d->canStart() || $d->status->value === 'completed')
                        <{{ $open ? 'a' : 'div' }} @if ($open) href="{{ route('learning.show', $row['session']) }}" @endif @class(['flex items-center gap-4 border-b border-night/5 px-5 py-4 last:border-0', 'hover:bg-canvas' => $open])>
                            <span @class(['flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-sm font-bold num', 'bg-primary text-white' => $d->status->value === 'completed', 'bg-lavender text-primary' => $d->canStart(), 'bg-night/5 text-night/40' => ! $open])>
                                @if ($d->status->value === 'completed') <x-icon name="check" class="h-4 w-4" /> @else {{ str_pad($row['session']->number, 2, '0', STR_PAD_LEFT) }} @endif
                            </span>
                            <span class="min-w-0 flex-1">
                                <span @class(['block truncate font-semibold', 'text-night/45' => ! $open])>{{ $row['session']->title }}</span>
                                <span class="flex items-center gap-1.5 text-sm muted"><x-icon :name="$mediaIcon[$row['session']->media_type] ?? 'book'" class="h-3.5 w-3.5" />{{ config('ritnova.media_labels')[$row['session']->media_type] ?? '' }}</span>
                            </span>
                            @include('participant.learning.partials.status-badge', ['row' => $row])
                        </{{ $open ? 'a' : 'div' }}>
                    @endforeach
                </div>
            </section>
        @endforeach
    </div>

    @if ($sessions->isEmpty())
        <div class="card text-center muted">Jadwal sesi untuk kohortmu belum ditetapkan. Hubungi tim peneliti bila ini berlangsung lama.</div>
    @endif
@endsection
