@extends('layouts.participant', ['title' => 'SJL Check'])

@php
    $clock = fn ($h) => \App\Domain\Sleep\SjlResultData::clockLabel($h);
    $human = fn ($m) => intdiv($m, 60).' jam '.($m % 60).' menit';
@endphp

@section('content')
    <h1 class="mb-1 text-2xl font-bold">Kalkulator Social Jetlag</h1>
    <p class="mb-6 text-sm text-ink/60">Selisih titik tengah (midpoint) tidur antara hari kuliah dan hari bebas.</p>

    <div class="grid gap-6 lg:grid-cols-2">
        {{-- Hasil dari diary (Layar 11) --}}
        <section>
            <h2 class="mb-3 font-semibold">Dari catatan tidurmu <span class="font-normal text-ink/50">· {{ $from->translatedFormat('d M') }} – {{ $to->translatedFormat('d M') }}</span></h2>
            @if ($result->isSufficient())
                <div class="card">
                    <p class="text-sm text-ink/60">Perbedaan midpoint tidurmu</p>
                    <p class="mb-4 text-4xl font-bold text-primary">{{ $human($result->sjlMinutes) }}</p>
                    <div class="mb-4 grid grid-cols-2 gap-3 text-sm">
                        <div class="rounded-xl bg-lavender p-3">Hari kuliah<br><b class="text-lg">{{ $clock($result->mswHours) }}</b><br><span class="text-xs text-ink/60">{{ $result->validWorkdays }} malam</span></div>
                        <div class="rounded-xl bg-sky/60 p-3">Hari bebas<br><b class="text-lg">{{ $clock($result->msfHours) }}</b><br><span class="text-xs text-ink/60">{{ $result->validFreeDays }} malam</span></div>
                    </div>
                    <p class="font-semibold">Apa artinya?</p>
                    <p class="text-sm text-ink/70">Midpoint tidurmu berbeda {{ number_format($result->sjlMinutes / 60, 1, ',', '') }} jam antara hari kuliah dan hari bebas. Angka ini <b>bukan diagnosis penyakit</b>.</p>
                </div>
            @else
                <div class="card">
                    <p class="font-semibold">Data belum cukup</p>
                    <p class="mb-3 text-sm text-ink/70">Dibutuhkan minimal {{ config('ritnova.sjl.min_workdays') }} malam hari kuliah dan {{ config('ritnova.sjl.min_free_days') }} malam hari bebas yang lengkap. Saat ini: {{ $result->validWorkdays }} hari kuliah, {{ $result->validFreeDays }} hari bebas.</p>
                    <a href="{{ route('diary.index') }}" class="btn-secondary px-4 py-2 text-sm">Lengkapi catatan tidur</a>
                </div>
            @endif
        </section>

        {{-- Kalkulator manual (Layar 10) --}}
        <section>
            <h2 class="mb-3 font-semibold">Coba hitung <span class="badge bg-peach text-ink">Simulasi</span></h2>
            <form method="POST" action="{{ route('sjl.calculate') }}" class="card space-y-4">
                @csrf
                <div class="grid grid-cols-2 gap-4">
                    @foreach (['work' => 'Hari Kuliah', 'free' => 'Hari Bebas'] as $key => $label)
                        <fieldset class="space-y-2">
                            <legend class="mb-1 font-semibold">{{ $label }}</legend>
                            <label class="label" for="{{ $key }}_onset">Mulai tidur</label>
                            <input class="input" type="time" id="{{ $key }}_onset" name="{{ $key }}_onset" value="{{ old($key.'_onset', $manualInput[$key.'_onset'] ?? ($key === 'work' ? '23:30' : '01:30')) }}" required>
                            <label class="label" for="{{ $key }}_offset">Bangun</label>
                            <input class="input" type="time" id="{{ $key }}_offset" name="{{ $key }}_offset" value="{{ old($key.'_offset', $manualInput[$key.'_offset'] ?? ($key === 'work' ? '06:30' : '09:30')) }}" required>
                        </fieldset>
                    @endforeach
                </div>
                <button class="btn-primary w-full">Hitung Selisih Midpoint</button>

                @if ($manual)
                    @if ($manual->isSufficient())
                        <div class="rounded-xl bg-canvas p-4 text-sm">
                            <p>Midpoint hari kuliah <b>{{ $clock($manual->mswHours) }}</b> · hari bebas <b>{{ $clock($manual->msfHours) }}</b></p>
                            <p class="mt-1 text-lg font-bold text-primary">Selisih: {{ $human($manual->sjlMinutes) }}</p>
                            <p class="mt-1 text-xs text-ink/50">Contoh hitungan dari jam yang kamu masukkan — bukan data diary-mu.</p>
                        </div>
                    @else
                        <p class="rounded-xl bg-peach/60 p-3 text-sm">Periksa kembali waktu tidur dan bangun (durasi tidur harus antara 1 dan 16 jam).</p>
                    @endif
                @endif
            </form>
        </section>
    </div>

    <details class="card mt-6 text-sm">
        <summary class="cursor-pointer font-semibold">Cara hitung</summary>
        <ol class="mt-2 list-inside list-decimal space-y-1 text-ink/80">
            <li>Midpoint = jam mulai tidur + setengah durasi tidur (tidur lewat tengah malam dihitung lintas tanggal).</li>
            <li>MSW = rata-rata midpoint hari kuliah; MSF = rata-rata midpoint hari bebas.</li>
            <li>SJL = |MSF − MSW|, dilaporkan dalam jam dan menit.</li>
            <li>Hanya malam yang lengkap dan sudah diberi jenis hari yang dipakai. Data mentah tetap disimpan.</li>
        </ol>
        <p class="mt-2 text-xs text-ink/50">Versi algoritma: {{ $result->algorithmVersion }}</p>
    </details>
@endsection
