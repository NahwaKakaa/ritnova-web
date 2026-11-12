@extends('layouts.admin', ['title' => 'Sesi '.str_pad($s->number, 2, '0', STR_PAD_LEFT)])

@php
    $quiz = old('quiz', $s->quizItems->map(fn ($q) => [
        'question' => $q->question,
        'options' => implode("\n", $q->options ?? []),
        'correct' => $q->correct_index === null ? null : $q->correct_index + 1,
    ])->all());
@endphp

@section('content')
    <a href="{{ route('admin.content.index') }}" class="mb-5 inline-flex items-center gap-2 text-sm font-medium text-brand hover:underline"><x-icon name="arrow-left" class="h-4 w-4" />Semua sesi</a>

    <div class="mb-7 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        @include('admin.partials.page-head', ['heading' => 'Sesi '.str_pad($s->number, 2, '0', STR_PAD_LEFT).': '.$s->title, 'sub' => 'Minggu '.$s->week.', '.(config('ritnova.weeks')[$s->week] ?? '').'. Versi konten saat ini: '.$s->content_version.'.'])
    </div>

    <form method="POST" action="{{ route('admin.content.update', $s) }}" class="grid gap-6 xl:grid-cols-[1fr_20rem]">
        @csrf @method('PUT')
        <div class="space-y-6">
            <section class="card space-y-4">
                <h2 class="font-bold">Judul & tujuan</h2>
                <div>
                    <label class="label" for="title">Judul sesi</label>
                    <input id="title" name="title" value="{{ old('title', $s->title) }}" class="input" required maxlength="160">
                </div>
                <div>
                    <label class="label" for="objective">Tujuan materi</label>
                    <textarea id="objective" name="objective" rows="2" class="input">{{ old('objective', $s->objective) }}</textarea>
                </div>
                <div class="sm:w-48">
                    <label class="label" for="estimated_minutes">Perkiraan durasi (menit)</label>
                    <input id="estimated_minutes" name="estimated_minutes" type="number" min="1" max="90" value="{{ old('estimated_minutes', $s->estimated_minutes) }}" class="input">
                </div>
            </section>

            <section class="card space-y-4">
                <h2 class="font-bold">Media</h2>
                <fieldset>
                    <legend class="label">Jenis media</legend>
                    <div class="grid gap-2 sm:grid-cols-3">
                        @foreach (config('ritnova.media_labels') as $k => $label)
                            <label class="choice"><input type="radio" name="media_type" value="{{ $k }}" class="accent-primary" @checked(old('media_type', $s->media_type) === $k)>{{ $label }}</label>
                        @endforeach
                    </div>
                </fieldset>
                <div>
                    <label class="label" for="media_url">Tautan media</label>
                    <input id="media_url" name="media_url" type="url" value="{{ old('media_url', $s->media_url) }}" class="input" placeholder="https://youtu.be/… atau tautan file audio/gambar">
                    <p class="mt-1.5 text-xs muted">Video: YouTube unlisted atau file video. Podcast: file audio. Infografis: file gambar.</p>
                </div>
                <div>
                    <label class="label" for="media_alt_text">Teks alternatif infografis</label>
                    <textarea id="media_alt_text" name="media_alt_text" rows="2" class="input">{{ old('media_alt_text', $s->media_alt_text) }}</textarea>
                </div>
                <div>
                    <label class="label" for="transcript">Transkrip / subtitle</label>
                    <textarea id="transcript" name="transcript" rows="6" class="input">{{ old('transcript', $s->transcript) }}</textarea>
                </div>
            </section>

            <section class="card space-y-4">
                <h2 class="font-bold">Pesan kunci & aksi</h2>
                <div>
                    <label class="label" for="key_message">Pesan kunci</label>
                    <textarea id="key_message" name="key_message" rows="2" class="input">{{ old('key_message', $s->key_message) }}</textarea>
                </div>
                <div>
                    <label class="label" for="action_prompt">Instruksi praktik</label>
                    <textarea id="action_prompt" name="action_prompt" rows="2" class="input">{{ old('action_prompt', $s->action_prompt) }}</textarea>
                </div>
            </section>

            <section class="card" data-repeater>
                <div class="mb-4 flex items-start justify-between gap-4">
                    <div>
                        <h2 class="font-bold">Cek pemahaman</h2>
                        <p class="text-sm muted">Kosongkan bila sesi ini tanpa kuis; tahap 02 akan dianggap terpenuhi.</p>
                    </div>
                    <button type="button" class="btn-secondary btn-sm shrink-0" data-repeater-add><x-icon name="plus" class="h-4 w-4" />Tambah butir</button>
                </div>
                <div class="space-y-4" data-repeater-list>
                    @foreach ($quiz as $i => $q)
                        @include('admin.content.partials.quiz-item', ['i' => $i, 'q' => $q])
                    @endforeach
                </div>
                <template>@include('admin.content.partials.quiz-item', ['i' => '__i__', 'q' => ['question' => '', 'options' => '', 'correct' => null]])</template>
            </section>
        </div>

        <aside class="space-y-4 xl:sticky xl:top-24 xl:self-start">
            <div class="card">
                <h2 class="mb-3 font-bold">Simpan</h2>
                <p class="mb-4 text-sm muted">Bila ada yang berubah, versi naik menjadi {{ $s->content_version + 1 }}. Progres peserta mencatat versi materi yang mereka terima.</p>
                @if ($startedCount)
                    <p class="mb-4 rounded-xl bg-peach/50 px-3 py-2 text-sm text-warn-ink">{{ $startedCount }} peserta sudah memulai sesi ini. Ubah hanya bila disepakati tim peneliti.</p>
                @endif
                <button class="btn-primary w-full">Simpan versi baru</button>
            </div>
            <div class="card text-sm">
                <h2 class="mb-3 font-bold">Jadwal kohort ini</h2>
                @if ($schedule)
                    <p class="mb-1"><span class="muted">Rilis</span> <span class="font-semibold num">{{ $schedule->release_at->translatedFormat('l j M, H.i') }}</span></p>
                    <p class="mb-3"><span class="muted">Batas</span> <span class="font-semibold num">{{ $schedule->close_at?->translatedFormat('l j M, H.i') ?? 'tanpa batas' }}</span></p>
                    <a href="{{ route('admin.schedule.index') }}" class="font-medium text-brand hover:underline">Atur jadwal rilis</a>
                @else
                    <p class="muted">Belum dijadwalkan.</p>
                @endif
            </div>
        </aside>
    </form>
@endsection
