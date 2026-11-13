@extends('layouts.admin', ['title' => 'Instrumen T0–T2'])

@php $methods = ['none' => 'Tanpa skor', 'sum' => 'Jumlah', 'mean' => 'Rata-rata']; @endphp

@section('content')
    <div class="mb-7 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        @include('admin.partials.page-head', ['heading' => 'Instrumen T0–T2', 'sub' => 'Butir kuesioner dan aturan skoring diisi dari instrumen yang sudah tervalidasi. Jangan mengarang butir atau mengubah skala tanpa izin pemilik instrumen.'])
        <a href="{{ route('admin.instruments.create') }}" class="btn-primary btn-sm"><x-icon name="plus" class="h-4 w-4" />Tambah instrumen</a>
    </div>

    @if ($instruments->isEmpty())
        <div class="card py-12 text-center">
            <x-icon name="clipboard" class="mx-auto mb-4 h-10 w-10 text-brand" />
            <h2 class="mb-2 text-lg font-bold">Belum ada instrumen</h2>
            <p class="mx-auto mb-5 max-w-md muted">Tambahkan instrumen yang dipakai di protokol, lalu pilih fase T0, T1, atau T2. Peserta melihatnya saat jendela pengukuran dibuka.</p>
            <a href="{{ route('admin.instruments.create') }}" class="btn-primary"><x-icon name="plus" class="h-4 w-4" />Tambah instrumen</a>
        </div>
    @else
        <div class="card overflow-hidden p-0">
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead><tr><th>Kode</th><th>Nama</th><th>Fase</th><th class="text-right">Butir</th><th>Skoring</th><th class="text-right">Respons</th><th>Versi</th><th><span class="sr-only">Aksi</span></th></tr></thead>
                    <tbody>
                        @foreach ($instruments as $i)
                            <tr>
                                <td class="font-semibold">{{ $i->code }}</td>
                                <td class="min-w-48">{{ $i->name }}</td>
                                <td class="whitespace-nowrap">@forelse ($i->phases ?? [] as $ph)<span class="pill-open mr-1">{{ $ph }}</span>@empty<span class="pill-warn">Tidak dipakai</span>@endforelse</td>
                                <td class="text-right num">{{ $i->items_count }}</td>
                                <td>{{ $methods[$i->scoring['method'] ?? 'none'] ?? '—' }}</td>
                                <td class="text-right num">{{ $i->responses_count }}</td>
                                <td class="num">{{ $i->version }}</td>
                                <td class="text-right"><a href="{{ route('admin.instruments.edit', $i) }}" class="btn-ghost btn-sm whitespace-nowrap"><x-icon name="pencil" class="h-4 w-4" />Kelola</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@endsection
