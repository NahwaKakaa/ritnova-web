@extends('layouts.participant', ['title' => 'SJL Check'])

@section('content')
    <h1 class="mb-1 text-2xl font-bold">Kalkulator Social Jetlag</h1>
    <p class="mb-6 text-sm text-ink/60">Data {{ $from->format('d/m') }} – {{ $to->format('d/m') }}</p>

    @if ($result->isSufficient())
        <div class="card mb-4">
            <p class="text-sm text-ink/60">Perbedaan midpoint tidurmu</p>
            <p class="mb-4 text-4xl font-bold text-primary">{{ $result->sjlLabel() }}</p>
            <div class="grid grid-cols-2 gap-3 text-sm">
                <div class="rounded-xl bg-lavender p-3">Hari kuliah<br><b>{{ \App\Domain\Sleep\SjlResultData::clockLabel($result->mswHours) }}</b></div>
                <div class="rounded-xl bg-sky/60 p-3">Hari bebas<br><b>{{ \App\Domain\Sleep\SjlResultData::clockLabel($result->msfHours) }}</b></div>
            </div>
            <p class="mt-4 text-sm">Angka ini menunjukkan selisih jam tengah tidurmu antara hari kuliah dan hari bebas. Angka ini <b>bukan diagnosis penyakit</b>.</p>
        </div>
    @else
        <div class="card mb-4">
            <p class="font-semibold">Data belum cukup</p>
            <p class="text-sm text-ink/70">Tambahkan catatan hari kuliah (min. {{ config('ritnova.sjl.min_workdays') }}) dan hari bebas (min. {{ config('ritnova.sjl.min_free_days') }}). Saat ini: {{ $result->validWorkdays }} hari kuliah, {{ $result->validFreeDays }} hari bebas.</p>
        </div>
    @endif

    <details class="card text-sm">
        <summary class="cursor-pointer font-semibold">Cara hitung</summary>
        <p class="mt-2">Midpoint = jam mulai tidur + setengah durasi tidur. SJL = |rata-rata midpoint hari bebas − rata-rata midpoint hari kuliah|. Versi algoritma: {{ $result->algorithmVersion }}.</p>
    </details>
@endsection
