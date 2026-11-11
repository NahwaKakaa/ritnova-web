@extends('layouts.participant', ['title' => 'Pengukuran'])

@php
    $badges = [
        'done' => ['Sudah diisi', 'bg-green-100 text-green-800'],
        'open' => ['Terbuka', 'bg-lavender text-primary'],
        'no_instrument' => ['Terbuka · instrumen disiapkan', 'bg-lavender text-primary'],
        'scheduled' => ['Terjadwal', 'bg-sky/60 text-ink'],
        'closed' => ['Ditutup', 'bg-canvas text-ink/60'],
    ];
@endphp

@section('content')
    <h1 class="mb-1 text-2xl font-bold">Pengukuran Penelitian</h1>
    <p class="mb-6 text-ink/60">Instrumen ilmiah terpisah dari modul belajar dan dibuka sesuai jadwal pengukuran.</p>

    <div class="space-y-4">
        @foreach ($phases as $p)
            <section class="card">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <p class="text-lg font-bold">{{ $p['phase'] }} <span class="text-base font-normal text-ink/60">· {{ $p['label'] }}</span></p>
                        <p class="text-sm text-ink/60">
                            @if ($p['window'])
                                {{ $p['window']->opens_at->translatedFormat('d M Y, H.i') }} – {{ $p['window']->closes_at?->translatedFormat('d M Y, H.i') ?? 'selesai' }} WITA
                            @else
                                Jadwal ditetapkan peneliti
                            @endif
                        </p>
                    </div>
                    <span class="badge {{ $badges[$p['state']][1] }}">{{ $badges[$p['state']][0] }}</span>
                </div>

                @if ($p['instruments']->isNotEmpty())
                    <div class="mt-4 space-y-2">
                        @foreach ($p['instruments'] as $row)
                            <div class="flex items-center justify-between gap-3 rounded-xl bg-canvas px-4 py-3 text-sm">
                                <span>{{ $row['instrument']->name }}</span>
                                @if ($row['submitted'])
                                    <span class="text-green-700">✓ Terkirim</span>
                                @elseif ($p['window']?->isOpen())
                                    <a href="{{ route('assessments.show', [$p['phase'], $row['instrument']]) }}" class="btn-primary px-4 py-1.5 text-sm">Isi</a>
                                @else
                                    <span class="text-ink/50">Belum dibuka</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @elseif ($p['state'] === 'no_instrument')
                    <p class="mt-3 text-sm text-ink/60">Instrumen untuk fase ini sedang disiapkan tim peneliti.</p>
                @endif
            </section>
        @endforeach
    </div>

    <p class="mt-6 text-xs text-ink/50">Konstruk yang diukur: derajat social jetlag, sleep self-efficacy, dan chronotype (baseline). Instrumen dan skoring ditetapkan setelah telaah validitas.</p>
@endsection
