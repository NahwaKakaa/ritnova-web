@extends('layouts.participant', ['title' => 'Beranda'])

@php
    $main = $tasks->first();
    $others = $tasks->slice(1);
    $sjl = $rhythm['result'] ?? null;
@endphp

@section('content')
    {{-- Panel malam: satu aksi utama + ritme --}}
    <section class="panel-night mb-6 grid gap-8 p-6 sm:p-8 md:grid-cols-[1.25fr_0.75fr] md:items-center md:p-10">
        <div class="relative z-10">
            <p class="mb-3 text-white/60">Halo, {{ $user->username }}.{{ $week ? ' Minggu ke-'.$week.' dari 4.' : '' }}</p>
            @if ($main)
                <h1 class="mb-3 text-[30px] leading-tight font-bold sm:text-4xl">{{ $main['label'] }}</h1>
                <p class="mb-7 max-w-md text-white/65">{{ $main['hint'] ?? '' }}</p>
                <a href="{{ $main['route'] }}" class="btn-on-night px-6 py-3.5 text-[15px]">{{ $main['cta'] }}</a>
            @else
                <h1 class="mb-3 text-[30px] leading-tight font-bold sm:text-4xl">Semua beres untuk hari ini</h1>
                <p class="max-w-md text-white/65">Catatan dan sesi hari ini sudah lengkap. Sampai jumpa besok malam.</p>
            @endif

            @if ($others->isNotEmpty())
                <div class="mt-8 border-t border-white/10 pt-5">
                    <p class="mb-3 text-sm text-white/50">Berikutnya</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($others as $task)
                            <a href="{{ $task['route'] }}" class="inline-flex items-center gap-2 rounded-full bg-white/10 px-4 py-2 text-sm hover:bg-white/20">{{ $task['label'] }}</a>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <div class="relative z-10 mx-auto w-full max-w-[17rem]">
            @if ($rhythm && ($rhythm['work'] || $rhythm['free']))
                <x-rhythm-dial theme="night" :work="$rhythm['work']" :free="$rhythm['free']" :value="$sjl?->sjlLabel()" :caption="$sjl?->isSufficient() ? 'SJL 7 hari terakhir' : 'data belum cukup'" />
            @elseif ($rhythm)
                <x-rhythm-dial theme="night" value="–" caption="isi diary untuk melihat ritmemu" />
            @else
                {{-- Kelompok pembanding: kelengkapan diary tanpa umpan balik SJL --}}
                <div class="rounded-[var(--radius-card)] bg-white/5 p-5">
                    <p class="mb-4 text-sm text-white/60">Catatan 7 malam terakhir</p>
                    <div class="grid grid-cols-7 gap-1.5 text-center text-[11px]">
                        @foreach (range(7, 1) as $ago)
                            @php($d = now()->subDays($ago))
                            @php($entry = $weekDiaries->get($d->toDateString()))
                            <div>
                                <span @class(['mb-1.5 flex h-9 items-center justify-center rounded-xl', 'bg-lavender text-primary' => $entry?->status === 'complete', 'bg-peach-deep/40' => $entry && $entry->status !== 'complete', 'bg-white/10' => ! $entry])>
                                    @if ($entry?->status === 'complete') <x-icon name="check" class="h-4 w-4" /> @endif
                                </span>
                                <span class="text-white/50">{{ $d->translatedFormat('D') }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </section>

    @foreach ($notifications as $n)
        <a href="{{ $n->data['url'] ?? route('notifications.index') }}" class="mb-3 flex items-center gap-3 rounded-[var(--radius-control)] bg-sky/45 px-4 py-3 text-sm hover:bg-sky/70">
            <x-icon name="bell" class="h-4 w-4 text-primary" />{{ $n->data['message'] }}
        </a>
    @endforeach

    {{-- Ringkasan --}}
    <dl class="mb-8 grid grid-cols-2 gap-px overflow-hidden rounded-[var(--radius-card)] bg-night/5 ring-1 ring-night/5 md:grid-cols-4">
        @if ($user->isIntervention())
            <div class="bg-white p-5"><dt class="mb-1 text-sm muted">Sesi selesai</dt><dd class="text-[28px] font-bold num">{{ $completedCount }}<span class="text-base font-medium text-night/40">/12</span></dd></div>
        @endif
        <div class="bg-white p-5"><dt class="mb-1 text-sm muted">Diary lengkap</dt><dd class="text-[28px] font-bold num">{{ $validNights }}<span class="text-base font-medium text-night/40"> malam</span></dd></div>
        @if ($user->isIntervention())
            <div class="bg-white p-5"><dt class="mb-1 text-sm muted">Target aktif</dt><dd class="text-[28px] font-bold num">{{ $activeGoal ? 1 : 0 }}</dd></div>
        @endif
        <div class="bg-white p-5">
            <dt class="mb-2 text-sm muted">Pengukuran</dt>
            <dd class="flex gap-1.5">
                @foreach ($phases as $phase => $state)
                    <span @class(['pill', 'bg-mint text-mint-deep' => $state === 'done', 'bg-lavender text-primary' => in_array($state, ['open', 'no_instrument']), 'bg-night/5 text-night/45' => in_array($state, ['scheduled', 'closed'])])>{{ $phase }}@if ($state === 'done')<x-icon name="check" class="h-3 w-3" />@endif</span>
                @endforeach
            </dd>
        </div>
    </dl>

    <div class="grid gap-6 lg:grid-cols-[1.3fr_1fr] [&>*]:min-w-0">
        @if ($user->isIntervention())
            <section class="card p-0">
                <div class="flex items-center justify-between gap-3 px-5 pt-5 pb-3">
                    <h2 class="text-lg font-bold">{{ $week ? 'Minggu '.$week.': '.config('ritnova.weeks')[$week] : 'Sesi belajar' }}</h2>
                    <a href="{{ route('learning.index') }}" class="btn-ghost btn-sm">Semua sesi</a>
                </div>
                @forelse ($weekSessions as $row)
                    @php($d = $row['decision'])
                    <a href="{{ $d->canStart() || $d->status->value === 'completed' ? route('learning.show', $row['session']) : route('learning.index') }}" class="flex items-center gap-4 border-t border-night/5 px-5 py-4 hover:bg-canvas">
                        <span @class(['flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-sm font-bold num', 'bg-primary text-white' => $d->status->value === 'completed', 'bg-lavender text-primary' => $d->status->value !== 'completed'])>
                            @if ($d->status->value === 'completed') <x-icon name="check" class="h-4 w-4" /> @else {{ str_pad($row['session']->number, 2, '0', STR_PAD_LEFT) }} @endif
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate font-semibold">{{ $row['session']->title }}</span>
                            <span class="text-sm muted">{{ config('ritnova.media_labels')[$row['session']->media_type] ?? '' }}</span>
                            <span class="mt-1.5 block sm:hidden">@include('participant.learning.partials.status-badge', ['row' => $row])</span>
                        </span>
                        <span class="hidden sm:block">@include('participant.learning.partials.status-badge', ['row' => $row])</span>
                    </a>
                @empty
                    <p class="border-t border-night/5 px-5 py-6 text-sm muted">
                        @if ($upcoming)
                            Sesi pertama dibuka {{ $upcoming['schedule']->release_at->translatedFormat('l, d F, H.i') }} WITA.
                        @else
                            Jadwal sesi belum tersedia.
                        @endif
                    </p>
                @endforelse
            </section>
        @endif

        <div class="space-y-6">
            @if ($user->isIntervention())
                <section class="panel-lavender">
                    <div class="mb-3 flex items-center gap-2 text-primary"><x-icon name="target" /><h2 class="font-bold">Target minggu ini</h2></div>
                    @if ($activeGoal)
                        <p class="mb-2 text-lg leading-snug font-semibold">{{ $activeGoal->target }}</p>
                        @if ($activeGoal->if_then_plan)<p class="text-sm text-night/65">{{ $activeGoal->if_then_plan }}</p>@endif
                        <a href="{{ route('goals.index') }}" class="btn-ghost btn-sm mt-4 -ml-3.5">Kelola target</a>
                    @else
                        <p class="mb-4 text-night/70">Ubah yang kamu pelajari menjadi satu kebiasaan kecil.</p>
                        <a href="{{ route('goals.index') }}" class="btn-primary btn-sm">Buat target</a>
                    @endif
                </section>
            @endif

            <section class="card">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="font-bold">Jadwal pengukuran</h2>
                    <a href="{{ route('assessments.index') }}" class="btn-ghost btn-sm -mr-3">Buka</a>
                </div>
                <ol class="relative space-y-4 border-l-2 border-lavender pl-5">
                    @foreach (config('ritnova.phase_labels') as $phase => $label)
                        @php($w = $windows->get($phase))
                        @php($state = $phases[$phase])
                        <li class="relative">
                            <span @class(['absolute top-1 -left-[27px] h-3 w-3 rounded-full ring-4 ring-white', 'bg-mint-deep' => $state === 'done', 'bg-primary' => in_array($state, ['open', 'no_instrument']), 'bg-lavender-deep' => in_array($state, ['scheduled', 'closed'])])></span>
                            <p class="font-semibold">{{ $phase }} <span class="font-normal muted">{{ $label }}</span></p>
                            <p class="text-sm muted num">{{ $w ? $w->opens_at->translatedFormat('d M').' – '.$w->closes_at?->translatedFormat('d M') : 'Jadwal ditetapkan peneliti' }}</p>
                        </li>
                    @endforeach
                </ol>
            </section>
        </div>
    </div>
@endsection
