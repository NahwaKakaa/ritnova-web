@extends('layouts.guest', ['title' => 'Persetujuan'])

@section('content')
    @if ($version)
        <p class="mb-2 text-sm muted num">Versi {{ $version->version }} · {{ $version->published_at?->translatedFormat('d F Y') }}</p>
        <h1 class="mb-6 text-[26px] leading-tight font-bold">{{ $version->title }}</h1>

        @if ($previous)
            <div class="mb-5 flex items-start gap-3 rounded-[var(--radius-control)] bg-lavender px-4 py-3 text-sm" role="note">
                <x-icon name="shield" class="mt-0.5 h-4 w-4 shrink-0 text-brand" />
                <p>Dokumen persetujuan diperbarui dari versi {{ $previous->version }} ke versi {{ $version->version }}. Baca ulang lalu setujui untuk melanjutkan. Data yang sudah kamu isi tetap tersimpan.</p>
            </div>
        @endif

        <ul class="mb-5 grid gap-2.5 text-[15px]">
            @foreach (['Tujuan dan kegiatan penelitian', 'Durasi kegiatan dan kewajiban pengisian', 'Jenis data tidur dan respons instrumen', 'Risiko, manfaat, kerahasiaan, dan hak berhenti'] as $point)
                <li class="flex items-center gap-3"><span class="flex h-6 w-6 items-center justify-center rounded-full bg-lavender text-brand"><x-icon name="check" class="h-3.5 w-3.5" /></span>{{ $point }}</li>
            @endforeach
        </ul>

        <div class="mb-4 max-h-64 overflow-y-auto rounded-[var(--radius-control)] bg-surface p-4 text-sm leading-relaxed whitespace-pre-line ring-1 ring-ink/10" tabindex="0" aria-label="Isi dokumen persetujuan">{{ $version->body }}</div>

        @if ($version->document_path)
            <a href="{{ route('consent.document', $version) }}" class="btn-ghost btn-sm mb-4 -ml-3"><x-icon name="clipboard" class="h-4 w-4" />Unduh informasi studi (PDF)</a>
        @endif

        <form method="POST" action="{{ route('consent.store') }}" class="space-y-4">
            @csrf
            <label class="choice items-start">
                <input type="checkbox" name="agree" value="1" class="mt-0.5 h-4 w-4 accent-primary">
                Saya memahami penjelasan dan bersedia berpartisipasi.
            </label>
            @error('agree') <p class="text-sm text-rose-deep">{{ $message }}</p> @enderror
            <button type="submit" class="btn-primary w-full py-3.5 text-[15px]">Setuju dan lanjutkan</button>
        </form>
        <p class="mt-5 text-xs muted">Kamu dapat menarik persetujuan kapan saja melalui halaman Profil. Pertanyaan tentang penelitian atau etik dapat disampaikan ke tim peneliti.</p>
    @else
        <h1 class="mb-2 text-2xl font-bold">Dokumen belum tersedia</h1>
        <p class="muted">Dokumen persetujuan belum diterbitkan. Hubungi tim peneliti.</p>
    @endif
@endsection
