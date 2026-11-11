@extends('layouts.admin', ['title' => 'Paparan Intervensi'])

@section('content')
    <h1 class="mb-6 text-2xl font-bold">Paparan Intervensi</h1>
    <div class="card overflow-x-auto p-0">
        <table class="w-full text-left text-sm">
            <thead class="bg-canvas text-ink/60"><tr><th class="p-3">Peserta</th><th class="p-3">Sesi</th><th class="p-3">Dibuka</th><th class="p-3">Media</th><th class="p-3">Aktif</th><th class="p-3">Status</th></tr></thead>
            <tbody>
                @foreach ($progress as $p)
                    <tr class="border-t border-black/5">
                        <td class="p-3 font-semibold">{{ $p->user->username }}</td>
                        <td class="p-3">{{ str_pad($p->learningSession->number, 2, '0', STR_PAD_LEFT) }}</td>
                        <td class="p-3">{{ $p->first_open_at?->format('d/m H.i') }}</td>
                        <td class="p-3">{{ round($p->media_completion_pct * 100) }}%</td>
                        <td class="p-3">{{ gmdate('i:s', $p->active_seconds) }}</td>
                        <td class="p-3">{{ $p->completed_at ? 'Selesai' : 'Berjalan' }}{{ $p->is_late ? ' · terlambat' : '' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $progress->links() }}</div>
@endsection
