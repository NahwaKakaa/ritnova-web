@extends('layouts.participant', ['title' => 'Progres'])

@php
    $pct = round($completedCount / 12 * 100);
    $mediaIcon = ['video' => 'play', 'infographic' => 'image', 'podcast' => 'headphones'];
    $max = max(180, collect($insights)->max('minutes') ?? 0);
@endphp

@section('content')
    {{-- Progres 12 sesi --}}
    <section class="panel-night mb-8 grid gap-6 p-6 sm:p-8 md:grid-cols-[auto_1fr] md:items-center md:p-10">
        <div class="relative mx-auto h-36 w-36">
            <svg viewBox="0 0 120 120" class="h-full w-full -rotate-90" aria-hidden="true">
                <circle cx="60" cy="60" r="50" fill="none" stroke="rgb(255 255 255 / 0.1)" stroke-width="12" />
                <circle cx="60" cy="60" r="50" fill="none" stroke="#BFB4FF" stroke-width="12" stroke-linecap="round" stroke-dasharray="{{ round(2 * M_PI * 50, 1) }}" stroke-dashoffset="{{ round(2 * M_PI * 50 * (1 - $completedCount / 12), 1) }}" />
            </svg>
            <span class="absolute inset-0 flex flex-col items-center justify-center"><span class="text-3xl font-bold num">{{ $pct }}%</span><span class="text-xs text-white/55">selesai</span></span>
        </div>
        <div>
            <h1 class="mb-2 text-[30px] leading-tight font-bold sm:text-4xl">Perjalanan ritme tidurmu</h1>
            <p class="text-white/65">{{ $completedCount }} dari 12 sesi selesai{{ $week ? ', sekarang minggu '.$week.' dari 4' : '' }}. Setiap status menjelaskan kenapa sesi terbuka atau masih menunggu.</p>
        </div>
    </section>

    <div class="mb-12 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($sessions as $row)
            @php($d = $row['decision'])
            @php($open = $d->canStart() || $d->status->value === 'completed')
            <{{ $open ? 'a' : 'div' }} @if ($open) href="{{ route('learning.show', $row['session']) }}" @endif @class(['flex items-start gap-3 rounded-[var(--radius-card)] p-4', 'bg-white shadow-[var(--shadow-card)] ring-1 ring-night/5 hover:ring-primary/30' => $open, 'bg-white/50 ring-1 ring-night/5' => ! $open])>
                <span @class(['flex h-9 w-9 shrink-0 items-center justify-center rounded-xl', 'bg-primary text-white' => $d->status->value === 'completed', 'bg-lavender text-primary' => $d->canStart(), 'bg-night/5 text-night/35' => ! $open])><x-icon :name="$mediaIcon[$row['session']->media_type] ?? 'book'" class="h-4 w-4" /></span>
                <span class="min-w-0 flex-1">
                    <span class="block text-xs font-semibold text-secondary num">Sesi {{ str_pad($row['session']->number, 2, '0', STR_PAD_LEFT) }}</span>
                    <span @class(['mb-2 block leading-snug font-semibold', 'text-night/45' => ! $open])>{{ $row['session']->title }}</span>
                    @include('participant.learning.partials.status-badge', ['row' => $row])
                </span>
            </{{ $open ? 'a' : 'div' }}>
        @endforeach
    </div>

    {{-- Insights --}}
    <section class="grid gap-8 lg:grid-cols-[0.8fr_1.2fr] lg:items-center">
        <div>
            <h2 class="mb-2 text-2xl font-bold">Perkembangan social jetlag</h2>
            <p class="mb-4 muted">Dihitung dari catatan tidurmu di sekitar setiap periode pengukuran.</p>
            <p class="text-sm muted">Perubahan dipengaruhi banyak hal, misalnya jadwal kuliah dan ujian. Grafik ini tidak menyatakan kondisi medis.</p>
        </div>

        <div class="card sm:p-6">
            <div class="relative flex h-60 items-end gap-4 border-b border-night/10 pt-6 sm:gap-8" role="img" aria-label="Grafik social jetlag pada T0, T1, dan T2 dalam jam">
                @foreach ([60, 120, 180] as $line)
                    @if ($line <= $max)
                        <span class="pointer-events-none absolute inset-x-0 border-t border-dashed border-night/10 text-[10px] muted" style="bottom: {{ $line / $max * 85 }}%"><span class="absolute -top-2 -left-1 bg-white pr-1 num">{{ $line / 60 }}j</span></span>
                    @endif
                @endforeach
                @foreach ($insights as $phase => $item)
                    <div class="relative z-10 flex h-full flex-1 flex-col items-center justify-end gap-2">
                        @if ($item['minutes'] !== null)
                            <span class="text-sm font-bold text-primary num">{{ number_format($item['minutes'] / 60, 1, ',', '') }} j</span>
                            <div class="w-full max-w-20 rounded-t-xl bg-gradient-to-t from-primary to-secondary" style="height: {{ max(3, round($item['minutes'] / $max * 85)) }}%"></div>
                        @else
                            <span class="text-center text-xs muted">belum<br>cukup data</span>
                            <div class="h-6 w-full max-w-20 rounded-t-xl border-2 border-b-0 border-dashed border-night/15"></div>
                        @endif
                    </div>
                @endforeach
            </div>
            <div class="flex gap-4 pt-3 text-center sm:gap-8">
                @foreach ($insights as $phase => $item)
                    <div class="flex-1">
                        <p class="font-bold">{{ $phase }}</p>
                        <p class="text-xs muted num">{{ $item['range'] ?? 'belum dijadwalkan' }}</p>
                        <p class="text-xs muted num">{{ $item['nights'] }} malam lengkap</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endsection
