@extends('layouts.admin', ['title' => 'Peserta'])

@section('content')
    <h1 class="mb-6 text-2xl font-bold">Peserta</h1>
    <div class="card overflow-x-auto p-0">
        <table class="w-full text-left text-sm">
            <thead class="bg-canvas text-ink/60"><tr><th class="p-3">Kode</th><th class="p-3">Kelompok</th><th class="p-3">Sesi selesai</th><th class="p-3">Diary valid</th><th class="p-3">Login terakhir</th></tr></thead>
            <tbody>
                @foreach ($participants as $p)
                    <tr class="border-t border-black/5"><td class="p-3 font-semibold">{{ $p->username }}</td><td class="p-3">{{ config('ritnova.groups')[$p->study_group] ?? '-' }}</td><td class="p-3">{{ $p->completed_sessions }}/12</td><td class="p-3">{{ $p->valid_diaries }}</td><td class="p-3">{{ $p->last_login_at?->format('d/m H.i') ?? '-' }}</td></tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $participants->links() }}</div>
@endsection
