@extends('layouts.participant', ['title' => 'SJL Check'])

@php
    $clock = fn ($h) => $h === null ? '–' : str_replace(':', '.', \App\Domain\Sleep\SjlResultData::clockLabel($h));
    $human = fn ($m) => intdiv($m, 60).' jam '.($m % 60).' menit';
    $minWork = config('ritnova.sjl.min_workdays');
    $minFree = config('ritnova.sjl.min_free_days');
@endphp

@section('content')
    <div class="mb-8">
        <h1 class="page-title">SJL Check</h1>
        <p class="mt-2 muted">Seberapa jauh jam tidurmu bergeser antara hari kuliah dan hari bebas, {{ $from->translatedFormat('d M') }}–{{ $to->translatedFormat('d M') }}.</p>
    </div>

    {{-- Hasil dari diary --}}
    <section class="card mb-6 grid gap-8 p-6 md:grid-cols-[minmax(0,20rem)_1fr] md:items-center md:p-10">
        <div class="mx-auto w-full max-w-[20rem]">
            @if ($result->isSufficient())
                <x-rhythm-dial :work="$rhythm['work']" :free="$rhythm['free']" :value="$result->sjlLabel()" now />
            @else
                <x-rhythm-dial :work="$rhythm['work']" :free="$rhythm['free']" value="–" caption="data belum cukup" />
            @endif
        </div>

        <div>
            @if ($result->isSufficient())
                <p class="mb-1 muted">Perbedaan midpoint tidurmu</p>
                <p class="mb-5 text-4xl font-bold text-brand num sm:text-5xl">{{ $human($result->sjlMinutes) }}</p>
                <div class="mb-6 grid grid-cols-2 gap-3">
                    <div class="rounded-2xl bg-sky/45 p-4">
                        <p class="text-sm text-ink/65">Midpoint hari kuliah</p>
                        <p class="text-2xl font-bold num">{{ $clock($result->mswHours) }}</p>
                        <p class="text-xs text-ink/55 num">dari {{ $result->validWorkdays }} malam</p>
                    </div>
                    <div class="rounded-2xl bg-peach/60 p-4">
                        <p class="text-sm text-ink/65">Midpoint hari bebas</p>
                        <p class="text-2xl font-bold num">{{ $clock($result->msfHours) }}</p>
                        <p class="text-xs text-ink/55 num">dari {{ $result->validFreeDays }} malam</p>
                    </div>
                </div>
                <h2 class="mb-1 font-bold">Apa artinya?</h2>
                <p class="text-ink/75">Titik tengah tidurmu berbeda {{ number_format($result->sjlMinutes / 60, 1, ',', '') }} jam antara hari kuliah dan hari bebas, seperti berpindah zona waktu setiap akhir pekan. Angka ini bukan diagnosis penyakit.</p>
            @else
                <h2 class="mb-2 text-2xl font-bold">Butuh beberapa catatan lagi</h2>
                <p class="mb-5 text-ink/70">SJL dihitung dari malam yang lengkap. Tidak ada data tidak sama dengan SJL nol.</p>
                <ul class="mb-6 space-y-3">
                    @foreach ([['Hari kuliah', $result->validWorkdays, $minWork, 'bg-sky-deep'], ['Hari bebas', $result->validFreeDays, $minFree, 'bg-peach-deep']] as [$label, $have, $need, $dot])
                        <li class="flex items-center gap-3">
                            <span class="h-2.5 w-2.5 rounded-full {{ $dot }}"></span>
                            <span class="flex-1">{{ $label }}</span>
                            <span class="font-semibold num">{{ min($have, $need) }}/{{ $need }} malam</span>
                            @if ($have >= $need)<x-icon name="check" class="h-4 w-4 text-mint-deep" />@endif
                        </li>
                    @endforeach
                </ul>
                <a href="{{ route('diary.create') }}" class="btn-primary">Catat tidur</a>
            @endif
        </div>
    </section>

    <div class="grid gap-6 lg:grid-cols-[0.8fr_1.2fr]">
        {{-- Cara hitung --}}
        <section class="card self-start sm:p-6">
            <h2 class="mb-4 text-lg font-bold">Cara hitung</h2>
            <ol class="space-y-4">
                <li class="flex gap-4"><span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-lavender text-sm font-bold text-brand">1</span><p><b>Midpoint</b> setiap malam = jam mulai tidur + setengah durasi tidur. Tidur lewat tengah malam dihitung lintas tanggal.</p></li>
                <li class="flex gap-4"><span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-lavender text-sm font-bold text-brand">2</span><p>Rata-ratakan midpoint <b>hari kuliah</b> (MSW = {{ $clock($result->mswHours) }}) dan <b>hari bebas</b> (MSF = {{ $clock($result->msfHours) }}).</p></li>
                <li class="flex gap-4"><span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-lavender text-sm font-bold text-brand">3</span><p><b>SJL = |MSF − MSW|</b>, dilaporkan dalam jam dan menit. Hanya malam yang lengkap dan sudah diberi jenis hari yang dipakai.</p></li>
            </ol>
            <p class="mt-5 text-xs muted">Data mentah tetap disimpan. Versi algoritma {{ $result->algorithmVersion }}.</p>
        </section>

        {{-- Simulasi interaktif --}}
        <section class="card sm:p-6">
            <div class="mb-5 flex items-center justify-between gap-3">
                <div>
                    <h2 class="text-lg font-bold">Coba geser jam tidurmu</h2>
                    <p class="text-sm muted">Lihat bagaimana selisih midpoint berubah.</p>
                </div>
                <span class="pill-warn">Simulasi</span>
            </div>
            @include('participant.partials.sjl-simulator', ['theme' => 'light'])

            <noscript>
                <form method="POST" action="{{ route('sjl.calculate') }}" class="mt-6 grid grid-cols-2 gap-3 border-t border-ink/5 pt-5">
                    @csrf
                    @foreach (['work_onset' => '23:30', 'work_offset' => '06:30', 'free_onset' => '01:30', 'free_offset' => '09:30'] as $name => $default)
                        <input class="input" type="time" name="{{ $name }}" value="{{ $manualInput[$name] ?? $default }}" required aria-label="{{ $name }}">
                    @endforeach
                    <button type="submit" class="btn-secondary col-span-2">Hitung</button>
                </form>
            </noscript>

            @if ($manual)
                <div class="mt-5 rounded-2xl bg-canvas p-4 text-sm">
                    @if ($manual->isSufficient())
                        <p class="muted num">Midpoint {{ $clock($manual->mswHours) }} dan {{ $clock($manual->msfHours) }}</p>
                        <p class="text-xl font-bold text-brand num">{{ $human($manual->sjlMinutes) }}</p>
                    @else
                        <p>Periksa kembali jam tidur dan bangun. Durasi tidur harus antara 1 dan 16 jam.</p>
                    @endif
                </div>
            @endif
        </section>
    </div>
@endsection
