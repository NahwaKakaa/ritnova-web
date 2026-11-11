@extends('layouts.participant', ['title' => 'Pengukuran'])

@php
    $badges = [
        'done' => ['Sudah diisi', 'pill-done'],
        'open' => ['Terbuka', 'pill-open'],
        'no_instrument' => ['Terbuka, instrumen disiapkan', 'pill-open'],
        'scheduled' => ['Terjadwal', 'pill-wait'],
        'closed' => ['Ditutup', 'pill-muted'],
    ];
@endphp

@section('content')
    <div class="mb-8">
        <h1 class="page-title">Pengukuran penelitian</h1>
        <p class="mt-2 max-w-xl muted">Kuesioner ilmiah, terpisah dari sesi belajar. Setiap pengukuran dibuka sesuai jadwal.</p>
    </div>

    <ol class="relative space-y-5 border-l-2 border-lavender pl-6 sm:pl-8">
        @foreach ($phases as $p)
            <li class="relative">
                <span @class(['absolute top-6 -left-[33px] flex h-4 w-4 items-center justify-center rounded-full ring-4 ring-canvas sm:-left-[41px]', 'bg-mint-deep' => $p['state'] === 'done', 'bg-primary' => in_array($p['state'], ['open', 'no_instrument']), 'bg-lavender-deep' => in_array($p['state'], ['scheduled', 'closed'])])></span>
                <section @class(['card sm:p-6', 'ring-2 ring-primary/40' => $p['state'] === 'open'])>
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h2 class="text-xl font-bold">{{ $p['phase'] }} <span class="text-base font-normal muted">{{ $p['label'] }}</span></h2>
                            <p class="mt-1 flex items-center gap-1.5 text-sm muted num"><x-icon name="calendar" class="h-4 w-4" />
                                @if ($p['window'])
                                    {{ $p['window']->opens_at->translatedFormat('d M, H.i') }} – {{ $p['window']->closes_at?->translatedFormat('d M, H.i') ?? 'selesai' }} WITA
                                @else
                                    Jadwal ditetapkan peneliti
                                @endif
                            </p>
                        </div>
                        <span class="{{ $badges[$p['state']][1] }}">{{ $badges[$p['state']][0] }}</span>
                    </div>

                    @if ($p['instruments']->isNotEmpty())
                        <div class="mt-5 space-y-2">
                            @foreach ($p['instruments'] as $row)
                                <div class="flex items-center justify-between gap-3 rounded-2xl bg-canvas px-4 py-3">
                                    <span class="flex items-center gap-3"><x-icon name="clipboard" class="h-4 w-4 text-brand" />{{ $row['instrument']->name }}</span>
                                    @if ($row['submitted'])
                                        <span class="pill-done"><x-icon name="check" class="h-3.5 w-3.5" />Terkirim</span>
                                    @elseif ($p['window']?->isOpen())
                                        <a href="{{ route('assessments.show', [$p['phase'], $row['instrument']]) }}" class="btn-primary btn-sm">Isi sekarang</a>
                                    @else
                                        <span class="text-sm muted">Belum dibuka</span>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @elseif ($p['state'] === 'no_instrument')
                        <p class="mt-4 text-sm muted">Kuesioner untuk pengukuran ini sedang disiapkan tim peneliti.</p>
                    @endif
                </section>
            </li>
        @endforeach
    </ol>

    <p class="mt-8 text-sm muted">Yang diukur: derajat social jetlag, keyakinan mengelola tidur (sleep self-efficacy), dan kronotipe di awal program.</p>
@endsection
