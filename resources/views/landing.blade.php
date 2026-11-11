@extends('layouts.base')

@section('body')
    <header class="sticky top-0 z-20 border-b border-black/5 bg-canvas/90 backdrop-blur">
        <div class="mx-auto flex max-w-5xl items-center justify-between px-5 py-4">
            <a href="#beranda" class="text-xl font-bold text-primary">RITNOVA ☾</a>
            <nav class="hidden gap-6 text-sm font-medium text-ink/70 md:flex">
                <a href="#beranda" class="hover:text-primary">Beranda</a>
                <a href="#program" class="hover:text-primary">Program</a>
                <a href="#fitur" class="hover:text-primary">Fitur</a>
                <a href="#faq" class="hover:text-primary">FAQ</a>
            </nav>
            <a href="{{ route('login') }}" class="btn-secondary px-4 py-2 text-sm">Masuk</a>
        </div>
    </header>

    <main class="mx-auto max-w-5xl px-5">
        {{-- 1. Penjelasan singkat --}}
        <section id="beranda" class="grid items-center gap-10 py-16 md:grid-cols-2 md:py-24">
            <div>
                <p class="mb-3 text-sm font-semibold tracking-wide text-secondary">PROGRAM KESEHATAN TIDUR</p>
                <h1 class="mb-4 text-4xl leading-tight font-bold md:text-5xl">Kenali ritmemu.<br>Selaraskan harimu.</h1>
                <p class="mb-8 text-lg text-ink/70">Edukasi interaktif, catatan tidur, dan rencana aksi untuk membantu mengelola ritme tidur mahasiswa.</p>
                <a href="{{ route('login') }}" class="btn-primary">Masuk sebagai Peserta</a>
            </div>
            <div class="card bg-lavender">
                <p class="mb-4 font-semibold">Program 4 minggu</p>
                <ol class="space-y-3">
                    <li><b>01 Pahami</b> — 12 sesi: infografis, podcast, dan video.</li>
                    <li><b>02 Terapkan</b> — diary tidur, midpoint, dan rencana aksi.</li>
                    <li><b>03 Pelajari dampaknya</b> — pengukuran T0, T1, dan T2.</li>
                </ol>
            </div>
        </section>

        {{-- 2. Alur program 4 minggu --}}
        <section id="program" class="py-12">
            <h2 class="mb-2 text-2xl font-bold">Alur program 4 minggu</h2>
            <p class="mb-6 text-ink/70">Tiga sesi setiap minggu, dirilis serentak Senin, Rabu, dan Jumat pukul 19.00 WITA.</p>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach (config('ritnova.weeks') as $week => $theme)
                    <div class="card">
                        <p class="text-xs font-semibold text-secondary">MINGGU {{ $week }}</p>
                        <p class="mb-1 text-lg font-semibold">{{ $theme }}</p>
                        <p class="text-sm text-ink/60">Sesi {{ str_pad(($week - 1) * 3 + 1, 2, '0', STR_PAD_LEFT) }}–{{ str_pad($week * 3, 2, '0', STR_PAD_LEFT) }}</p>
                    </div>
                @endforeach
            </div>
        </section>

        {{-- Fitur --}}
        <section id="fitur" class="py-12">
            <h2 class="mb-6 text-2xl font-bold">Yang kamu dapatkan</h2>
            <div class="grid gap-4 sm:grid-cols-2">
                @foreach ([
                    ['▤', 'RITNOVA Learn', 'Materi singkat dalam bentuk video, infografis, dan podcast, masing-masing dengan satu aksi nyata.'],
                    ['☽', 'Sleep Tracker', 'Catat jam tidur dan bangun setiap hari dalam waktu kurang dari satu menit.'],
                    ['◷', 'SJL Check', 'Lihat selisih titik tengah tidur antara hari kuliah dan hari bebas, dengan rumus yang transparan.'],
                    ['◎', 'Goals & Refleksi', 'Ubah wawasan menjadi target mingguan dan rencana aksi yang realistis.'],
                ] as [$icon, $title, $text])
                    <div class="card flex gap-4">
                        <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-lavender text-xl text-primary">{{ $icon }}</span>
                        <div><p class="font-semibold">{{ $title }}</p><p class="text-sm text-ink/70">{{ $text }}</p></div>
                    </div>
                @endforeach
            </div>
        </section>

        {{-- 3. Privasi & persetujuan --}}
        <section class="py-12">
            <div class="card bg-sky/40">
                <h2 class="mb-2 text-xl font-bold">Privasi & persetujuan</h2>
                <ul class="list-inside list-disc space-y-1 text-sm text-ink/80">
                    <li>Kamu masuk dengan <b>kode peserta</b>, bukan NIM atau nama.</li>
                    <li>Keikutsertaan dimulai setelah kamu membaca dan menyetujui informasi penelitian.</li>
                    <li>Data hanya dapat diakses tim peneliti, dan kamu bisa menarik diri kapan saja.</li>
                    <li>Tidak ada skor kesehatan yang ditampilkan ke publik.</li>
                </ul>
            </div>
        </section>

        {{-- FAQ --}}
        <section id="faq" class="py-12">
            <h2 class="mb-6 text-2xl font-bold">Pertanyaan umum</h2>
            <div class="space-y-3">
                @foreach ([
                    ['Siapa yang bisa ikut?', 'Peserta yang terdaftar dalam penelitian dan menerima kode peserta dari tim peneliti.'],
                    ['Berapa lama setiap hari?', 'Mencatat tidur kurang dari satu menit; satu sesi belajar sekitar 8 menit, tiga kali seminggu.'],
                    ['Apakah RITNOVA mendiagnosis gangguan tidur?', 'Tidak. RITNOVA adalah program edukasi. Bila memiliki keluhan tidur yang bermakna, konsultasikan dengan tenaga kesehatan.'],
                    ['Bagaimana jika lupa kata sandi?', 'Hubungi tim peneliti untuk reset kata sandi.'],
                ] as [$q, $a])
                    <details class="card"><summary class="cursor-pointer font-semibold">{{ $q }}</summary><p class="mt-2 text-sm text-ink/70">{{ $a }}</p></details>
                @endforeach
            </div>
        </section>

        {{-- 4. Pintu masuk peserta --}}
        <section class="py-12 text-center">
            <h2 class="mb-4 text-2xl font-bold">Sudah punya kode peserta?</h2>
            <a href="{{ route('login') }}" class="btn-primary">Masuk sebagai Peserta</a>
        </section>
    </main>

    <footer class="border-t border-black/5 py-8 text-center text-xs text-ink/50">
        RITNOVA adalah program edukasi dan bukan layanan diagnosis. Data peserta dilindungi dan hanya dapat diakses tim peneliti.
    </footer>
@endsection
