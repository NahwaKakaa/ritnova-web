@extends('layouts.base')

@section('body')
    <main class="mx-auto max-w-5xl px-5 py-10">
        <header class="mb-16 flex items-center justify-between">
            <span class="text-xl font-bold text-primary">RITNOVA ☾</span>
            <a href="{{ route('login') }}" class="btn-secondary px-4 py-2 text-sm">Masuk</a>
        </header>

        <section class="grid items-center gap-10 md:grid-cols-2">
            <div>
                <p class="mb-3 text-sm font-semibold tracking-wide text-secondary">PROGRAM KESEHATAN TIDUR</p>
                <h1 class="mb-4 text-4xl font-bold leading-tight md:text-5xl">Kenali ritmemu.<br>Selaraskan harimu.</h1>
                <p class="mb-8 text-ink/70">Edukasi interaktif, catatan tidur, dan rencana aksi untuk membantu mengelola ritme tidur mahasiswa.</p>
                <a href="{{ route('login') }}" class="btn-primary">Masuk sebagai Peserta</a>
            </div>
            <div class="card bg-lavender">
                <p class="mb-4 font-semibold">Program 4 minggu</p>
                <ol class="space-y-3 text-sm">
                    <li><b>01 Pahami</b> — 12 sesi: infografis, podcast, dan video.</li>
                    <li><b>02 Terapkan</b> — diary tidur, midpoint, dan rencana aksi.</li>
                    <li><b>03 Pelajari dampaknya</b> — pengukuran T0, T1, dan T2.</li>
                </ol>
            </div>
        </section>

        <p class="mt-16 text-xs text-ink/50">RITNOVA adalah program edukasi dan bukan layanan diagnosis. Data peserta dilindungi dan hanya dapat diakses tim peneliti.</p>
    </main>
@endsection
