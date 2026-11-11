@extends('layouts.admin', ['title' => 'Audit Log'])

@section('content')
    <h1 class="mb-6 text-2xl font-bold">Audit Log</h1>
    <div class="card overflow-x-auto p-0">
        <table class="w-full text-left text-sm">
            <thead class="bg-canvas text-ink/60"><tr><th class="p-3">Waktu</th><th class="p-3">Oleh</th><th class="p-3">Aksi</th><th class="p-3">Objek</th></tr></thead>
            <tbody>
                @foreach ($logs as $log)
                    <tr class="border-t border-black/5"><td class="p-3">{{ $log->created_at->format('d/m/Y H.i.s') }}</td><td class="p-3">{{ $log->user?->username ?? 'sistem' }}</td><td class="p-3">{{ $log->action }}</td><td class="p-3">{{ class_basename($log->auditable_type ?? '') }} {{ $log->auditable_id }}</td></tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $logs->links() }}</div>
@endsection
