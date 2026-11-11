@extends('layouts.participant', ['title' => 'Sleep Tracker'])

@section('content')
    <div class="mb-6 flex items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold">Catatan Tidur Minggu Ini</h1>
            <p class="text-sm text-ink/60">Tanggal = malam mulai tidur. Jenis hari mengikuti hari setelah bangun.</p>
        </div>
        <a href="{{ route('diary.create') }}" class="btn-primary shrink-0 px-4 py-2 text-sm">+ Catat</a>
    </div>

    {{-- Minggu ini (Senin–Minggu) --}}
    <div class="card mb-4 grid grid-cols-7 gap-2 p-3 text-center text-xs sm:p-5">
        @foreach ($week as $day)
            @php($diary = $diaries->get($day->toDateString()))
            @php($future = $day->isAfter(now()->startOfDay()))
            <a href="{{ $future ? '#' : ($diary ? route('diary.edit', $diary) : route('diary.create', ['date' => $day->toDateString()])) }}"
               @class(['rounded-xl p-2', 'pointer-events-none opacity-40' => $future, 'bg-lavender' => $diary?->status === 'complete', 'bg-peach' => $diary && $diary->status !== 'complete', 'bg-canvas' => ! $diary])>
                <span class="block font-semibold text-ink/60">{{ $day->translatedFormat('D') }}</span>
                <span class="block text-lg">{{ $diary ? ($diary->status === 'complete' ? '✓' : '!') : ($future ? '·' : '–') }}</span>
                @if ($diary)
                    <span class="hidden text-[11px] text-ink/60 sm:block">{{ $diary->sleep_onset?->format('H.i') ?? '?' }}–{{ $diary->sleep_offset?->format('H.i') ?? '?' }}</span>
                    <span class="hidden text-[11px] text-ink/50 sm:block">{{ $diary->day_type === 'free_day' ? 'bebas' : 'kuliah' }}</span>
                @endif
            </a>
        @endforeach
    </div>

    @if ($issues->isNotEmpty())
        <div class="card mb-6 bg-peach/40">
            <ul class="mb-3 space-y-1 text-sm">
                @foreach ($issues as $issue)
                    <li><b>{{ $issue['day']->translatedFormat('l') }}:</b> {{ $issue['missing'] }}</li>
                @endforeach
            </ul>
            @php($first = $issues->first())
            <a href="{{ $first['diary'] ? route('diary.edit', $first['diary']) : route('diary.create', ['date' => $first['day']->toDateString()]) }}" class="btn-primary px-4 py-2 text-sm">Lengkapi catatan</a>
        </div>
    @endif

    {{-- Riwayat 5 minggu --}}
    <h2 class="mb-3 font-semibold">Riwayat</h2>
    <div class="grid grid-cols-7 gap-2 text-center text-xs">
        @foreach (['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'] as $label)
            <span class="text-ink/50">{{ $label }}</span>
        @endforeach
        @php($start = now()->startOfWeek()->subWeeks(4))
        @foreach (range(0, 34) as $offset)
            @php($day = $start->copy()->addDays($offset))
            @php($diary = $diaries->get($day->toDateString()))
            @php($future = $day->isAfter(now()->startOfDay()))
            <a href="{{ $future ? '#' : ($diary ? route('diary.edit', $diary) : route('diary.create', ['date' => $day->toDateString()])) }}"
               @class(['rounded-lg p-1.5', 'pointer-events-none opacity-30' => $future, 'bg-lavender' => $diary?->status === 'complete', 'bg-peach' => $diary && $diary->status !== 'complete', 'bg-white ring-1 ring-black/5' => ! $diary && ! $future])
               title="{{ $diary && $diary->revisions_count ? $diary->revisions_count.' kali diedit' : '' }}">
                <span class="font-semibold">{{ $day->format('d') }}</span>
                <span class="block">{{ $diary ? ($diary->status === 'complete' ? '✓' : '!') : '' }}</span>
            </a>
        @endforeach
    </div>
    <p class="mt-4 text-xs text-ink/50">✓ lengkap · ! belum lengkap · kosong = belum diisi. Tidak ada data tidak sama dengan SJL nol.</p>
@endsection
