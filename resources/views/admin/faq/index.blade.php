@extends('layouts.admin', ['title' => 'FAQ landing page'])

@php
    $items = old('faqs', $faqs->map(fn ($f) => ['id' => $f->id, 'question' => $f->question, 'answer' => $f->answer, 'is_published' => $f->is_published])->all());
@endphp

@section('content')
    <div class="mb-7 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        @include('admin.partials.page-head', ['heading' => 'FAQ landing page', 'sub' => 'Pertanyaan umum yang tampil di halaman depan RITNOVA. Urutan di sini sama dengan urutan di landing page.'])
        <a href="{{ route('landing') }}#faq" target="_blank" rel="noopener" class="btn-secondary btn-sm"><x-icon name="eye" class="h-4 w-4" />Lihat di landing page</a>
    </div>

    <form method="POST" action="{{ route('admin.faq.update') }}" class="grid gap-6 xl:grid-cols-[1fr_18rem]">
        @csrf @method('PUT')
        <section class="card" data-repeater>
            <div class="space-y-4" data-repeater-list>
                @foreach ($items as $i => $f)
                    @include('admin.faq.partials.item', ['i' => $i, 'f' => $f])
                @endforeach
            </div>
            <template>@include('admin.faq.partials.item', ['i' => '__i__', 'f' => ['is_published' => true]])</template>
            <button type="button" class="btn-secondary btn-sm mt-4" data-repeater-add><x-icon name="plus" class="h-4 w-4" />Tambah pertanyaan</button>
        </section>

        <aside class="space-y-4 xl:sticky xl:top-24 xl:self-start">
            <div class="card">
                <h2 class="mb-2 font-bold">Simpan</h2>
                <p class="mb-4 text-sm muted">Perubahan langsung tampil di landing page dan tercatat di audit log. Hapus centang "Tampilkan" untuk menyembunyikan tanpa menghapus.</p>
                <button class="btn-primary w-full">Simpan FAQ</button>
            </div>
            <p class="px-1 text-xs muted">Bila semua pertanyaan disembunyikan atau dihapus, bagian FAQ dan menunya otomatis hilang dari landing page.</p>
        </aside>
    </form>
@endsection
