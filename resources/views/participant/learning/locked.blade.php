@extends('layouts.participant', ['title' => 'Sesi belum tersedia'])

@section('content')
    <div class="mx-auto max-w-xl">
        <a href="{{ route('learning.index') }}" class="btn-ghost btn-sm mb-6 -ml-3.5"><x-icon name="arrow-left" class="h-4 w-4" />Daftar sesi</a>

        <section class="panel-night p-8 text-center sm:p-10">
            <span class="mx-auto mb-5 flex h-14 w-14 items-center justify-center rounded-2xl bg-white/10 text-[#EBE7FF]"><x-icon name="lock" class="h-6 w-6" /></span>
            <p class="mb-1 text-white/60 num">Sesi {{ str_pad($session->number, 2, '0', STR_PAD_LEFT) }} belum tersedia</p>
            <h1 class="mb-6 text-[28px] leading-tight font-bold">{{ $session->title }}</h1>

            @if (! $decision->timeReached)
                <p class="mb-5 text-white/70">Terbuka {{ \Illuminate\Support\Carbon::instance($decision->releaseAt)->translatedFormat('l, d F, H.i') }} WITA</p>
                <div class="mx-auto mb-2 grid max-w-sm grid-cols-3 gap-2" data-countdown-grid="{{ $decision->releaseAt->getTimestamp() }}" aria-live="polite">
                    @foreach (['d' => 'hari', 'h' => 'jam', 'm' => 'menit'] as $key => $unit)
                        <div class="rounded-2xl bg-white/10 py-4"><span class="block text-4xl font-bold num" data-unit="{{ $key }}">00</span><span class="text-xs text-white/55">{{ $unit }}</span></div>
                    @endforeach
                </div>
            @endif
        </section>

        @unless ($decision->prerequisiteMet)
            <div class="mt-4 flex items-start gap-3 rounded-[var(--radius-control)] bg-peach/60 p-4 text-sm">
                <x-icon name="alert" class="mt-0.5 h-4 w-4 text-warn-ink" />
                <span>Sesi {{ $previous ? str_pad($previous['session']->number, 2, '0', STR_PAD_LEFT) : 'sebelumnya' }} perlu diselesaikan dulu sebelum sesi ini bisa dimulai.</span>
            </div>
        @endunless

        @if ($decision->status->value === 'expired')
            <div class="mt-4 rounded-[var(--radius-control)] bg-ink/5 p-4 text-sm">Batas waktu sesi ini sudah lewat sesuai protokol penelitian.</div>
        @endif

        <div class="mt-6 flex flex-col gap-3 sm:flex-row">
            @if ($previous && ($previous['decision']->canStart() || $previous['decision']->status->value === 'completed'))
                <a href="{{ route('learning.show', $previous['session']) }}" class="btn-primary flex-1">Buka Sesi {{ str_pad($previous['session']->number, 2, '0', STR_PAD_LEFT) }}</a>
            @endif
            <a href="{{ route('help') }}" class="btn-secondary flex-1"><x-icon name="help" class="h-4 w-4" />Bantuan</a>
        </div>
        <p class="mt-4 text-center text-sm muted">Kamu akan mendapat notifikasi saat sesi ini dibuka.</p>
    </div>
@endsection
