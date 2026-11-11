@extends('layouts.base')

@section('body')
    <div class="mx-auto max-w-6xl px-3 pt-3 sm:px-5 sm:pt-5">
        {{-- Hero: panel malam + jam ritme --}}
        <section id="beranda" class="panel-night px-6 pt-5 pb-10 sm:px-10 md:pb-14">
            <header class="relative z-10 mb-12 flex items-center justify-between gap-4 md:mb-16">
                <x-logo light />
                <nav class="hidden items-center gap-1 text-sm md:flex">
                    <a href="#cara-kerja" class="rounded-full px-3.5 py-2 text-white/70 hover:bg-white/10 hover:text-white">Program</a>
                    <a href="#fitur" class="rounded-full px-3.5 py-2 text-white/70 hover:bg-white/10 hover:text-white">Fitur</a>
                    <a href="#faq" class="rounded-full px-3.5 py-2 text-white/70 hover:bg-white/10 hover:text-white">FAQ</a>
                </nav>
                <a href="{{ route('login') }}" class="btn-on-night btn-sm">Masuk</a>
            </header>

            <div class="relative z-10 grid items-center gap-10 md:grid-cols-[1.1fr_0.9fr]">
                <div>
                    <span class="mb-6 inline-flex rounded-full bg-white/10 px-3.5 py-1.5 text-xs font-semibold tracking-wide text-lavender">PROGRAM KESEHATAN TIDUR</span>
                    <h1 class="mb-5 text-[40px] leading-[1.05] font-bold sm:text-[56px]">Kenali ritmemu.<br>Selaraskan harimu.</h1>
                    <p class="mb-8 max-w-md text-lg text-white/70">Edukasi interaktif, catatan tidur, dan rencana aksi untuk membantu mengelola ritme tidur mahasiswa.</p>
                    <div class="flex flex-wrap items-center gap-4">
                        <a href="{{ route('login') }}" class="btn-on-night px-6 py-3.5 text-[15px]">Masuk sebagai Peserta</a>
                        <span class="text-sm text-white/50">Kode peserta diberikan tim peneliti.</span>
                    </div>
                </div>

                <div class="mx-auto w-full max-w-[22rem]">
                    <x-rhythm-dial theme="night"
                        :work="['onset' => 1410, 'offset' => 390, 'mid' => 180]"
                        :free="['onset' => 90, 'offset' => 570, 'mid' => 330]"
                        value="2j 30m" />
                    <p class="mt-4 text-center text-sm text-white/55">Contoh: tidur bergeser 2 jam lebih saat akhir pekan. Itulah <i>social jetlag</i>.</p>
                </div>
            </div>

            <svg class="pointer-events-none absolute -top-32 -left-40 h-[28rem] w-[28rem] text-white/[0.03]" viewBox="0 0 64 64" aria-hidden="true">
                <path d="M28.85 16.04 A18 18 0 1 0 47.96 35.15 A14 14 0 0 1 28.85 16.04 Z" fill="currentColor" />
            </svg>
        </section>
    </div>

    <main class="mx-auto max-w-6xl px-5">
        {{-- Cara kerja: urutan nyata --}}
        <section id="cara-kerja" class="py-16 md:py-24">
            <h2 class="mb-3 text-3xl font-bold">Empat minggu, tiga langkah</h2>
            <p class="mb-10 max-w-xl muted">Tiga sesi singkat setiap pekan, dirilis bersamaan untuk semua peserta setiap Senin, Rabu, dan Jumat pukul 19.00 WITA.</p>

            <ol class="grid gap-8 md:grid-cols-3">
                @foreach ([
                    ['Pahami', '12 sesi berupa infografis, podcast, dan video, masing-masing sekitar 8 menit.', 'book'],
                    ['Terapkan', 'Catat tidurmu setiap hari, lihat midpoint, lalu susun rencana aksi yang realistis.', 'moon'],
                    ['Pelajari dampaknya', 'Pengukuran sebelum, di tengah, dan di akhir program (T0, T1, T2).', 'chart'],
                ] as $i => [$title, $text, $icon])
                    <li class="relative">
                        <div class="mb-4 flex items-center gap-3">
                            <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-lavender text-primary"><x-icon :name="$icon" /></span>
                            <span class="text-sm font-semibold text-secondary num">Langkah {{ $i + 1 }}</span>
                        </div>
                        <h3 class="mb-2 text-xl font-bold">{{ $title }}</h3>
                        <p class="muted">{{ $text }}</p>
                    </li>
                @endforeach
            </ol>

            <div class="mt-12 overflow-hidden rounded-[var(--radius-card)] ring-1 ring-night/5">
                @foreach (config('ritnova.weeks') as $week => $theme)
                    <div class="grid grid-cols-[4.5rem_1fr] items-center gap-4 border-b border-night/5 bg-white px-5 py-4 last:border-0 sm:grid-cols-[6rem_1fr_auto]">
                        <span class="text-sm font-semibold text-primary num">Minggu {{ $week }}</span>
                        <span class="font-semibold">{{ $theme }}</span>
                        <span class="col-start-2 text-sm muted num sm:col-start-3">Sesi {{ str_pad(($week - 1) * 3 + 1, 2, '0', STR_PAD_LEFT) }}–{{ str_pad($week * 3, 2, '0', STR_PAD_LEFT) }}</span>
                    </div>
                @endforeach
            </div>
        </section>

        {{-- Fitur --}}
        <section id="fitur" class="grid gap-12 border-t border-night/5 py-16 md:grid-cols-[0.8fr_1.2fr] md:py-24">
            <div>
                <h2 class="mb-3 text-3xl font-bold">Yang kamu gunakan setiap hari</h2>
                <p class="muted">Dirancang untuk diisi kurang dari satu menit, di ponsel maupun laptop.</p>
            </div>
            <dl class="grid gap-x-10 gap-y-8 sm:grid-cols-2">
                @foreach ([
                    ['book', 'RITNOVA Learn', 'Materi singkat dengan satu aksi nyata di akhir setiap sesi.'],
                    ['moon', 'Sleep Tracker', 'Catat jam mulai tidur dan bangun. Tidur lewat tengah malam dihitung otomatis.'],
                    ['clock', 'SJL Check', 'Selisih midpoint hari kuliah dan hari bebas, dengan rumus yang bisa kamu periksa.'],
                    ['target', 'Goals & refleksi', 'Satu target perilaku per minggu dan evaluasi singkat tanpa menghakimi.'],
                ] as [$icon, $title, $text])
                    <div>
                        <dt class="mb-2 flex items-center gap-2.5 font-semibold"><x-icon :name="$icon" class="h-5 w-5 text-primary" />{{ $title }}</dt>
                        <dd class="text-sm muted">{{ $text }}</dd>
                    </div>
                @endforeach
            </dl>
        </section>

        {{-- Privasi & persetujuan --}}
        <section class="panel-lavender mb-16 grid gap-6 md:grid-cols-[auto_1fr] md:items-start md:p-10">
            <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-white text-primary"><x-icon name="shield" class="h-6 w-6" /></span>
            <div>
                <h2 class="mb-4 text-2xl font-bold">Privasi dan persetujuanmu</h2>
                <ul class="grid gap-3 text-[15px] sm:grid-cols-2">
                    <li class="flex gap-2.5"><x-icon name="check" class="mt-0.5 h-4 w-4 text-primary" />Masuk dengan kode peserta, bukan NIM atau nama.</li>
                    <li class="flex gap-2.5"><x-icon name="check" class="mt-0.5 h-4 w-4 text-primary" />Kegiatan dimulai setelah kamu menyetujui informasi penelitian.</li>
                    <li class="flex gap-2.5"><x-icon name="check" class="mt-0.5 h-4 w-4 text-primary" />Data hanya dapat diakses tim peneliti.</li>
                    <li class="flex gap-2.5"><x-icon name="check" class="mt-0.5 h-4 w-4 text-primary" />Tidak ada skor kesehatan yang ditampilkan ke publik.</li>
                </ul>
            </div>
        </section>

        {{-- FAQ --}}
        <section id="faq" class="mx-auto max-w-3xl pb-20">
            <h2 class="mb-6 text-3xl font-bold">Pertanyaan umum</h2>
            <div class="divide-y divide-night/5 rounded-[var(--radius-card)] bg-white ring-1 ring-night/5">
                @foreach ([
                    ['Siapa yang bisa ikut?', 'Mahasiswa yang terdaftar dalam penelitian dan menerima kode peserta dari tim peneliti.'],
                    ['Berapa lama setiap hari?', 'Mencatat tidur kurang dari satu menit. Satu sesi belajar sekitar 8 menit, tiga kali seminggu.'],
                    ['Apakah RITNOVA mendiagnosis gangguan tidur?', 'Tidak. RITNOVA adalah program edukasi. Bila kamu memiliki keluhan tidur yang bermakna, konsultasikan dengan tenaga kesehatan.'],
                    ['Bagaimana jika lupa kata sandi?', 'Hubungi tim peneliti untuk mengatur ulang kata sandi.'],
                ] as [$q, $a])
                    <details class="group px-5 py-4">
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-4 font-semibold">{{ $q }}<x-icon name="plus" class="h-5 w-5 text-primary transition group-open:rotate-45" /></summary>
                        <p class="mt-2 text-[15px] muted">{{ $a }}</p>
                    </details>
                @endforeach
            </div>
            <div class="mt-12 text-center">
                <a href="{{ route('login') }}" class="btn-primary px-6 py-3.5">Masuk sebagai Peserta</a>
            </div>
        </section>
    </main>

    <footer class="border-t border-night/5 py-8">
        <div class="mx-auto flex max-w-6xl flex-col items-center justify-between gap-3 px-5 text-xs muted sm:flex-row">
            <x-logo class="scale-90" />
            <p>Program edukasi, bukan layanan diagnosis. Data peserta dilindungi dan hanya dapat diakses tim peneliti.</p>
        </div>
    </footer>
@endsection
