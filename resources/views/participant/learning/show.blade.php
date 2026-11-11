@extends('layouts.participant', ['title' => $session->title])

@section('content')
    <p class="text-xs font-semibold text-ink/50">SESI {{ str_pad($session->number, 2, '0', STR_PAD_LEFT) }}</p>
    <h1 class="mb-6 text-2xl font-bold">{{ $session->title }}</h1>

    @error('complete') <div class="mb-4 rounded-xl bg-peach px-4 py-3 text-sm">{{ $message }}</div> @enderror

    <div class="space-y-4">
        {{-- 01 Konsumsi materi --}}
        <section class="card">
            <p class="mb-2 text-sm font-semibold text-secondary">01 · Materi ({{ $session->media_type }})</p>
            @if ($session->objective)<p class="mb-3 text-sm">{{ $session->objective }}</p>@endif
            <div class="flex aspect-video items-center justify-center rounded-xl bg-lavender text-sm text-ink/60" data-media
                 data-track-url="{{ route('learning.media', $session) }}">
                {{-- TODO: pemutar media (YouTube IFrame API / audio) yang melaporkan persentase ke data-track-url --}}
                Media belum diunggah
            </div>
            <p class="mt-2 text-xs text-ink/50">Progres media: {{ round($progress->media_completion_pct * 100) }}%</p>
        </section>

        {{-- 02 Cek pemahaman --}}
        <section class="card">
            <p class="mb-2 text-sm font-semibold text-secondary">02 · Cek pemahaman</p>
            @if ($progress->quiz_completed_at)
                <p class="text-sm">Sudah dijawab ✓</p>
            @elseif ($session->quizItems->isEmpty())
                <p class="text-sm text-ink/60">Butir pertanyaan belum diisi oleh peneliti.</p>
            @else
                <form method="POST" action="{{ route('learning.quiz', $session) }}" class="space-y-4">
                    @csrf
                    @foreach ($session->quizItems as $item)
                        <fieldset>
                            <legend class="mb-2 text-sm font-medium">{{ $item->question }}</legend>
                            @foreach ($item->options as $i => $option)
                                <label class="flex items-center gap-2 text-sm"><input type="radio" name="answers[{{ $item->id }}]" value="{{ $i }}" required> {{ $option }}</label>
                            @endforeach
                        </fieldset>
                    @endforeach
                    <button class="btn-secondary">Kirim jawaban</button>
                </form>
            @endif
        </section>

        {{-- 03 Aksi / refleksi --}}
        <section class="card">
            <p class="mb-2 text-sm font-semibold text-secondary">03 · Aksi</p>
            <p class="mb-3 text-sm">{{ $session->action_prompt }}</p>
            @if ($progress->action_completed_at)
                <p class="rounded-xl bg-canvas p-3 text-sm">{{ $progress->action_response }}</p>
            @else
                <form method="POST" action="{{ route('learning.action', $session) }}" class="space-y-3">
                    @csrf
                    <textarea name="action_response" rows="3" class="input" required></textarea>
                    <button class="btn-secondary">Simpan aksi</button>
                </form>
            @endif
        </section>

        @unless ($progress->completed_at)
            <form method="POST" action="{{ route('learning.complete', $session) }}">
                @csrf
                <button class="btn-primary w-full">Selesaikan sesi</button>
            </form>
        @endunless
    </div>
@endsection
