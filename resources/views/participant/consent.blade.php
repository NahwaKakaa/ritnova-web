@extends('layouts.guest', ['title' => 'Persetujuan'])

@section('content')
    <div class="card">
        @if ($version)
            <p class="mb-1 text-xs text-ink/50">Versi {{ $version->version }} · {{ $version->published_at?->translatedFormat('d F Y') }}</p>
            <h1 class="mb-4 text-xl font-bold">{{ $version->title }}</h1>
            <div class="mb-6 max-h-80 overflow-y-auto whitespace-pre-line rounded-xl bg-canvas p-4 text-sm">{{ $version->body }}</div>

            <form method="POST" action="{{ route('consent.store') }}" class="space-y-4">
                @csrf
                <label class="flex items-start gap-3 text-sm">
                    <input type="checkbox" name="agree" value="1" class="mt-1">
                    Saya memahami penjelasan dan bersedia berpartisipasi.
                </label>
                @error('agree') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                <button class="btn-primary w-full">Setuju dan lanjutkan</button>
            </form>
        @else
            <p>Dokumen persetujuan belum diterbitkan. Hubungi tim peneliti.</p>
        @endif
    </div>
@endsection
