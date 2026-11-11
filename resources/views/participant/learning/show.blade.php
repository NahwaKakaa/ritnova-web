@extends('layouts.participant', ['title' => $session->title])

@php
    $quizDone = $progress->quiz_completed_at !== null || $session->quizItems->isEmpty();
    $mediaDone = $progress->media_completed_at !== null;
    $actionDone = $progress->action_completed_at !== null;
    $stages = [
        ['01', 'Konsumsi materi', config('ritnova.media_labels')[$session->media_type] ?? $session->media_type, $mediaDone],
        ['02', 'Cek pemahaman', '2–3 butir singkat', $quizDone],
        ['03', 'Aksi/refleksi', '1 target atau catatan', $actionDone],
    ];
@endphp

@section('content')
    <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
        <div>
            <a href="{{ route('learning.index') }}" class="text-sm text-primary">← Daftar sesi</a>
            <p class="mt-2 text-xs font-semibold text-ink/50">SESI {{ str_pad($session->number, 2, '0', STR_PAD_LEFT) }} · MINGGU {{ $session->week }}</p>
            <h1 class="text-2xl font-bold">{{ $session->title }}</h1>
        </div>
        <p class="text-sm text-ink/60">
            Dimulai {{ ($progress->started_at ?? $progress->first_open_at)->format('H.i') }} WITA ·
            Aktif: <span data-active-seconds="{{ $progress->active_seconds }}">{{ gmdate('i\m s\d', $progress->active_seconds) }}</span>
        </p>
    </div>

    @if ($progress->completed_at)
        <div class="mb-4 rounded-xl bg-green-100 px-4 py-3 text-sm text-green-900">Sesi ini sudah selesai pada {{ $progress->completed_at->translatedFormat('d F, H.i') }} WITA. Kamu bisa membukanya kembali kapan saja.</div>
    @endif
    @error('complete') <div class="mb-4 rounded-xl bg-peach px-4 py-3 text-sm">{{ $message }}</div> @enderror

    {{-- Alur 3 tahap --}}
    <ol class="mb-6 grid grid-cols-3 gap-2 text-center text-xs sm:text-sm">
        @foreach ($stages as [$no, $label, $hint, $done])
            <li @class(['rounded-xl p-3', 'bg-primary text-white' => $done, 'bg-white ring-1 ring-black/5' => ! $done])>
                <span class="block font-bold">{{ $done ? '✓' : $no }}</span>
                <span class="block font-semibold">{{ $label }}</span>
                <span @class(['hidden sm:block', 'text-white/80' => $done, 'text-ink/50' => ! $done])>{{ $done ? 'Selesai' : $hint }}</span>
            </li>
        @endforeach
    </ol>

    <div class="space-y-4">
        {{-- 01 Konsumsi materi --}}
        <section class="card">
            <p class="mb-1 text-sm font-semibold text-secondary">01 · Materi</p>
            @if ($session->objective)
                <p class="mb-3 text-sm"><b>Tujuan sesi:</b> {{ $session->objective }}</p>
            @endif

            <div data-session-media
                 data-type="{{ $session->media_type }}"
                 data-src="{{ $session->media_url }}"
                 data-track-url="{{ route('learning.media', $session) }}"
                 data-pct="{{ $progress->media_completion_pct }}"
                 data-min-seconds="{{ config('ritnova.infographic_min_seconds') }}"
                 data-trackable="{{ $decision->canStart() ? '1' : '0' }}">
                @if (! $session->media_url)
                    <div class="flex aspect-video items-center justify-center rounded-xl bg-lavender text-sm text-ink/60">Media belum diunggah oleh tim peneliti.</div>
                @elseif ($session->media_type === 'video')
                    <div class="aspect-video overflow-hidden rounded-xl bg-ink" data-player></div>
                @elseif ($session->media_type === 'podcast')
                    <audio class="w-full" controls preload="metadata" src="{{ $session->media_url }}" data-player></audio>
                @else
                    <img class="w-full rounded-xl" src="{{ $session->media_url }}" alt="{{ $session->media_alt_text ?? $session->title }}" data-player>
                    <div data-infographic-end class="h-1"></div>
                @endif
            </div>

            <div class="mt-3 flex items-center gap-3 text-xs text-ink/60">
                <div class="h-1.5 flex-1 rounded-full bg-lavender"><div class="h-1.5 rounded-full bg-primary transition-all" data-media-bar style="width: {{ round($progress->media_completion_pct * 100) }}%"></div></div>
                <span data-media-label>{{ round($progress->media_completion_pct * 100) }}%</span>
            </div>

            @if ($session->transcript)
                <details class="mt-3 rounded-xl bg-canvas p-3 text-sm">
                    <summary class="cursor-pointer font-medium">{{ $session->media_type === 'video' ? 'Subtitle / transkrip' : 'Transkrip' }}</summary>
                    <div class="mt-2 whitespace-pre-line">{{ $session->transcript }}</div>
                </details>
            @endif

            @if ($session->key_message)
                <div class="mt-3 rounded-xl bg-sky/40 p-3 text-sm"><b>Pesan kunci:</b> {{ $session->key_message }}</div>
            @endif
        </section>

        {{-- 02 Cek pemahaman --}}
        <section class="card">
            <p class="mb-2 text-sm font-semibold text-secondary">02 · Cek pemahaman</p>
            @if ($progress->quiz_completed_at)
                <p class="text-sm">Sudah dijawab ✓</p>
            @elseif ($session->quizItems->isEmpty())
                <p class="text-sm text-ink/60">Butir pertanyaan untuk sesi ini belum ditetapkan peneliti.</p>
            @else
                <form method="POST" action="{{ route('learning.quiz', $session) }}" class="space-y-4">
                    @csrf
                    @foreach ($session->quizItems as $item)
                        <fieldset>
                            <legend class="mb-2 text-sm font-medium">{{ $loop->iteration }}. {{ $item->question }}</legend>
                            @foreach ($item->options as $i => $option)
                                <label class="flex items-center gap-2 py-0.5 text-sm"><input type="radio" name="answers[{{ $item->id }}]" value="{{ $i }}" required> {{ $option }}</label>
                            @endforeach
                        </fieldset>
                    @endforeach
                    <button class="btn-secondary" @disabled(! $decision->canStart())>Kirim jawaban</button>
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
                    <textarea name="action_response" rows="3" class="input" placeholder="Tulis aksi atau catatanmu…" required></textarea>
                    <button class="btn-secondary" @disabled(! $decision->canStart())>Simpan aksi</button>
                </form>
            @endif
        </section>

        @if (! $progress->completed_at)
            <form method="POST" action="{{ route('learning.complete', $session) }}">
                @csrf
                <button class="btn-primary w-full" data-complete-button>Selesaikan sesi</button>
                <p class="mt-2 text-center text-xs text-ink/50">Sesi tercatat selesai bila ketiga tahap sudah dipenuhi.</p>
            </form>
        @endif
    </div>
@endsection
