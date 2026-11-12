@extends('layouts.admin', ['title' => 'Ringkasan kohort'])

@php
    $pct = fn (?float $r) => $r === null ? '—' : round($r * 100).'%';
    $policy = $cohort->late_policy === 'strict' ? 'strict window' : 'catch-up terkontrol';
    $tiles = [
        ['Populasi sasaran', $target ? number_format($target, 0, ',', '.') : '—', $target ? 'Terdaftar '.$overview['total'].' peserta' : 'Isi RITNOVA_TARGET_POPULATION di .env', $target ? min(1, $overview['total'] / max(1, $target)) : null, 'users'],
        ['Consent berlaku', $overview['consented'], 'dari '.$overview['total'].' peserta'.($overview['withdrawn'] ? ', '.$overview['withdrawn'].' mengundurkan diri' : ''), $overview['total'] ? $overview['consented'] / $overview['total'] : null, 'shield'],
        ['Diary valid', $pct($diary['rate']), $diary['expected'] ? number_format($diary['valid'], 0, ',', '.').' dari '.number_format($diary['expected'], 0, ',', '.').' malam' : 'Belum ada malam yang lewat', $diary['rate'], 'moon'],
        ['Sesi selesai', $pct($sessions['rate']), $sessions['expected'] ? $sessions['done'].' dari '.$sessions['expected'].' sesi dirilis (intervensi)' : 'Belum ada sesi dirilis', $sessions['rate'], 'book'],
    ];
    $phaseState = ['open' => ['Terbuka', 'pill-open'], 'scheduled' => ['Terjadwal', 'pill-wait'], 'closed' => ['Ditutup', 'pill-muted'], 'unset' => ['Belum diatur', 'pill-warn']];
    $groups = config('ritnova.groups');
    $intv = $overview['byGroup']['intervention'] ?? 0;
    $comp = $overview['byGroup']['comparison'] ?? 0;
@endphp

@section('content')
    <div class="mb-7">
        @include('admin.partials.page-head', [
            'heading' => 'Ringkasan kohort',
            'sub' => $cohort->name.($week ? ', minggu ke-'.$week.' dari 4' : ', program belum dimulai').'. Kebijakan keterlambatan: '.$policy.'.',
        ])
    </div>

    {{-- Empat metrik utama --}}
    <section class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Metrik utama">
        @foreach ($tiles as [$label, $value, $note, $rate, $icon])
            <div class="card flex flex-col">
                <div class="mb-4 flex items-center justify-between">
                    <p class="stat-label">{{ $label }}</p>
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-lavender text-brand"><x-icon :name="$icon" class="h-[18px] w-[18px]" /></span>
                </div>
                <p class="stat-value mb-2">{{ $value }}</p>
                <p class="mb-4 text-xs muted">{{ $note }}</p>
                <div class="track mt-auto"><span class="block h-full rounded-full bg-primary" style="width: {{ $rate === null ? 0 : round($rate * 100) }}%"></span></div>
            </div>
        @endforeach
    </section>

    <div class="mb-6 grid gap-6 lg:grid-cols-[1.4fr_1fr]">
        {{-- Penyelesaian modul per minggu --}}
        <section class="card">
            <div class="mb-6 flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-lg font-bold">Penyelesaian modul per minggu</h2>
                    <p class="text-sm muted">Kelompok intervensi, hanya sesi yang sudah dirilis.</p>
                </div>
                <a href="{{ route('admin.exposure.index') }}" class="btn-ghost btn-sm shrink-0">Lihat paparan</a>
            </div>
            <div class="grid h-56 grid-cols-4 items-end gap-4 border-b border-ink/10 px-2 sm:gap-8 sm:px-6">
                @foreach ($weeks as $n => $w)
                    @php $h = $w['rate'] === null ? 0 : max(4, round($w['rate'] * 100)); @endphp
                    <div class="flex h-full flex-col items-center justify-end gap-2" data-tip="Minggu {{ $n }}: {{ $w['released'] }} dari {{ $w['total'] }} sesi dirilis{{ $w['rate'] !== null ? ', '.round($w['rate'] * 100).'% selesai' : '' }}">
                        <span class="text-sm font-bold num">{{ $pct($w['rate']) }}</span>
                        @if ($w['rate'] === null)
                            <span class="block h-1/3 w-full max-w-16 rounded-t-xl border-2 border-dashed border-ink/15"></span>
                        @else
                            <span class="sleep-bar block w-full max-w-16 rounded-t-xl bg-gradient-to-t from-primary to-secondary" style="height: {{ $h }}%; transform-origin: bottom; animation-delay: {{ $n * 90 }}ms"></span>
                        @endif
                    </div>
                @endforeach
            </div>
            <div class="mt-3 grid grid-cols-4 gap-4 px-2 text-center text-xs sm:gap-8 sm:px-6">
                @foreach ($weeks as $n => $w)
                    <div><p class="font-semibold">M{{ $n }}</p><p class="muted">{{ config('ritnova.weeks')[$n] }}</p></div>
                @endforeach
            </div>
        </section>

        {{-- Tindak lanjut --}}
        <section class="panel-lavender flex flex-col">
            <h2 class="mb-1 text-lg font-bold">Tindak lanjut</h2>
            <p class="mb-5 text-sm muted">Yang perlu diperhatikan hari ini.</p>
            <ul class="space-y-2.5">
                @foreach ($followUps['items'] as $item)
                    <li>
                        <a href="{{ route($item['route']) }}" class="flex items-center gap-3 rounded-2xl bg-surface/70 px-4 py-3 transition hover:bg-surface">
                            <x-icon :name="$item['icon']" class="h-[18px] w-[18px] shrink-0 text-brand" />
                            <span class="min-w-0 flex-1 text-sm">{{ $item['label'] }}</span>
                            <span class="pill-{{ $item['tone'] }} num">{{ $item['count'] }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
            <div class="mt-auto pt-5">
                @if ($next = $followUps['next'])
                    <div class="flex items-center gap-3 rounded-2xl bg-night px-4 py-3 text-white">
                        <x-icon name="calendar" class="h-[18px] w-[18px] shrink-0 text-[#BFB4FF]" />
                        <p class="text-sm"><span class="text-white/60">Rilis berikutnya:</span> Sesi {{ str_pad($next->learningSession->number, 2, '0', STR_PAD_LEFT) }}, {{ $next->release_at->translatedFormat('l j M, H.i') }} WITA</p>
                    </div>
                @else
                    <p class="text-sm muted">Semua sesi sudah dirilis.</p>
                @endif
            </div>
        </section>
    </div>

    <div class="grid gap-6 lg:grid-cols-[1.4fr_1fr]">
        {{-- Pengukuran --}}
        <section class="card">
            <div class="mb-5 flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-lg font-bold">Kelengkapan T0, T1, T2</h2>
                    <p class="text-sm muted">Jendela dibuka sesuai kalender penelitian, tidak menunggu modul selesai.</p>
                </div>
                <a href="{{ route('admin.measurements.index') }}" class="btn-ghost btn-sm shrink-0">Detail</a>
            </div>
            <div class="grid gap-3 sm:grid-cols-3">
                @foreach ($phases as $phase => $p)
                    @php [$stateLabel, $stateClass] = $phaseState[$p['state']]; @endphp
                    <div class="rounded-2xl bg-canvas p-4 ring-1 ring-ink/5">
                        <div class="mb-3 flex items-center justify-between">
                            <span class="text-lg font-bold">{{ $phase }}</span>
                            <span class="{{ $stateClass }}">{{ $stateLabel }}</span>
                        </div>
                        <p class="mb-1 text-xs muted">{{ config('ritnova.phase_labels')[$phase] }}</p>
                        <p class="mb-3 text-xs muted num">
                            @if ($p['window']){{ $p['window']->opens_at->translatedFormat('j M') }} – {{ $p['window']->closes_at?->translatedFormat('j M') ?? 'tanpa batas' }}@else Jendela belum diatur @endif
                        </p>
                        @if ($p['instruments'] === 0)
                            <p class="text-xs font-medium text-warn-ink">Instrumen belum diisi peneliti</p>
                        @else
                            <p class="mb-1.5 text-sm"><span class="font-bold num">{{ $p['done'] }}</span><span class="muted"> / {{ $p['eligible'] }} lengkap</span></p>
                            <div class="track"><span class="block h-full rounded-full bg-mint-deep" style="width: {{ $p['eligible'] ? round($p['done'] / $p['eligible'] * 100) : 0 }}%"></span></div>
                        @endif
                    </div>
                @endforeach
            </div>
        </section>

        {{-- Komposisi --}}
        <section class="card">
            <h2 class="mb-1 text-lg font-bold">Komposisi peserta</h2>
            <p class="mb-5 text-sm muted">{{ $overview['onboarded'] }} dari {{ $overview['total'] }} sudah menyelesaikan persiapan.</p>
            <div class="mb-4 flex h-3 overflow-hidden rounded-full bg-ink/5" role="img" aria-label="Intervensi {{ $intv }}, pembanding {{ $comp }}">
                @if ($intv + $comp)
                    <span class="bg-primary" style="width: {{ $intv / ($intv + $comp) * 100 }}%"></span>
                    <span class="bg-sky-deep" style="width: {{ $comp / ($intv + $comp) * 100 }}%"></span>
                @endif
            </div>
            <dl class="grid grid-cols-2 gap-3">
                <div class="rounded-2xl bg-canvas p-4"><dt class="mb-1 flex items-center gap-2 text-sm muted"><span class="h-2.5 w-2.5 rounded-full bg-primary"></span>{{ $groups['intervention'] }}</dt><dd class="text-2xl font-bold num">{{ $intv }}</dd></div>
                <div class="rounded-2xl bg-canvas p-4"><dt class="mb-1 flex items-center gap-2 text-sm muted"><span class="h-2.5 w-2.5 rounded-full bg-sky-deep"></span>{{ $groups['comparison'] }}</dt><dd class="text-2xl font-bold num">{{ $comp }}</dd></div>
            </dl>
            @if ($unassigned = $overview['total'] - $intv - $comp)
                <p class="mt-3 text-xs text-warn-ink">{{ $unassigned }} peserta belum ditetapkan kelompoknya.</p>
            @endif
        </section>
    </div>
@endsection
