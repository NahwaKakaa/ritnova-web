@extends('layouts.participant', ['title' => 'Pengukuran'])

@section('content')
    <h1 class="mb-6 text-2xl font-bold">Pengukuran Penelitian</h1>
    <div class="space-y-3">
        @forelse ($windows as $w)
            <div class="card flex items-center justify-between">
                <div>
                    <p class="font-semibold">{{ $w->phase }}</p>
                    <p class="text-sm text-ink/60">{{ $w->opens_at->format('d/m/Y H.i') }} – {{ $w->closes_at?->format('d/m/Y H.i') ?? 'selesai' }} WITA</p>
                </div>
                <span @class(['badge', 'bg-lavender text-primary' => $w->isOpen(), 'bg-canvas text-ink/60' => ! $w->isOpen()])>{{ $w->isOpen() ? 'Terbuka' : ($w->opens_at->isFuture() ? 'Terjadwal' : 'Ditutup') }}</span>
            </div>
        @empty
            <p class="text-ink/60">Jadwal pengukuran belum ditetapkan.</p>
        @endforelse
    </div>
    <p class="mt-4 text-xs text-ink/50">Form instrumen akan tersedia setelah instrumen final ditetapkan peneliti.</p>
@endsection
