@extends('layouts.admin', ['title' => 'Konten sesi'])

@php $media = config('ritnova.media_labels'); $icons = ['video' => 'play', 'infographic' => 'image', 'podcast' => 'headphones']; @endphp

@section('content')
    <div class="mb-7">
        @include('admin.partials.page-head', ['heading' => 'Konten 12 sesi', 'sub' => 'Judul, media, transkrip, cek pemahaman, dan instruksi praktik. Setiap simpan perubahan menaikkan versi konten untuk audit.'])
    </div>

    @foreach ($sessions->groupBy('week') as $week => $rows)
        <section class="mb-6">
            <h2 class="mb-3 flex items-baseline gap-2 font-bold">Minggu {{ $week }} <span class="text-sm font-normal muted">{{ config('ritnova.weeks')[$week] ?? '' }}</span></h2>
            <div class="card divide-y divide-ink/5 p-0">
                @foreach ($rows as $s)
                    @php
                        $sch = $schedules->get($s->id);
                        [$label, $class] = match (true) {
                            ! $s->media_url => ['Draf', 'pill-warn'],
                            $sch && $sch->release_at->isPast() => ['Terbit', 'pill-done'],
                            default => ['Terjadwal', 'pill-wait'],
                        };
                        $missing = collect(['media' => $s->media_url, 'transkrip' => $s->media_type === 'infographic' ? $s->media_alt_text : $s->transcript, 'cek pemahaman' => $s->quiz_items_count, 'aksi' => $s->action_prompt])->filter(fn ($v) => ! $v)->keys();
                    @endphp
                    <a href="{{ route('admin.content.edit', $s) }}" class="flex flex-col gap-3 px-5 py-4 transition hover:bg-lavender/40 sm:flex-row sm:items-center">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-lavender font-bold text-brand num">{{ str_pad($s->number, 2, '0', STR_PAD_LEFT) }}</span>
                        <div class="min-w-0 flex-1">
                            <p class="font-semibold">{{ $s->title }}</p>
                            <p class="flex flex-wrap items-center gap-x-3 text-sm muted">
                                <span class="inline-flex items-center gap-1"><x-icon :name="$icons[$s->media_type] ?? 'book'" class="h-3.5 w-3.5" />{{ $media[$s->media_type] ?? $s->media_type }}</span>
                                <span>versi {{ $s->content_version }}</span>
                                @if ($sch)<span class="num">rilis {{ $sch->release_at->translatedFormat('D j M, H.i') }}</span>@endif
                            </p>
                            @if ($missing->isNotEmpty())<p class="mt-1 text-xs text-warn-ink">Belum ada: {{ $missing->implode(', ') }}</p>@endif
                        </div>
                        <span class="{{ $class }} self-start sm:self-center">{{ $label }}</span>
                    </a>
                @endforeach
            </div>
        </section>
    @endforeach
@endsection
