@extends('layouts.admin', ['title' => 'Ekspor dataset'])

@section('content')
    <div class="mb-7 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        @include('admin.partials.page-head', ['heading' => 'Ekspor dataset', 'sub' => 'Data mentah dan hasil kalkulasi dipisah. Semua file CSV untuk '.($cohort?->name ?? 'kohort terpilih').', siap dibuka di Excel, SPSS, atau R.'])
        <a href="{{ route('admin.export.dictionary') }}" class="btn-secondary btn-sm"><x-icon name="file" class="h-4 w-4" />Kamus data</a>
    </div>

    <div class="grid gap-6 xl:grid-cols-[1fr_20rem]">
        <div class="grid gap-4 md:grid-cols-2">
            @foreach ($datasets as $key => [$title, $desc, $fields])
                <article class="card flex flex-col">
                    <div class="mb-2 flex items-start justify-between gap-3">
                        <h2 class="font-bold">{{ $title }}</h2>
                        <span class="pill-muted shrink-0">{{ count($fields) }} kolom</span>
                    </div>
                    <p class="mb-3 text-sm muted">{{ $desc }}</p>
                    <details class="group mb-4 text-sm">
                        <summary class="cursor-pointer list-none font-medium text-brand hover:underline"><span class="group-open:hidden">Lihat kolom</span><span class="hidden group-open:inline">Sembunyikan kolom</span></summary>
                        <dl class="mt-3 space-y-1.5 rounded-xl bg-canvas p-3 ring-1 ring-ink/5">
                            @foreach ($fields as $field => $meaning)
                                <div class="grid grid-cols-[minmax(0,10rem)_1fr] gap-3"><dt class="truncate font-mono text-xs text-brand">{{ $field }}</dt><dd class="text-xs muted">{{ $meaning }}</dd></div>
                            @endforeach
                        </dl>
                    </details>
                    <a href="{{ route('admin.export.download', $key) }}" class="btn-primary btn-sm mt-auto"><x-icon name="download" class="h-4 w-4" />Unduh CSV</a>
                </article>
            @endforeach
        </div>

        <aside class="space-y-6">
            <section class="panel-night p-5">
                <h2 class="mb-3 font-bold">Aturan ekspor</h2>
                <ul class="space-y-2.5 text-sm text-white/75">
                    <li class="flex gap-2.5"><x-icon name="check" class="mt-0.5 h-4 w-4 shrink-0 text-[#7BE0B9]" />Data per hari dan per fase</li>
                    <li class="flex gap-2.5"><x-icon name="check" class="mt-0.5 h-4 w-4 shrink-0 text-[#7BE0B9]" />Data kosong tetap kosong, tidak diisi nol</li>
                    <li class="flex gap-2.5"><x-icon name="check" class="mt-0.5 h-4 w-4 shrink-0 text-[#7BE0B9]" />Waktu lengkap dengan zona waktu (+08:00)</li>
                    <li class="flex gap-2.5"><x-icon name="check" class="mt-0.5 h-4 w-4 shrink-0 text-[#7BE0B9]" />SJL membawa versi rumusnya</li>
                    <li class="flex gap-2.5"><x-icon name="check" class="mt-0.5 h-4 w-4 shrink-0 text-[#7BE0B9]" />Setiap unduhan tercatat di audit log</li>
                </ul>
            </section>

            <section class="card">
                <h2 class="mb-1 font-bold">Target pengingat WhatsApp</h2>
                <p class="mb-4 text-sm muted">Peserta yang belum melengkapi catatan tidur semalam, untuk blast WhatsApp oleh tim.</p>
                <div class="grid gap-2">
                    <a href="{{ route('admin.export.reminders', ['type' => 'diary']) }}" class="btn-secondary btn-sm">Semua kelompok</a>
                    <div class="grid grid-cols-2 gap-2">
                        @foreach (config('ritnova.groups') as $k => $label)
                            <a href="{{ route('admin.export.reminders', ['type' => 'diary', 'group' => $k]) }}" class="btn-ghost btn-sm ring-1 ring-ink/10">{{ $label }}</a>
                        @endforeach
                    </div>
                </div>
            </section>

            <section class="card">
                <h2 class="mb-3 font-bold">Unduhan terakhir</h2>
                <ul class="space-y-2.5 text-sm">
                    @forelse ($recent as $log)
                        <li class="flex items-start justify-between gap-3">
                            <span>{{ $datasets[$log->new_values['dataset'] ?? ''][0] ?? 'Target pengingat' }}<span class="block text-xs muted">{{ $log->user?->username ?? 'sistem' }}</span></span>
                            <span class="shrink-0 text-xs muted num">{{ $log->created_at->translatedFormat('j M, H.i') }}</span>
                        </li>
                    @empty
                        <li class="muted">Belum ada ekspor.</li>
                    @endforelse
                </ul>
            </section>
        </aside>
    </div>
@endsection
