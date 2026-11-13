@extends('layouts.admin', ['title' => 'Dokumen persetujuan'])

@php $isAdmin = auth()->user()->role === \App\Models\User::ROLE_ADMIN; @endphp

@section('content')
    <div class="mb-7 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        @include('admin.partials.page-head', ['heading' => 'Dokumen persetujuan', 'sub' => 'Isi dokumen mengikuti naskah yang disetujui komisi etik. Versi yang sudah terbit dibekukan sebagai bukti; perubahan dibuat sebagai versi baru dan peserta diminta menyetujui ulang.'])
        @if ($isAdmin)
            <a href="{{ route('admin.consent.create') }}" class="btn-primary btn-sm"><x-icon name="plus" class="h-4 w-4" />Buat versi baru</a>
        @endif
    </div>

    <section class="mb-6 grid gap-4 md:grid-cols-3" aria-label="Status persetujuan">
        <div class="card">
            <p class="text-sm muted">Versi aktif</p>
            <p class="mt-1 text-2xl font-bold num">{{ $current?->version ?? 'Belum ada' }}</p>
            <p class="mt-1 text-sm muted">{{ $current?->published_at ? 'Terbit '.$current->published_at->translatedFormat('d F Y, H:i') : 'Peserta belum bisa memulai.' }}</p>
        </div>
        <div class="card">
            <p class="text-sm muted">Sudah menyetujui versi aktif</p>
            <p class="mt-1 text-2xl font-bold num">{{ $participantTotal - $pending->count() }} <span class="text-base font-medium muted">dari {{ $participantTotal }}</span></p>
            <p class="mt-1 text-sm muted">Peserta yang belum mengundurkan diri.</p>
        </div>
        <div class="card">
            <p class="text-sm muted">Menunggu persetujuan</p>
            <p class="mt-1 text-2xl font-bold num">{{ $pending->count() }}</p>
            @if ($pending->isNotEmpty())
                <p class="mt-2 flex flex-wrap gap-1.5">
                    @foreach ($pending->take(24) as $p)<a href="{{ route('admin.participants.show', $p->id) }}" class="pill-wait hover:underline">{{ $p->username }}</a>@endforeach
                    @if ($pending->count() > 24)<span class="pill-muted">+{{ $pending->count() - 24 }}</span>@endif
                </p>
            @else
                <p class="mt-1 text-sm muted">Semua peserta aktif sudah setuju.</p>
            @endif
        </div>
    </section>

    <div class="card overflow-hidden p-0">
        <div class="overflow-x-auto">
            <table class="data-table">
                <thead><tr><th>Versi</th><th>Judul</th><th>Status</th><th>Terbit</th><th class="text-right">Persetujuan</th><th>PDF</th><th><span class="sr-only">Aksi</span></th></tr></thead>
                <tbody>
                    @forelse ($versions as $v)
                        <tr>
                            <td class="font-semibold num">{{ $v->version }}</td>
                            <td class="min-w-56">{{ $v->title }}</td>
                            <td>
                                @if ($v->is_active)<span class="pill-done">Aktif</span>
                                @elseif ($v->published_at)<span class="pill-muted">Arsip</span>
                                @else<span class="pill-wait">Draf</span>@endif
                            </td>
                            <td class="whitespace-nowrap num">{{ $v->published_at?->translatedFormat('d M Y') ?? '—' }}</td>
                            <td class="text-right num">{{ $v->agreed_count }}</td>
                            <td>@if ($v->document_path)<a href="{{ route('consent.document', $v) }}" class="text-brand hover:underline">Unduh</a>@else<span class="muted">—</span>@endif</td>
                            <td class="text-right"><a href="{{ route('admin.consent.edit', $v) }}" class="btn-ghost btn-sm whitespace-nowrap"><x-icon name="{{ $v->published_at || ! $isAdmin ? 'file' : 'pencil' }}" class="h-4 w-4" />{{ $v->published_at || ! $isAdmin ? 'Lihat' : 'Kelola' }}</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="py-10 text-center muted">Belum ada dokumen persetujuan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
