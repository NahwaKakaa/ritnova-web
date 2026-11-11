@extends('layouts.participant', ['title' => 'Catatan Tidur'])

@section('content')
    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-2xl font-bold">Catatan Tidur</h1>
        <a href="{{ route('diary.create') }}" class="btn-primary px-4 py-2 text-sm">+ Catat</a>
    </div>

    <div class="grid grid-cols-7 gap-2 text-center text-xs">
        @foreach (range(27, 0) as $daysAgo)
            @php($date = now()->subDays($daysAgo)->toDateString())
            @php($diary = $diaries->get($date))
            <a href="{{ $diary ? route('diary.edit', $diary) : route('diary.create', ['date' => $date]) }}"
               @class(['rounded-xl p-2', 'bg-lavender' => $diary?->status === 'complete', 'bg-peach' => $diary?->status === 'partial', 'bg-white ring-1 ring-black/5' => ! $diary])>
                <span class="block text-ink/50">{{ \Illuminate\Support\Carbon::parse($date)->translatedFormat('D') }}</span>
                <span class="block font-semibold">{{ \Illuminate\Support\Carbon::parse($date)->format('d') }}</span>
                <span>{{ $diary ? ($diary->status === 'complete' ? '✓' : '!') : '–' }}</span>
            </a>
        @endforeach
    </div>
    <p class="mt-4 text-xs text-ink/50">✓ lengkap · ! belum lengkap · – belum diisi. Tanggal = malam mulai tidur.</p>
@endsection
