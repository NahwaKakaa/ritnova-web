@extends('layouts.participant', ['title' => $session->title])

@php
    $quizDone = $progress->quiz_completed_at !== null || $session->quizItems->isEmpty();
    $mediaDone = $progress->media_completed_at !== null;
    $actionDone = $progress->action_completed_at !== null;
    $mediaLabel = config('ritnova.media_labels')[$session->media_type] ?? $session->media_type;
    $stages = [
        ['Materi', $mediaLabel, $mediaDone, '#materi'],
        ['Cek pemahaman', '2–3 pertanyaan singkat', $quizDone, '#cek'],
        ['Aksi', 'Satu target atau catatan', $actionDone, '#aksi'],
    ];
    $allDone = $mediaDone && $quizDone && $actionDone;
@endphp

@section('content')
    <a href="{{ route('learning.index') }}" class="btn-ghost btn-sm mb-4 -ml-3.5"><x-icon name="arrow-left" class="h-4 w-4" />Daftar sesi</a>

    <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="mb-1 font-semibold text-secondary num">Sesi {{ str_pad($session->number, 2, '0', STR_PAD_LEFT) }} · Minggu {{ $session->week }}</p>
            <h1 class="page-title">{{ $session->title }}</h1>
        </div>
        <p class="rounded-full bg-surface px-4 py-2 text-sm ring-1 ring-ink/5 num">
            Mulai {{ ($progress->started_at ?? $progress->first_open_at)->format('H.i') }} WITA ·
            aktif <span data-active-seconds="{{ $progress->active_seconds }}">{{ gmdate('i', $progress->active_seconds) }}m {{ gmdate('s', $progress->active_seconds) }}d</span>
        </p>
    </div>

    @if ($progress->completed_at)
        <div class="mb-6 flex items-center gap-3 rounded-[var(--radius-control)] bg-mint px-4 py-3 text-sm text-mint-deep"><x-icon name="check" class="h-4 w-4" />Selesai pada {{ $progress->completed_at->translatedFormat('d F, H.i') }} WITA. Kamu bisa membukanya lagi kapan saja.</div>
    @endif
    @error('complete') <div class="mb-6 flex items-center gap-3 rounded-[var(--radius-control)] bg-peach/70 px-4 py-3 text-sm"><x-icon name="alert" class="h-4 w-4" />{{ $message }}</div> @enderror

    <div class="grid gap-8 lg:grid-cols-[15rem_1fr]">
        {{-- Alur 3 tahap --}}
        <nav aria-label="Tahap sesi" class="lg:sticky lg:top-24 lg:self-start">
            <ol class="grid grid-cols-3 gap-2 lg:grid-cols-1 lg:gap-0">
                @foreach ($stages as $i => [$label, $hint, $done, $anchor])
                    <li class="relative lg:pb-6 lg:last:pb-0">
                        @if (! $loop->last)<span class="absolute top-10 left-5 hidden h-[calc(100%-2.5rem)] w-0.5 bg-lavender lg:block" aria-hidden="true"></span>@endif
                        <a href="{{ $anchor }}" class="flex flex-col items-center gap-2 rounded-2xl p-2 text-center lg:flex-row lg:items-start lg:text-left">
                            <span @class(['flex h-10 w-10 shrink-0 items-center justify-center rounded-full font-bold num', 'bg-primary text-white' => $done, 'bg-surface text-brand ring-2 ring-lavender-deep' => ! $done])>
                                @if ($done) <x-icon name="check" class="h-4 w-4" /> @else {{ $i + 1 }} @endif
                            </span>
                            <span>
                                <span class="block text-sm font-semibold">{{ $label }}</span>
                                <span class="hidden text-xs muted lg:block">{{ $done ? 'Selesai' : $hint }}</span>
                            </span>
                        </a>
                    </li>
                @endforeach
            </ol>
        </nav>

        <div class="min-w-0 space-y-6">
            {{-- 1. Materi --}}
            <section id="materi" class="card scroll-mt-24 p-0">
                <div class="p-5 pb-4 sm:p-6 sm:pb-4">
                    <h2 class="mb-1 text-lg font-bold">Materi</h2>
                    @if ($session->objective)<p class="muted">{{ $session->objective }}</p>@endif
                </div>

                <div class="px-5 sm:px-6"
                     data-session-media
                     data-type="{{ $session->media_type }}"
                     data-src="{{ $session->media_url }}"
                     data-track-url="{{ route('learning.media', $session) }}"
                     data-pct="{{ $progress->media_completion_pct }}"
                     data-min-seconds="{{ config('ritnova.infographic_min_seconds') }}"
                     data-trackable="{{ $decision->canStart() ? '1' : '0' }}">
                    @if (! $session->media_url)
                        <div class="flex aspect-video flex-col items-center justify-center gap-3 rounded-2xl bg-night text-center text-white/60">
                            <x-icon :name="['video' => 'play', 'infographic' => 'image', 'podcast' => 'headphones'][$session->media_type] ?? 'book'" class="h-10 w-10 text-[#EBE7FF]" />
                            <span class="text-sm">{{ $mediaLabel }} sedang disiapkan tim peneliti</span>
                        </div>
                    @elseif ($session->media_type === 'video')
                        <div class="aspect-video overflow-hidden rounded-2xl bg-night" data-player></div>
                    @elseif ($session->media_type === 'podcast')
                        <div class="rounded-2xl bg-night p-5"><audio class="w-full" controls preload="metadata" src="{{ $session->media_url }}" data-player></audio></div>
                    @else
                        <img class="w-full rounded-2xl" src="{{ $session->media_url }}" alt="{{ $session->media_alt_text ?? $session->title }}" data-player>
                        <div data-infographic-end class="h-1"></div>
                    @endif
                </div>

                <div class="flex items-center gap-3 px-5 pt-4 text-sm sm:px-6">
                    <div class="track flex-1"><span class="transition-all" data-media-bar style="width: {{ round($progress->media_completion_pct * 100) }}%"></span></div>
                    <span class="w-10 text-right font-semibold num" data-media-label>{{ round($progress->media_completion_pct * 100) }}%</span>
                </div>

                <div class="space-y-3 p-5 sm:p-6">
                    @if ($session->key_message)
                        <div class="rounded-2xl bg-sky/40 p-4"><p class="mb-1 text-sm font-semibold">Pesan kunci</p><p>{{ $session->key_message }}</p></div>
                    @endif
                    @if ($session->transcript)
                        <details class="group rounded-2xl bg-canvas p-4 text-sm">
                            <summary class="flex cursor-pointer list-none items-center justify-between font-semibold">{{ $session->media_type === 'video' ? 'Subtitle dan transkrip' : 'Transkrip' }}<x-icon name="plus" class="h-4 w-4 transition group-open:rotate-45" /></summary>
                            <div class="mt-3 leading-relaxed whitespace-pre-line">{{ $session->transcript }}</div>
                        </details>
                    @endif
                </div>
            </section>

            {{-- 2. Cek pemahaman --}}
            <section id="cek" class="card scroll-mt-24 sm:p-6">
                <h2 class="mb-4 text-lg font-bold">Cek pemahaman</h2>
                @if ($progress->quiz_completed_at)
                    <p class="flex items-center gap-2 text-mint-deep"><x-icon name="check" class="h-4 w-4" />Jawabanmu sudah tersimpan.</p>
                @elseif ($session->quizItems->isEmpty())
                    <p class="muted">Pertanyaan untuk sesi ini belum ditetapkan tim peneliti.</p>
                @else
                    <form method="POST" action="{{ route('learning.quiz', $session) }}" class="space-y-6">
                        @csrf
                        @foreach ($session->quizItems as $item)
                            <fieldset>
                                <legend class="mb-3 font-medium">{{ $item->question }}</legend>
                                <div class="grid gap-2">
                                    @foreach ($item->options as $i => $option)
                                        <label class="choice"><input type="radio" name="answers[{{ $item->id }}]" value="{{ $i }}" class="h-4 w-4 accent-primary" required>{{ $option }}</label>
                                    @endforeach
                                </div>
                            </fieldset>
                        @endforeach
                        <button type="submit" class="btn-secondary" @disabled(! $decision->canStart())>Simpan jawaban</button>
                    </form>
                @endif
            </section>

            {{-- 3. Aksi --}}
            <section id="aksi" class="card scroll-mt-24 sm:p-6">
                <h2 class="mb-2 text-lg font-bold">Aksi</h2>
                <p class="mb-4 text-[17px]">{{ $session->action_prompt }}</p>
                @if ($progress->action_completed_at)
                    <blockquote class="rounded-2xl border-l-4 border-primary bg-lavender/60 p-4">{{ $progress->action_response }}</blockquote>
                @else
                    <form method="POST" action="{{ route('learning.action', $session) }}" class="space-y-3">
                        @csrf
                        <label for="action_response" class="sr-only">Aksi atau catatanmu</label>
                        <textarea id="action_response" name="action_response" rows="3" class="input" placeholder="Tulis aksi atau catatanmu" required></textarea>
                        <button type="submit" class="btn-secondary" @disabled(! $decision->canStart())>Simpan aksi</button>
                    </form>
                @endif
            </section>

            @if (! $progress->completed_at)
                <form method="POST" action="{{ route('learning.complete', $session) }}" class="sticky bottom-24 z-10 md:bottom-4">
                    @csrf
                    <div class="flex flex-col items-center gap-3 rounded-[var(--radius-card)] bg-surface/95 p-4 shadow-[var(--shadow-lift)] ring-1 ring-ink/5 backdrop-blur sm:flex-row sm:justify-between">
                        <p class="text-sm muted">{{ $allDone ? 'Semua tahap terpenuhi.' : 'Selesaikan ketiga tahap untuk menutup sesi.' }}</p>
                        <button type="submit" @class(['w-full sm:w-auto', 'btn-primary' => $allDone, 'btn-secondary' => ! $allDone])>Selesaikan sesi</button>
                    </div>
                </form>
            @endif
        </div>
    </div>
@endsection
