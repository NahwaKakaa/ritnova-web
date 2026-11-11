@extends('layouts.participant', ['title' => 'Bantuan'])

@php
    $sections = [
        ['help', 'Pertanyaan umum', [
            ['Mengapa data belum cukup untuk SJL?', 'SJL membutuhkan catatan hari kuliah dan hari bebas. Lengkapi minimal '.config('ritnova.sjl.min_workdays').' malam hari kuliah dan '.config('ritnova.sjl.min_free_days').' malam hari bebas. Tidak ada data tidak sama dengan SJL nol.'],
            ['Kenapa sesi belum bisa dibuka?', 'Sesi dibuka bersamaan untuk semua peserta setiap Senin, Rabu, dan Jumat pukul 19.00 WITA, dan dikerjakan berurutan.'],
            ['Saya salah mengisi catatan tidur.', 'Buka Sleep Tracker, pilih tanggalnya, lalu ubah. Riwayat perubahan tetap disimpan.'],
        ]],
        ['pencil', 'Panduan pengisian', [
            ['Waktu di kasur atau mulai tidur?', '"Masuk tempat tidur" adalah jam kamu berbaring. "Mulai benar-benar tidur" adalah perkiraan jam kamu terlelap. Yang kedua dipakai untuk menghitung SJL.'],
            ['Hari kuliah atau hari bebas?', 'Pilih berdasarkan hari setelah bangun: hari kuliah bila ada jadwal atau kewajiban pagi, hari bebas bila kamu bisa bangun tanpa kewajiban.'],
        ]],
        ['shield', 'Privasi', [
            ['Siapa yang bisa melihat catatan saya?', 'Hanya tim peneliti, dengan kode peserta, bukan nama atau NIM. Data tidak ditampilkan ke publik.'],
        ]],
    ];
@endphp

@section('content')
    <div class="mb-8">
        <h1 class="page-title">Bantuan dan privasi</h1>
        <p class="mt-2 muted">Jawaban untuk kendala yang paling sering muncul.</p>
    </div>

    <div class="grid gap-8 lg:grid-cols-[1.4fr_0.6fr]">
        <div class="space-y-8">
            @foreach ($sections as [$icon, $title, $items])
                <section>
                    <h2 class="mb-3 flex items-center gap-2 font-bold"><x-icon :name="$icon" class="h-5 w-5 text-brand" />{{ $title }}</h2>
                    <div class="divide-y divide-ink/5 rounded-[var(--radius-card)] bg-surface ring-1 ring-ink/5">
                        @foreach ($items as [$q, $a])
                            <details class="group px-5 py-4">
                                <summary class="flex cursor-pointer list-none items-center justify-between gap-4 font-semibold">{{ $q }}<x-icon name="plus" class="h-5 w-5 text-brand transition group-open:rotate-45" /></summary>
                                <p class="mt-2 text-[15px] text-ink/70">{{ $a }}</p>
                            </details>
                        @endforeach
                    </div>
                </section>
            @endforeach
        </div>

        <aside class="space-y-4 lg:pt-9">
            <section class="panel-lavender">
                <h2 class="mb-2 font-bold">Hubungi tim peneliti</h2>
                <p class="mb-4 text-sm text-ink/70">Untuk kendala login, pengisian, atau bila ingin berhenti dari program.</p>
                <a href="{{ route('profile') }}" class="btn-primary btn-sm">Buka profil</a>
            </section>
            <p class="text-xs muted">Program edukasi ini tidak menggantikan konsultasi tenaga kesehatan bila kamu memiliki keluhan tidur yang bermakna.</p>
        </aside>
    </div>
@endsection
