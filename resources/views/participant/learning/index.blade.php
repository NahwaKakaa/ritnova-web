@extends('layouts.participant', ['title' => 'Belajar'])

@section('content')
    @php $done = $sessions->filter(fn ($r) => $r['progress']?->completed_at)->count(); @endphp
    <h1 class="mb-1 text-2xl font-bold">Perjalanan Ritme Tidurmu</h1>
    <p class="mb-3 text-ink/60">{{ $done }} dari 12 sesi selesai</p>
    <div class="mb-6 h-2 rounded-full bg-lavender"><div class="h-2 rounded-full bg-primary" style="width: {{ round($done / 12 * 100) }}%"></div></div>

    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($sessions as $row)
            @php($d = $row['decision'])
            <div class="card">
                <p class="text-xs font-semibold text-ink/50">SESI {{ str_pad($row['session']->number, 2, '0', STR_PAD_LEFT) }} · Minggu {{ $row['session']->week }}</p>
                <p class="mb-3 font-semibold">{{ $row['session']->title }}</p>
                <span @class(['badge',
                    'bg-green-100 text-green-800' => $d->status->value === 'completed',
                    'bg-lavender text-primary' => $d->status->value === 'available',
                    'bg-sky/60 text-ink' => $d->status->value === 'locked_time',
                    'bg-peach text-ink' => in_array($d->status->value, ['locked_prerequisite', 'expired']),
                ])>
                    @if ($d->status->value === 'locked_time')
                        Rilis {{ $row['schedule']->release_at->translatedFormat('l, d M · H.i') }} WITA
                    @else
                        {{ $d->status->label() }}{{ $d->isLate ? ' · terlambat' : '' }}
                    @endif
                </span>
                @if ($d->canStart() || $d->status->value === 'completed')
                    <a href="{{ route('learning.show', $row['session']) }}" class="mt-3 block text-sm font-semibold text-primary">{{ $d->canStart() ? 'Mulai sesi →' : 'Buka kembali →' }}</a>
                @endif
            </div>
        @endforeach
    </div>
@endsection
