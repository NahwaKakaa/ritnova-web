@extends('layouts.guest', ['title' => 'Persetujuan'])

@section('content')
    <div class="card">
        @if ($version)
            <p class="mb-1 text-xs text-ink/50">Versi {{ $version->version }} · {{ $version->published_at?->translatedFormat('d F Y') }}</p>
            <h1 class="mb-4 text-xl font-bold">{{ $version->title }}</h1>

            <ul class="mb-4 space-y-1 text-sm">
                @foreach (['Tujuan dan kegiatan penelitian', 'Durasi kegiatan dan kewajiban pengisian', 'Jenis data tidur dan respons instrumen', 'Risiko, manfaat, kerahasiaan, dan hak berhenti'] as $point)
                    <li class="flex gap-2"><span class="text-primary">✓</span>{{ $point }}</li>
                @endforeach
            </ul>

            <div class="mb-4 max-h-72 overflow-y-auto rounded-xl bg-canvas p-4 text-sm whitespace-pre-line">{{ $version->body }}</div>

            @if ($version->document_path)
                <a href="{{ asset('storage/'.$version->document_path) }}" class="mb-4 inline-block text-sm text-primary underline" download>Unduh informasi studi (PDF)</a>
            @endif

            <p class="mb-6 text-xs text-ink/60">Kamu dapat menarik persetujuan kapan saja melalui menu Profil. Pertanyaan tentang penelitian atau etik dapat disampaikan ke tim peneliti.</p>

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
