@extends('layouts.admin', ['title' => $version->exists ? 'Persetujuan '.$version->version : 'Versi persetujuan baru'])

@php
    $isAdmin = auth()->user()->role === \App\Models\User::ROLE_ADMIN;
    $editable = $isAdmin && $version->published_at === null;
@endphp

@section('content')
    <a href="{{ route('admin.consent.index') }}" class="mb-5 inline-flex items-center gap-2 text-sm font-medium text-brand hover:underline"><x-icon name="arrow-left" class="h-4 w-4" />Semua versi</a>

    <div class="mb-7">
        @include('admin.partials.page-head', [
            'heading' => $version->exists ? $version->title : 'Versi persetujuan baru',
            'sub' => match (true) {
                ! $version->exists => 'Isi awal disalin dari versi aktif. Tempel naskah resmi yang disetujui komisi etik.',
                $version->is_active => 'Versi '.$version->version.' sedang aktif. '.$agreed.' persetujuan tercatat.',
                $version->published_at !== null => 'Versi '.$version->version.' sudah diarsipkan. '.$agreed.' persetujuan tercatat.',
                default => 'Draf versi '.$version->version.'. Peserta belum melihatnya.',
            },
        ])
    </div>

    @error('delete')<div class="mb-6 rounded-[var(--radius-control)] bg-peach/60 px-4 py-3 text-sm text-warn-ink" role="alert">{{ $message }}</div>@enderror

    @if (! $editable && $version->exists)
        <div class="mb-6 flex items-start gap-3 rounded-[var(--radius-control)] bg-lavender px-4 py-3 text-sm" role="note">
            <x-icon name="lock" class="mt-0.5 h-4 w-4 shrink-0 text-brand" />
            <p>{{ $version->published_at ? 'Versi yang sudah terbit tidak bisa diubah, supaya isi yang disetujui peserta tetap bisa dibuktikan. Untuk revisi, buat versi baru.' : 'Hanya admin yang bisa mengubah dan menerbitkan dokumen persetujuan.' }}</p>
        </div>
    @endif

    <form method="POST" action="{{ $version->exists ? route('admin.consent.update', $version) : route('admin.consent.store') }}" enctype="multipart/form-data" class="grid gap-6 xl:grid-cols-[1fr_20rem]">
        @csrf
        @if ($version->exists) @method('PUT') @endif

        <section class="card space-y-4">
            <div class="grid gap-4 sm:grid-cols-[10rem_1fr]">
                <div>
                    <label class="label" for="version">Kode versi</label>
                    <input id="version" name="version" value="{{ old('version', $version->version) }}" class="input" maxlength="30" placeholder="v1.0" required @disabled(! $editable)>
                    @error('version')<p class="mt-1 text-sm text-rose-deep">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="label" for="title">Judul</label>
                    <input id="title" name="title" value="{{ old('title', $version->title) }}" class="input" maxlength="200" required @disabled(! $editable)>
                    @error('title')<p class="mt-1 text-sm text-rose-deep">{{ $message }}</p>@enderror
                </div>
            </div>
            <div>
                <label class="label" for="body">Isi dokumen</label>
                <textarea id="body" name="body" rows="18" class="input leading-relaxed" required @disabled(! $editable)>{{ old('body', $version->body) }}</textarea>
                <p class="mt-1 text-xs muted">Baris baru dipertahankan seperti yang diketik. Peserta membaca isi ini di halaman persetujuan.</p>
                @error('body')<p class="mt-1 text-sm text-rose-deep">{{ $message }}</p>@enderror
            </div>
        </section>

        <aside class="space-y-4 xl:sticky xl:top-24 xl:self-start">
            <section class="card space-y-3">
                <h2 class="font-bold">PDF informasi studi</h2>
                @if ($version->document_path)
                    <a href="{{ route('consent.document', $version) }}" class="inline-flex items-center gap-2 text-sm font-medium text-brand hover:underline"><x-icon name="file" class="h-4 w-4" />Unduh PDF saat ini</a>
                    @if ($editable)
                        <label class="choice text-sm"><input type="checkbox" name="remove_document" value="1" class="h-4 w-4 accent-primary">Hapus PDF ini</label>
                    @endif
                @else
                    <p class="text-sm muted">Belum ada PDF. Opsional; peserta bisa mengunduhnya dari halaman persetujuan.</p>
                @endif
                @if ($editable)
                    <input type="file" name="document" accept="application/pdf" class="block w-full text-sm file:mr-3 file:rounded-full file:border-0 file:bg-lavender file:px-4 file:py-2 file:font-medium file:text-brand">
                    <p class="text-xs muted">PDF, maksimal 10 MB. File baru menggantikan yang lama.</p>
                    @error('document')<p class="text-sm text-rose-deep">{{ $message }}</p>@enderror
                @endif
            </section>
            @if ($editable)
                <button type="submit" class="btn-primary w-full">{{ $version->exists ? 'Simpan draf' : 'Simpan sebagai draf' }}</button>
            @endif
        </aside>
    </form>

    @if ($editable && $version->exists)
        <section class="mt-6 grid gap-4 md:grid-cols-2" aria-label="Tindakan lain">
            <form method="POST" action="{{ route('admin.consent.publish', $version) }}" class="card flex flex-col" data-confirm-title="Terbitkan versi {{ $version->version }}?" data-confirm="Versi ini menggantikan versi aktif. Semua peserta aktif diminta membaca dan menyetujui ulang sebelum bisa memakai fitur lain. Setelah terbit, isinya tidak bisa diubah." data-confirm-ok="Terbitkan">
                @csrf
                <h2 class="mb-1 font-bold">Terbitkan</h2>
                <p class="mb-4 text-sm muted">Simpan draf dulu bila ada perubahan. Penerbitan tercatat di audit log.</p>
                <button class="btn-primary btn-sm mt-auto">Terbitkan versi ini</button>
            </form>
            <form method="POST" action="{{ route('admin.consent.destroy', $version) }}" class="card flex flex-col" data-confirm-tone="danger" data-confirm-title="Hapus draf {{ $version->version }}?" data-confirm="Draf dan PDF-nya dihapus permanen." data-confirm-ok="Hapus draf">
                @csrf @method('DELETE')
                <h2 class="mb-1 font-bold">Hapus draf</h2>
                <p class="mb-4 text-sm muted">Hanya draf yang belum pernah terbit.</p>
                <button class="btn btn-sm mt-auto bg-rose text-rose-deep hover:brightness-95">Hapus</button>
            </form>
        </section>
    @endif
@endsection
