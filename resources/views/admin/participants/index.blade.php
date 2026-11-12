@extends('layouts.admin', ['title' => 'Peserta & kelompok'])

@php
    $groups = config('ritnova.groups');
    $phases = config('ritnova.phases');
    $activeFilters = collect($filters)->filter()->count();
@endphp

@section('content')
    <div class="mb-7 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        @include('admin.partials.page-head', ['heading' => 'Peserta & kelompok', 'sub' => 'Kode peserta acak, tanpa nama atau NIM. Kelompok bisa disembunyikan saat memeriksa data agar penilaian tetap buta.'])
        <div class="flex flex-wrap gap-2">
            <button type="button" class="btn-secondary btn-sm" data-blind-toggle="participants-table" aria-pressed="false"><x-icon name="eye-off" class="h-4 w-4" /><span data-blind-label>Sembunyikan kelompok</span></button>
            <a href="{{ route('admin.export.download', 'participants') }}" class="btn-secondary btn-sm"><x-icon name="download" class="h-4 w-4" />Ekspor</a>
        </div>
    </div>

    @include('admin.partials.credentials')

    @if ($canManage)
        <details class="card mb-6 group" @if ($errors->any() || old('rows')) open @endif>
            <summary class="flex cursor-pointer list-none items-center justify-between gap-3 font-semibold">
                <span class="flex items-center gap-3"><span class="flex h-9 w-9 items-center justify-center rounded-xl bg-lavender text-brand"><x-icon name="upload" class="h-[18px] w-[18px]" /></span>Impor kode peserta</span>
                <span class="text-sm font-normal muted group-open:hidden">Buka</span><span class="hidden text-sm font-normal muted group-open:inline">Tutup</span>
            </summary>
            <form method="POST" action="{{ route('admin.participants.import') }}" enctype="multipart/form-data" class="mt-5 grid gap-5 lg:grid-cols-[1fr_18rem]">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label class="label" for="rows">Tempel daftar peserta</label>
                        <textarea id="rows" name="rows" rows="5" class="input font-mono text-sm" placeholder="RV-101,intervensi&#10;RV-102,pembanding&#10;RV-103,intervensi">{{ old('rows') }}</textarea>
                    </div>
                    <div>
                        <label class="label" for="file">atau unggah file CSV</label>
                        <input id="file" name="file" type="file" accept=".csv,text/csv,text/plain" class="input py-2.5 file:mr-3 file:rounded-lg file:border-0 file:bg-lavender file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-brand">
                    </div>
                    <button class="btn-primary"><x-icon name="upload" class="h-4 w-4" />Impor ke {{ $cohort->name }}</button>
                </div>
                <aside class="rounded-2xl bg-canvas p-4 text-sm ring-1 ring-ink/5">
                    <p class="mb-2 font-semibold">Format</p>
                    <p class="mb-3 muted">Satu peserta per baris: <code class="rounded bg-lavender px-1 text-brand">kode,kelompok</code>. Kolom ketiga opsional untuk kata sandi.</p>
                    <ul class="space-y-1.5 muted">
                        <li>Kelompok: intervensi atau pembanding, boleh dikosongkan dulu.</li>
                        <li>Kode yang sudah terdaftar dilewati.</li>
                        <li>Kata sandi kosong dibuatkan otomatis dan tampil sekali.</li>
                    </ul>
                </aside>
            </form>
        </details>
    @endif

    {{-- Filter --}}
    <form method="GET" class="mb-4 flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-end">
        <div class="relative sm:w-56">
            <label for="q" class="sr-only">Cari kode</label>
            <x-icon name="search" class="pointer-events-none absolute top-1/2 left-3.5 h-4 w-4 -translate-y-1/2 text-ink/40" />
            <input id="q" name="q" value="{{ $filters['q'] ?? '' }}" class="input py-2.5 pl-10" placeholder="Cari kode">
        </div>
        <div class="sm:w-48">
            <label for="group" class="sr-only">Kelompok</label>
            <select id="group" name="group" class="input py-2.5" data-autosubmit>
                <option value="">Semua kelompok</option>
                @foreach ($groups as $k => $label)<option value="{{ $k }}" @selected(($filters['group'] ?? '') === $k)>{{ $label }}</option>@endforeach
                <option value="none" @selected(($filters['group'] ?? '') === 'none')>Belum ditetapkan</option>
            </select>
        </div>
        <div class="sm:w-64">
            <label for="stage" class="sr-only">Fase studi</label>
            <select id="stage" name="stage" class="input py-2.5" data-autosubmit>
                <option value="">Semua fase studi</option>
                @foreach ($stages as $k => $label)<option value="{{ $k }}" @selected(($filters['stage'] ?? '') === $k)>{{ $label }}</option>@endforeach
            </select>
        </div>
        <button class="btn-secondary btn-sm py-2.5">Terapkan</button>
        @if ($activeFilters)<a href="{{ route('admin.participants.index') }}" class="btn-ghost btn-sm py-2.5">Hapus filter</a>@endif
        <p class="text-sm muted sm:ml-auto sm:self-center"><span class="font-semibold text-ink num">{{ $participants->count() }}</span> peserta</p>
    </form>

    <div class="card overflow-hidden p-0">
        <div class="overflow-x-auto">
            <table class="data-table" id="participants-table">
                <thead>
                    <tr>
                        <th>Kode</th><th>Kelompok</th><th>Consent</th><th>Persiapan</th>
                        @foreach ($phases as $ph)<th class="text-center">{{ $ph }}</th>@endforeach
                        <th>Sesi</th><th class="text-right">Diary valid</th><th>Login terakhir</th><th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($participants as $u)
                        <tr>
                            <td><a href="{{ route('admin.participants.show', $u) }}" class="font-semibold text-brand hover:underline">{{ $u->username }}</a></td>
                            <td data-group-cell>
                                @if ($u->study_group)
                                    <span @class(['pill', 'bg-lavender text-brand' => $u->study_group === 'intervention', 'bg-sky/60 text-ink/80' => $u->study_group === 'comparison'])>{{ $groups[$u->study_group] }}</span>
                                @else
                                    <span class="pill-warn">Belum</span>
                                @endif
                            </td>
                            <td>@if ($u->has_consent)<x-icon name="check" class="h-4 w-4 text-mint-deep" /><span class="sr-only">Ya</span>@else<span class="muted">—</span>@endif</td>
                            <td>@if ($u->onboarded_at)<x-icon name="check" class="h-4 w-4 text-mint-deep" /><span class="sr-only">Ya</span>@else<span class="muted">—</span>@endif</td>
                            @foreach ($phases as $ph)
                                <td class="text-center">
                                    @if (($instrumentCounts[$ph] ?? 0) === 0)<span class="muted" title="Instrumen belum diisi">·</span>
                                    @elseif ($phaseDone[$u->id][$ph] ?? false)<x-icon name="check" class="mx-auto h-4 w-4 text-mint-deep" /><span class="sr-only">Lengkap</span>
                                    @else<span class="muted">—</span>@endif
                                </td>
                            @endforeach
                            <td class="num whitespace-nowrap" data-group-cell>
                                @if ($u->study_group === 'intervention')<span class="font-semibold">{{ $u->completed_sessions }}</span><span class="muted">/{{ $released }}</span>@else<span class="muted">—</span>@endif
                            </td>
                            <td class="text-right num">{{ $u->valid_diaries }}</td>
                            <td class="whitespace-nowrap text-ink/70">{{ $u->last_login_at?->diffForHumans() ?? '—' }}</td>
                            <td>@if ($u->status === 'withdrawn')<span class="pill-alert">Mundur</span>@else<span class="pill-done">Aktif</span>@endif</td>
                        </tr>
                    @empty
                        <tr><td colspan="12" class="py-12 text-center muted">Tidak ada peserta yang cocok dengan filter ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <p class="mt-3 text-xs muted">Sesi dihitung terhadap {{ $released }} sesi yang sudah dirilis. Kolom sesi ikut disembunyikan dalam mode buta karena hanya kelompok intervensi yang menerima modul.</p>
@endsection
