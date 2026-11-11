@extends('layouts.participant', ['title' => 'Sesi belum tersedia'])

@section('content')
    <div class="card mx-auto max-w-lg text-center">
        <p class="mb-2 text-4xl text-primary">◷</p>
        <h1 class="mb-4 text-xl font-bold">Sesi {{ str_pad($session->number, 2, '0', STR_PAD_LEFT) }} — Belum Tersedia</h1>

        @if (! $decision->timeReached)
            <p class="mb-1">Akan terbuka: <b>{{ \Illuminate\Support\Carbon::instance($decision->releaseAt)->translatedFormat('l, d F · H.i') }} WITA</b></p>
            <p class="mb-4 font-mono text-lg text-primary" data-countdown="{{ $decision->releaseAt->getTimestamp() }}" aria-live="polite"></p>
        @endif

        @unless ($decision->prerequisiteMet)
            <p class="mb-4 rounded-xl bg-peach/60 p-3 text-sm">Sesi {{ $previous ? str_pad($previous['session']->number, 2, '0', STR_PAD_LEFT) : 'sebelumnya' }} harus selesai sebelum Sesi {{ str_pad($session->number, 2, '0', STR_PAD_LEFT) }} dapat dimulai.</p>
        @endunless

        @if ($decision->status->value === 'expired')
            <p class="mb-4 rounded-xl bg-peach/60 p-3 text-sm">Batas waktu sesi ini sudah lewat sesuai protokol penelitian.</p>
        @endif

        <p class="mb-6 text-xs text-ink/50">Kamu akan mendapat notifikasi ketika sesi ini dibuka.</p>

        <div class="flex flex-col gap-2 sm:flex-row sm:justify-center">
            @if ($previous && ($previous['decision']->canStart() || $previous['decision']->status->value === 'completed'))
                <a href="{{ route('learning.show', $previous['session']) }}" class="btn-primary">Ke Sesi {{ str_pad($previous['session']->number, 2, '0', STR_PAD_LEFT) }}</a>
            @endif
            <a href="{{ route('learning.index') }}" class="btn-secondary">Daftar sesi</a>
            <a href="{{ route('help') }}" class="btn-secondary">Bantuan</a>
        </div>
    </div>
@endsection
