@extends('layouts.admin', ['title' => $instrument->exists ? 'Instrumen '.$instrument->code : 'Instrumen baru'])

@php
    $toForm = function ($item) {
        $o = $item->options ?? [];
        $choices = isset($o['values'])
            ? collect($o['values'])->map(fn ($v, $k) => ($o['labels'][$k] ?? $v) === (string) $v ? (string) $v : $v.' = '.$o['labels'][$k])->implode("\n")
            : '';

        return ['type' => $item->type, 'text' => $item->text, 'choices' => $choices, 'min' => $o['min'] ?? null, 'max' => $o['max'] ?? null,
            'required' => $item->required, 'reverse' => $item->reverse_scored];
    };
    $items = old('items', $instrument->exists ? $instrument->items->map($toForm)->all() : [['type' => 'likert', 'required' => true]]);
    $phases = old('phases', $instrument->phases ?? []);
    $method = old('scoring_method', $instrument->scoring['method'] ?? 'none');
    $scoringItems = old('scoring_items', implode(', ', $instrument->scoring['items'] ?? []));
@endphp

@section('content')
    <a href="{{ route('admin.instruments.index') }}" class="mb-5 inline-flex items-center gap-2 text-sm font-medium text-brand hover:underline"><x-icon name="arrow-left" class="h-4 w-4" />Semua instrumen</a>

    <div class="mb-7">
        @include('admin.partials.page-head', [
            'heading' => $instrument->exists ? $instrument->code.': '.$instrument->name : 'Instrumen baru',
            'sub' => $instrument->exists ? 'Versi '.$instrument->version.'. '.$responses.' respons peserta sudah masuk.' : 'Isi persis seperti instrumen yang sudah tervalidasi.',
        ])
    </div>

    @error('delete')<div class="mb-6 rounded-[var(--radius-control)] bg-peach/60 px-4 py-3 text-sm text-warn-ink" role="alert">{{ $message }}</div>@enderror

    @if ($locked)
        <div class="mb-6 flex items-start gap-3 rounded-[var(--radius-control)] bg-lavender px-4 py-3 text-sm" role="note">
            <x-icon name="lock" class="mt-0.5 h-4 w-4 shrink-0 text-brand" />
            <p>Butir dikunci karena sudah ada respons peserta, supaya jawaban yang masuk tetap bermakna. Nama, fase, dan aturan skoring masih bisa diubah. Untuk mengubah butir, buat salinan instrumen.</p>
        </div>
    @endif

    <form method="POST" action="{{ $instrument->exists ? route('admin.instruments.update', $instrument) : route('admin.instruments.store') }}" class="grid gap-6 xl:grid-cols-[1fr_20rem]">
        @csrf
        @if ($instrument->exists) @method('PUT') @endif

        <div class="space-y-6">
            <section class="card space-y-4">
                <h2 class="font-bold">Identitas</h2>
                <div class="grid gap-4 sm:grid-cols-[10rem_1fr]">
                    <div>
                        <label class="label" for="code">Kode</label>
                        <input id="code" name="code" value="{{ old('code', $instrument->code) }}" class="input uppercase" maxlength="20" placeholder="PSQI" required>
                    </div>
                    <div>
                        <label class="label" for="name">Nama instrumen</label>
                        <input id="name" name="name" value="{{ old('name', $instrument->name) }}" class="input" maxlength="160" required>
                    </div>
                </div>
                <div>
                    <label class="label" for="description">Petunjuk untuk peserta</label>
                    <textarea id="description" name="description" rows="2" class="input" maxlength="2000">{{ old('description', $instrument->description) }}</textarea>
                </div>
                <fieldset>
                    <legend class="label">Dipakai pada fase</legend>
                    <div class="grid grid-cols-3 gap-2 sm:w-96">
                        @foreach (config('ritnova.phases') as $ph)
                            <label class="choice"><input type="checkbox" name="phases[]" value="{{ $ph }}" class="accent-primary" @checked(in_array($ph, $phases))>{{ $ph }}</label>
                        @endforeach
                    </div>
                </fieldset>
            </section>

            <section class="card" data-repeater>
                <div class="mb-4 flex items-start justify-between gap-4">
                    <div>
                        <h2 class="font-bold">Butir</h2>
                        <p class="text-sm muted">Urutan di sini sama dengan urutan yang dilihat peserta.</p>
                    </div>
                    @unless ($locked)<button type="button" class="btn-secondary btn-sm shrink-0" data-repeater-add><x-icon name="plus" class="h-4 w-4" />Tambah butir</button>@endunless
                </div>
                @error('items')<p class="mb-3 text-sm text-rose-deep">{{ $message }}</p>@enderror
                <div class="space-y-4" data-repeater-list>
                    @foreach ($items as $i => $it)
                        @include('admin.instruments.partials.item', ['i' => $i, 'it' => $it, 'locked' => $locked])
                    @endforeach
                </div>
                @unless ($locked)
                    <template>@include('admin.instruments.partials.item', ['i' => '__i__', 'it' => ['type' => 'likert', 'required' => true], 'locked' => false])</template>
                @endunless
            </section>
        </div>

        <aside class="space-y-4 xl:sticky xl:top-24 xl:self-start">
            <section class="card space-y-4">
                <h2 class="font-bold">Skoring</h2>
                <fieldset>
                    <legend class="label">Cara menghitung skor</legend>
                    <div class="grid gap-2">
                        @foreach (['none' => 'Tanpa skor (hanya jawaban mentah)', 'sum' => 'Jumlah nilai butir', 'mean' => 'Rata-rata nilai butir'] as $k => $label)
                            <label class="choice text-sm"><input type="radio" name="scoring_method" value="{{ $k }}" class="accent-primary" @checked($method === $k)>{{ $label }}</label>
                        @endforeach
                    </div>
                </fieldset>
                <div>
                    <label class="label" for="scoring_items">Butir yang dihitung</label>
                    <input id="scoring_items" name="scoring_items" value="{{ $scoringItems }}" class="input py-2.5" placeholder="Kosong = semua butir angka/skala">
                    <p class="mt-1.5 text-xs muted">Nomor butir dipisah koma, misalnya 1, 2, 5. Butir "skor terbalik" dibalik terhadap rentang nilainya sendiri. Bila ada jawaban yang dihitung kosong, skor = kosong (bukan 0).</p>
                    @error('scoring_items')<p class="mt-1 text-sm text-rose-deep">{{ $message }}</p>@enderror
                </div>
                <button class="btn-primary w-full">{{ $instrument->exists ? 'Simpan instrumen' : 'Buat instrumen' }}</button>
            </section>
        </aside>
    </form>

    @if ($instrument->exists)
        <section class="mt-6 grid gap-4 md:grid-cols-3" aria-label="Tindakan lain">
            <form method="POST" action="{{ route('admin.instruments.recompute', $instrument) }}" class="card flex flex-col" data-confirm-title="Hitung ulang skor?" data-confirm="Skor {{ $responses }} respons akan dihitung ulang dengan aturan skoring versi {{ $instrument->version }}. Jawaban mentah tidak berubah." data-confirm-ok="Hitung ulang">
                @csrf
                <h2 class="mb-1 font-bold">Hitung ulang skor</h2>
                <p class="mb-4 text-sm muted">Terapkan aturan skoring saat ini ke semua respons yang sudah masuk. Tercatat di audit log.</p>
                <button class="btn-secondary btn-sm mt-auto" @disabled(! $responses)>Hitung ulang {{ $responses }} respons</button>
            </form>
            <form method="POST" action="{{ route('admin.instruments.duplicate', $instrument) }}" class="card flex flex-col">
                @csrf
                <h2 class="mb-1 font-bold">Buat salinan</h2>
                <p class="mb-4 text-sm muted">Salin semua butir ke instrumen baru yang bisa diedit bebas, misalnya untuk versi revisi.</p>
                <button class="btn-secondary btn-sm mt-auto">Salin instrumen</button>
            </form>
            <form method="POST" action="{{ route('admin.instruments.destroy', $instrument) }}" class="card flex flex-col" data-confirm-tone="danger" data-confirm-title="Hapus {{ $instrument->code }}?" data-confirm="Instrumen dan semua butirnya dihapus permanen." data-confirm-ok="Hapus permanen">
                @csrf @method('DELETE')
                <h2 class="mb-1 font-bold">Hapus instrumen</h2>
                <p class="mb-4 text-sm muted">{{ $responses ? 'Tidak bisa dihapus karena sudah ada respons. Kosongkan fasenya bila tidak dipakai.' : 'Hanya bila belum ada peserta yang mengisi.' }}</p>
                <button class="btn btn-sm mt-auto bg-rose text-rose-deep hover:brightness-95" @disabled($responses)>Hapus</button>
            </form>
        </section>
    @endif
@endsection
