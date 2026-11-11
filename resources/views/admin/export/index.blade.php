@extends('layouts.admin', ['title' => 'Ekspor'])

@section('content')
    <h1 class="mb-6 text-2xl font-bold">Ekspor Data</h1>
    <div class="grid gap-4 md:grid-cols-2">
        <div class="card">
            <p class="mb-1 font-semibold">Target pengingat (untuk blast WhatsApp)</p>
            <p class="mb-4 text-sm text-ink/60">Peserta yang belum mengisi diary semalam. Pisahkan per kelompok.</p>
            <div class="flex flex-wrap gap-2">
                <a class="btn-secondary px-4 py-2 text-sm" href="{{ route('admin.export.reminders', ['type' => 'diary', 'group' => 'intervention']) }}">Intervensi (CSV)</a>
                <a class="btn-secondary px-4 py-2 text-sm" href="{{ route('admin.export.reminders', ['type' => 'diary', 'group' => 'comparison']) }}">Pembanding (CSV)</a>
            </div>
        </div>
        <div class="card">
            <p class="mb-1 font-semibold">Dataset penelitian</p>
            <p class="mb-4 text-sm text-ink/60">Data mentah diary. Dataset lain menyusul.</p>
            <a class="btn-primary px-4 py-2 text-sm" href="{{ route('admin.export.diaries') }}">Diary tidur (CSV)</a>
        </div>
    </div>
    <p class="mt-4 text-xs text-ink/50">Setiap ekspor tercatat di audit log.</p>
@endsection
