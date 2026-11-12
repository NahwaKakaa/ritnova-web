@extends('layouts.admin', ['title' => 'Audit log'])

@php
    $fmt = fn ($v) => is_array($v) ? json_encode($v, JSON_UNESCAPED_UNICODE) : (is_bool($v) ? ($v ? 'ya' : 'tidak') : (string) ($v ?? '—'));
@endphp

@section('content')
    <div class="mb-7">
        @include('admin.partials.page-head', ['heading' => 'Audit log', 'sub' => 'Siapa mengubah apa dan kapan: login, perubahan data, jadwal, konten, kelompok, dan ekspor. Catatan tidak bisa dihapus dari panel.'])
    </div>

    <form method="GET" class="card mb-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-[repeat(5,minmax(0,1fr))_auto] lg:items-end">
        <div>
            <label class="label text-xs" for="aksi">Aksi</label>
            <select id="aksi" name="aksi" class="input px-3 py-2 text-sm">
                <option value="">Semua aksi</option>
                @foreach (\App\Support\AuditLabel::ACTIONS as $k => $l)<option value="{{ $k }}" @selected(($filters['aksi'] ?? '') === $k)>{{ $l }}</option>@endforeach
            </select>
        </div>
        <div>
            <label class="label text-xs" for="objek">Objek</label>
            <select id="objek" name="objek" class="input px-3 py-2 text-sm">
                <option value="">Semua objek</option>
                @foreach (\App\Support\AuditLabel::OBJECTS as $k => $l)<option value="{{ $k }}" @selected(($filters['objek'] ?? '') === $k)>{{ ucfirst($l) }}</option>@endforeach
            </select>
        </div>
        <div>
            <label class="label text-xs" for="oleh">Oleh</label>
            <input id="oleh" name="oleh" value="{{ $filters['oleh'] ?? '' }}" class="input px-3 py-2 text-sm" placeholder="Kode / username">
        </div>
        <div>
            <label class="label text-xs" for="dari">Dari</label>
            <input id="dari" name="dari" type="date" value="{{ $filters['dari'] ?? '' }}" class="input px-3 py-2 text-sm">
        </div>
        <div>
            <label class="label text-xs" for="sampai">Sampai</label>
            <input id="sampai" name="sampai" type="date" value="{{ $filters['sampai'] ?? '' }}" class="input px-3 py-2 text-sm">
        </div>
        <div class="flex gap-2">
            <button class="btn-primary btn-sm py-2.5">Terapkan</button>
            @if (array_filter($filters))<a href="{{ route('admin.audit.index') }}" class="btn-ghost btn-sm py-2.5">Reset</a>@endif
        </div>
    </form>

    <div class="card overflow-hidden p-0">
        <div class="overflow-x-auto">
            <table class="data-table">
                <thead><tr><th>Waktu (WITA)</th><th>Oleh</th><th>Aksi</th><th>Perubahan</th><th>IP</th></tr></thead>
                <tbody>
                    @forelse ($logs as $log)
                        @php $changes = collect($log->new_values ?? [])->keys()->merge(collect($log->old_values ?? [])->keys())->unique()->values(); @endphp
                        <tr class="align-top">
                            <td class="whitespace-nowrap num">{{ $log->created_at->translatedFormat('j M Y') }}<span class="block text-xs muted">{{ $log->created_at->format('H.i.s') }}</span></td>
                            <td class="whitespace-nowrap">
                                <span class="font-medium">{{ $log->user?->username ?? 'sistem' }}</span>
                                @if ($log->user)<span class="block text-xs muted">{{ ['participant' => 'Peserta', 'researcher' => 'Peneliti', 'admin' => 'Admin'][$log->user->role] ?? $log->user->role }}</span>@endif
                            </td>
                            <td class="min-w-44">
                                <span class="font-medium">{{ \App\Support\AuditLabel::action($log) }}</span>
                                @if ($log->auditable_id)<span class="block text-xs muted">#{{ $log->auditable_id }}</span>@endif
                            </td>
                            <td class="min-w-64">
                                @if ($changes->isEmpty())<span class="muted">—</span>
                                @else
                                    <details>
                                        <summary class="cursor-pointer text-sm text-brand">{{ $changes->take(3)->implode(', ') }}{{ $changes->count() > 3 ? ' +'.($changes->count() - 3) : '' }}</summary>
                                        <dl class="mt-2 space-y-1 rounded-xl bg-canvas p-3 text-xs ring-1 ring-ink/5">
                                            @foreach ($changes as $key)
                                                <div class="grid grid-cols-[8rem_1fr] gap-2">
                                                    <dt class="truncate font-mono text-brand">{{ $key }}</dt>
                                                    <dd class="break-all">
                                                        @if ($log->old_values && array_key_exists($key, $log->old_values))<span class="text-rose-deep line-through">{{ \Illuminate\Support\Str::limit($fmt($log->old_values[$key]), 80) }}</span> @endif
                                                        @if ($log->new_values && array_key_exists($key, $log->new_values))<span>{{ \Illuminate\Support\Str::limit($fmt($log->new_values[$key]), 120) }}</span>@endif
                                                    </dd>
                                                </div>
                                            @endforeach
                                        </dl>
                                    </details>
                                @endif
                            </td>
                            <td class="whitespace-nowrap text-xs muted num">{{ $log->ip_address ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="py-12 text-center muted">Tidak ada catatan yang cocok.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-4">{{ $logs->links() }}</div>
@endsection
