@extends('layouts.admin', ['title' => 'Panel peneliti'])

@section('content')
    <div class="card mx-auto max-w-lg py-12 text-center">
        <x-icon name="calendar" class="mx-auto mb-4 h-10 w-10 text-brand" />
        <h1 class="mb-2 text-xl font-bold">Belum ada kohort</h1>
        <p class="muted">Jalankan seeder awal atau buat kohort lebih dulu, lalu jadwal sesi bisa diatur dari menu Jadwal rilis.</p>
    </div>
@endsection
