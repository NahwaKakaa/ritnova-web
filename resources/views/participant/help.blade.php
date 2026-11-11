@extends('layouts.participant', ['title' => 'Bantuan'])

@section('content')
    <h1 class="mb-6 text-2xl font-bold">Bantuan & Privasi</h1>
    <div class="space-y-3">
        <details class="card"><summary class="cursor-pointer font-semibold">Mengapa data belum cukup untuk SJL?</summary><p class="mt-2 text-sm">SJL membutuhkan catatan pada hari kuliah dan hari bebas. Lengkapi diary beberapa hari agar hasilnya bisa dihitung.</p></details>
        <details class="card"><summary class="cursor-pointer font-semibold">Apa bedanya waktu di kasur dan mulai tidur?</summary><p class="mt-2 text-sm">Isi "mulai benar-benar tidur" dengan perkiraan jam kamu terlelap, bukan jam masuk tempat tidur.</p></details>
        <details class="card"><summary class="cursor-pointer font-semibold">Siapa yang bisa melihat catatan saya?</summary><p class="mt-2 text-sm">Hanya tim peneliti, dengan kode peserta (bukan nama atau NIM).</p></details>
        <div class="card text-sm">Kendala login, pengisian, atau ingin mengundurkan diri? Hubungi tim peneliti.</div>
    </div>
    <p class="mt-6 text-xs text-ink/50">Program edukasi ini tidak menggantikan konsultasi tenaga kesehatan bila kamu memiliki keluhan tidur yang bermakna.</p>
@endsection
