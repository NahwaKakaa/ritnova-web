@extends('layouts.participant', ['title' => 'Bantuan'])

@section('content')
    <h1 class="mb-6 text-2xl font-bold">Bantuan & Privasi</h1>

    <div class="grid gap-6 md:grid-cols-2">
        <section>
            <h2 class="mb-3 font-semibold text-secondary">FAQ</h2>
            <div class="space-y-3">
                <details class="card"><summary class="cursor-pointer font-semibold">Mengapa data belum cukup untuk SJL?</summary><p class="mt-2 text-sm">SJL membutuhkan catatan pada hari kuliah dan hari bebas. Lengkapi diary minimal {{ config('ritnova.sjl.min_workdays') }} hari kuliah dan {{ config('ritnova.sjl.min_free_days') }} hari bebas agar hasilnya bisa dihitung. Tidak ada data <b>tidak sama</b> dengan SJL nol.</p></details>
                <details class="card"><summary class="cursor-pointer font-semibold">Kenapa sesi belum bisa dibuka?</summary><p class="mt-2 text-sm">Sesi dibuka serentak sesuai jadwal (Senin, Rabu, Jumat pukul 19.00 WITA) dan harus dikerjakan berurutan.</p></details>
                <details class="card"><summary class="cursor-pointer font-semibold">Saya salah mengisi catatan tidur.</summary><p class="mt-2 text-sm">Buka Sleep Tracker, pilih tanggalnya, lalu ubah. Riwayat perubahan tetap disimpan.</p></details>
            </div>
        </section>

        <section>
            <h2 class="mb-3 font-semibold text-secondary">Panduan pengisian</h2>
            <div class="space-y-3">
                <details class="card"><summary class="cursor-pointer font-semibold">Waktu di kasur vs mulai tidur</summary><p class="mt-2 text-sm">"Masuk tempat tidur" adalah jam kamu berbaring. "Mulai benar-benar tidur" adalah perkiraan jam kamu terlelap. Isi yang kedua dengan teliti karena dipakai untuk menghitung SJL.</p></details>
                <details class="card"><summary class="cursor-pointer font-semibold">Hari kuliah vs hari bebas</summary><p class="mt-2 text-sm">Pilih berdasarkan hari <b>setelah bangun</b>: hari kuliah bila ada jadwal/kewajiban pagi, hari bebas bila bisa bangun tanpa kewajiban.</p></details>
            </div>
        </section>

        <section>
            <h2 class="mb-3 font-semibold text-secondary">Privasi</h2>
            <details class="card"><summary class="cursor-pointer font-semibold">Siapa yang bisa melihat catatan saya?</summary><p class="mt-2 text-sm">Hanya tim peneliti, dengan kode peserta (bukan nama atau NIM). Data tidak ditampilkan ke publik.</p></details>
        </section>

        <section>
            <h2 class="mb-3 font-semibold text-secondary">Hubungi tim</h2>
            <div class="card text-sm">Kendala login, pengisian, atau ingin mengundurkan diri? Hubungi tim peneliti melalui kontak yang diberikan saat pendaftaran, atau ajukan penarikan diri lewat <a href="{{ route('profile') }}" class="text-primary underline">Profil</a>.</div>
        </section>
    </div>

    <p class="mt-8 text-xs text-ink/50">Program edukasi ini tidak menggantikan konsultasi tenaga kesehatan ketika kamu memiliki keluhan tidur yang bermakna.</p>
@endsection
