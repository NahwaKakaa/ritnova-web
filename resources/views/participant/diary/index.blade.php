@extends('layouts.participant', ['title' => 'Sleep Tracker'])

@php
    // Sumbu grafik: 18.00 malam sampai 12.00 siang berikutnya (18 jam).
    $span = 18 * 60;
    $pos = function ($diary) use ($span) {
        $axisStart = $diary->diary_date->copy()->setTime(18, 0);
        $top = $diary->sleep_onset ? max(0, min($span, $axisStart->diffInMinutes($diary->sleep_onset))) : null;
        $bottom = $diary->sleep_offset ? max(0, min($span, $axisStart->diffInMinutes($diary->sleep_offset))) : null;
        return [$top, $bottom];
    };
@endphp

@section('content')
    <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="page-title">Catatan tidur</h1>
            <p class="mt-2 muted">Satu batang = satu malam, dari jam mulai tidur sampai bangun.</p>
        </div>
        <a href="{{ route('diary.create') }}" class="btn-primary"><x-icon name="plus" class="h-4 w-4" />Catat tidur</a>
    </div>

    {{-- Grafik minggu ini --}}
    <section class="card mb-6 sm:p-6">
        <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-bold">Minggu ini</h2>
            <div class="flex gap-4 text-xs muted">
                <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-sky-deep"></span>Hari kuliah</span>
                <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-peach-deep"></span>Hari bebas</span>
            </div>
        </div>

        <div class="grid grid-cols-[2.5rem_1fr] gap-2">
            <div class="relative h-64 text-right text-[11px] muted num" aria-hidden="true">
                @foreach (['18.00' => 0, '00.00' => 360, '06.00' => 720, '12.00' => 1080] as $label => $min)
                    <span class="absolute right-0 -translate-y-1/2" style="top: {{ $min / $span * 100 }}%">{{ $label }}</span>
                @endforeach
            </div>
            <div class="relative grid h-64 grid-cols-7 gap-2 sm:gap-3">
                @foreach ([360, 720] as $min)
                    <span class="pointer-events-none absolute inset-x-0 border-t border-dashed border-night/10" style="top: {{ $min / $span * 100 }}%"></span>
                @endforeach
                @foreach ($week as $day)
                    @php($diary = $diaries->get($day->toDateString()))
                    @php($future = $day->isAfter(now()->startOfDay()) || $day->isToday())
                    @php([$top, $bottom] = $diary ? $pos($diary) : [null, null])
                    <a href="{{ $future ? '#' : ($diary ? route('diary.edit', $diary) : route('diary.create', ['date' => $day->toDateString()])) }}"
                       @class(['relative rounded-xl transition', 'pointer-events-none' => $future, 'bg-canvas hover:bg-lavender/60' => ! $future, 'bg-canvas/40' => $future])
                       aria-label="{{ $day->translatedFormat('l, d F') }}: {{ $diary ? ($diary->status === 'complete' ? 'lengkap' : 'belum lengkap') : 'belum diisi' }}">
                        @if ($diary && $top !== null)
                            <span @class(['absolute inset-x-1.5 rounded-lg sm:inset-x-2.5',
                                'bg-sky-deep' => $diary->day_type === 'workday' && $bottom !== null,
                                'bg-peach-deep' => $diary->day_type === 'free_day' && $bottom !== null,
                                'border-2 border-dashed border-peach-deep bg-peach/40' => $bottom === null,
                            ]) style="top: {{ $top / $span * 100 }}%; height: {{ (($bottom ?? min($span, $top + 120)) - $top) / $span * 100 }}%"></span>
                        @elseif (! $future)
                            <span class="absolute inset-x-0 top-1/2 -translate-y-1/2 text-center text-xl text-night/20">+</span>
                        @endif
                    </a>
                @endforeach
            </div>
            <span></span>
            <div class="grid grid-cols-7 gap-2 text-center text-xs sm:gap-3">
                @foreach ($week as $day)
                    @php($diary = $diaries->get($day->toDateString()))
                    <div>
                        <p class="font-semibold">{{ $day->translatedFormat('D') }}</p>
                        <p class="muted num">{{ $diary?->sleep_onset ? $diary->sleep_onset->format('H.i') : '' }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    @if ($issues->isNotEmpty())
        @php($first = $issues->first())
        <section class="mb-8 flex flex-col gap-4 rounded-[var(--radius-card)] bg-peach/45 p-5 sm:flex-row sm:items-center sm:justify-between">
            <ul class="space-y-1 text-[15px]">
                @foreach ($issues as $issue)
                    <li><b>{{ $issue['day']->translatedFormat('l') }}:</b> {{ $issue['missing'] }}</li>
                @endforeach
            </ul>
            <a href="{{ $first['diary'] ? route('diary.edit', $first['diary']) : route('diary.create', ['date' => $first['day']->toDateString()]) }}" class="btn-primary shrink-0">Lengkapi catatan</a>
        </section>
    @endif

    {{-- Riwayat 5 minggu --}}
    <section>
        <h2 class="mb-3 font-bold">Riwayat lima minggu</h2>
        <div class="card sm:p-6">
            <div class="grid grid-cols-7 gap-1.5 text-center text-xs sm:gap-2">
                @foreach (['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'] as $label)
                    <span class="pb-1 font-semibold muted">{{ $label }}</span>
                @endforeach
                @php($start = now()->startOfWeek()->subWeeks(4))
                @foreach (range(0, 34) as $offset)
                    @php($day = $start->copy()->addDays($offset))
                    @php($diary = $diaries->get($day->toDateString()))
                    @php($future = $day->isAfter(now()->startOfDay()) || $day->isToday())
                    <a href="{{ $future ? '#' : ($diary ? route('diary.edit', $diary) : route('diary.create', ['date' => $day->toDateString()])) }}"
                       @class(['flex aspect-square flex-col items-center justify-center rounded-xl num',
                           'pointer-events-none text-night/20' => $future,
                           'bg-sky/70' => $diary?->status === 'complete' && $diary->day_type === 'workday',
                           'bg-peach' => $diary?->status === 'complete' && $diary->day_type === 'free_day',
                           'border-2 border-dashed border-peach-deep bg-white' => $diary && $diary->status !== 'complete',
                           'bg-canvas hover:bg-lavender' => ! $diary && ! $future])
                       title="{{ $diary && $diary->revisions_count ? $diary->revisions_count.' kali diedit' : '' }}">
                        <span class="font-semibold">{{ $day->format('j') }}</span>
                        @if ($diary && $diary->status !== 'complete')<span class="text-[10px] text-[#8A4B22]">belum</span>@endif
                    </a>
                @endforeach
            </div>
            <p class="mt-4 text-xs muted">Kotak berwarna berarti catatan lengkap. Hari tanpa catatan tidak dihitung sebagai SJL nol.</p>
        </div>
    </section>
@endsection
