@extends('layouts.participant', ['title' => 'Sesi belum tersedia'])

@section('content')
    <div class="card mx-auto max-w-lg text-center">
        <p class="mb-2 text-4xl">◷</p>
        <h1 class="mb-2 text-xl font-bold">Sesi {{ str_pad($session->number, 2, '0', STR_PAD_LEFT) }} — Belum Tersedia</h1>

        @if (! $decision->timeReached)
            <p class="mb-2">Akan terbuka: <b>{{ $decision->releaseAt->format('d/m/Y · H.i') }} WITA</b></p>
            <p class="mb-4 text-sm text-ink/60" data-countdown="{{ $decision->releaseAt->getTimestamp() }}"></p>
        @endif

        @unless ($decision->prerequisiteMet)
            <p class="mb-4 text-sm">Sesi sebelumnya harus selesai sebelum sesi ini dapat dimulai.</p>
        @endunless

        @if ($decision->status->value === 'expired')
            <p class="mb-4 text-sm">Batas waktu sesi ini sudah lewat.</p>
        @endif

        <a href="{{ route('learning.index') }}" class="btn-secondary">Kembali ke daftar sesi</a>
    </div>
@endsection
