@extends('layouts.admin', ['title' => 'Pengukuran T0–T2'])

@php
    $state = ['open' => ['Terbuka', 'pill-open'], 'scheduled' => ['Terjadwal', 'pill-wait'], 'closed' => ['Ditutup', 'pill-muted'], 'unset' => ['Belum diatur', 'pill-warn']];
    $min = config('ritnova.sjl.min_workdays') + config('ritnova.sjl.min_free_days');
@endphp

@section('content')
    <div class="mb-7">
        @include('admin.partials.page-head', ['heading' => 'Pengukuran T0, T1, T2', 'sub' => 'Pengukuran penelitian, bukan hadiah setelah sesi. T1 dan T2 tetap dibuka sesuai kalender walau peserta belum menuntaskan modul.'])
    </div>

    <form method="POST" action="{{ route('admin.measurements.windows') }}" class="mb-6 grid gap-4 lg:grid-cols-3">
        @csrf @method('PUT')
        @foreach ($phases as $phase => $p)
            @php [$label, $class] = $state[$p['state']]; @endphp
            <section class="card">
                <div class="mb-1 flex items-center justify-between">
                    <h2 class="text-xl font-bold">{{ $phase }}</h2>
                    <span class="{{ $class }}">{{ $label }}</span>
                </div>
                <p class="mb-4 text-sm muted">{{ config('ritnova.phase_labels')[$phase] }}</p>
                @if ($p['instruments'])
                    <p class="mb-1.5 text-sm"><span class="text-2xl font-bold num">{{ $p['done'] }}</span><span class="muted"> / {{ $p['eligible'] }} lengkap</span></p>
                    <div class="track mb-5"><span class="block h-full rounded-full bg-mint-deep" style="width: {{ $p['eligible'] ? round($p['done'] / $p['eligible'] * 100) : 0 }}%"></span></div>
                @else
                    <p class="mb-5 rounded-xl bg-peach/50 px-3 py-2 text-sm text-warn-ink">Belum ada instrumen untuk {{ $phase }}. <a href="{{ route('admin.instruments.index') }}" class="font-semibold underline">Tambahkan</a></p>
                @endif
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-1 xl:grid-cols-2">
                    <div>
                        <label class="label text-xs" for="w-{{ $phase }}-open">Dibuka (WITA)</label>
                        <input id="w-{{ $phase }}-open" type="datetime-local" name="windows[{{ $phase }}][opens_at]" value="{{ old("windows.$phase.opens_at", $p['window']?->opens_at->format('Y-m-d\TH:i')) }}" class="input px-3 py-2 text-sm" @disabled(! $canManage) required>
                    </div>
                    <div>
                        <label class="label text-xs" for="w-{{ $phase }}-close">Ditutup (WITA)</label>
                        <input id="w-{{ $phase }}-close" type="datetime-local" name="windows[{{ $phase }}][closes_at]" value="{{ old("windows.$phase.closes_at", $p['window']?->closes_at?->format('Y-m-d\TH:i')) }}" class="input px-3 py-2 text-sm" @disabled(! $canManage)>
                    </div>
                </div>
            </section>
        @endforeach
        @if ($canManage)
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center lg:col-span-3">
                <button class="btn-primary">Simpan jendela pengukuran</button>
                <p class="text-sm muted">Diary untuk SJL tiap fase: 7 hari sebelum jendela dibuka sampai jendela ditutup.</p>
            </div>
        @endif
    </form>

    <div class="grid gap-6 xl:grid-cols-[1fr_22rem]">
        <section class="card overflow-hidden p-0">
            <div class="px-5 pt-5 pb-3">
                <h2 class="font-bold">Status per peserta</h2>
                <p class="text-sm muted">Malam lengkap dihitung pada rentang diary tiap fase. SJL butuh minimal {{ config('ritnova.sjl.min_workdays') }} hari kuliah dan {{ config('ritnova.sjl.min_free_days') }} hari bebas.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Peserta</th>
                            @foreach ($phases as $phase => $p)<th>{{ $phase }} instrumen</th><th class="text-right">{{ $phase }} malam</th><th class="text-right">{{ $phase }} SJL</th>@endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($participants as $u)
                            <tr>
                                <td><a href="{{ route('admin.participants.show', $u) }}" class="font-semibold text-brand hover:underline">{{ $u->username }}</a></td>
                                @foreach ($phases as $phase => $p)
                                    @php $r = $sjl[$u->id][$phase] ?? null; $n = $nights[$phase][$u->id] ?? 0; @endphp
                                    <td>
                                        @if (! $p['instruments'])<span class="muted">·</span>
                                        @elseif ($phaseDone[$u->id][$phase] ?? false)<span class="pill-done">Lengkap</span>
                                        @elseif ($p['state'] === 'open')<span class="pill-warn">Menunggu</span>
                                        @elseif ($p['state'] === 'closed')<span class="pill-alert">Terlewat</span>
                                        @else<span class="muted">—</span>@endif
                                    </td>
                                    <td class="text-right num {{ $p['window'] && $n < $min && $p['state'] !== 'scheduled' ? 'font-semibold text-warn-ink' : '' }}">{{ $p['window'] ? $n : '—' }}</td>
                                    <td class="text-right whitespace-nowrap num">{{ $r ? ($r->sjl_minutes === null ? 'Belum cukup' : intdiv($r->sjl_minutes, 60).'j '.($r->sjl_minutes % 60).'m') : '—' }}</td>
                                @endforeach
                            </tr>
                        @empty
                            <tr><td colspan="10" class="py-12 text-center muted">Belum ada peserta dengan persetujuan berlaku.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="card self-start">
            <h2 class="mb-1 font-bold">Instrumen</h2>
            <p class="mb-3 text-sm muted">Butir dan skoring diisi tim peneliti dari instrumen yang sudah tervalidasi.</p>
            <a href="{{ route('admin.instruments.index') }}" class="btn-secondary btn-sm mb-4 w-full"><x-icon name="pencil" class="h-4 w-4" />Kelola instrumen</a>
            <ul class="space-y-3">
                @forelse ($allInstruments as $i)
                    <li class="rounded-2xl bg-canvas p-3 ring-1 ring-ink/5">
                        <div class="mb-1 flex items-center justify-between gap-2"><span class="font-semibold">{{ $i->code }}</span><span class="pill-muted">versi {{ $i->version }}</span></div>
                        <p class="mb-2 text-sm">{{ $i->name }}</p>
                        <p class="text-xs muted">{{ $i->items_count }} butir, fase {{ implode(', ', $i->phases ?? []) }}</p>
                    </li>
                @empty
                    <li class="text-sm muted">Belum ada instrumen.</li>
                @endforelse
            </ul>
        </section>
    </div>
@endsection
