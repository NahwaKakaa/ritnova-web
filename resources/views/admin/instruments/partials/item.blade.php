{{-- Satu butir instrumen. $i = indeks atau '__i__' untuk template. $locked = hanya baca. --}}
@php
    $named = $i !== '__i__';
    $n = fn ($f) => $named ? "items[$i][$f]" : null;
    $type = $it['type'] ?? 'likert';
@endphp
<div class="rounded-2xl bg-canvas p-4 ring-1 ring-ink/5" data-repeater-item data-instrument-item>
    <div class="mb-3 flex items-center gap-2">
        <span class="flex h-7 w-7 items-center justify-center rounded-full bg-lavender text-xs font-bold text-brand num" data-repeater-index>{{ $named ? $i + 1 : '' }}</span>
        <label class="sr-only" for="type-{{ $i }}">Jenis butir</label>
        <select id="type-{{ $i }}" class="input w-auto px-3 py-1.5 text-sm" data-item-type data-name="items[__i__][type]" @if ($named) name="{{ $n('type') }}" @endif @disabled($locked)>
            @foreach (\App\Models\InstrumentItem::TYPES as $k => $label)<option value="{{ $k }}" @selected($type === $k)>{{ $label }}</option>@endforeach
        </select>
        @unless ($locked)
            <span class="ml-auto flex gap-1">
                <button type="button" class="rounded-full p-1.5 text-ink/50 hover:bg-lavender hover:text-brand" data-repeater-up aria-label="Pindah ke atas" data-tip="Pindah ke atas"><x-icon name="arrow-left" class="h-4 w-4 rotate-90" /></button>
                <button type="button" class="rounded-full p-1.5 text-ink/50 hover:bg-lavender hover:text-brand" data-repeater-down aria-label="Pindah ke bawah" data-tip="Pindah ke bawah"><x-icon name="arrow-right" class="h-4 w-4 rotate-90" /></button>
                <button type="button" class="rounded-full p-1.5 text-ink/50 hover:bg-rose hover:text-rose-deep" data-repeater-remove aria-label="Hapus butir" data-tip="Hapus"><x-icon name="x" class="h-4 w-4" /></button>
            </span>
        @endunless
    </div>
    <label class="mb-3 block">
        <span class="label text-xs">Teks butir (sesuai instrumen asli)</span>
        <textarea rows="2" class="input" maxlength="1000" data-name="items[__i__][text]" @if ($named) name="{{ $n('text') }}" @endif @disabled($locked) required>{{ $it['text'] ?? '' }}</textarea>
    </label>
    <label class="mb-3 block" data-show-for="likert choice" @if (! in_array($type, ['likert', 'choice'])) hidden @endif>
        <span class="label text-xs">Pilihan jawaban, satu per baris: <code class="text-brand">nilai = label</code></span>
        <textarea rows="4" class="input font-mono text-sm" maxlength="2000" placeholder="1 = Sangat tidak setuju&#10;2 = Tidak setuju&#10;3 = Netral&#10;4 = Setuju&#10;5 = Sangat setuju" data-name="items[__i__][choices]" @if ($named) name="{{ $n('choices') }}" @endif @disabled($locked)>{{ $it['choices'] ?? '' }}</textarea>
    </label>
    <div class="mb-3 grid grid-cols-2 gap-3 sm:w-72" data-show-for="number" @if ($type !== 'number') hidden @endif>
        <label class="block"><span class="label text-xs">Minimum</span><input type="number" step="any" class="input py-2 text-sm" value="{{ $it['min'] ?? '' }}" data-name="items[__i__][min]" @if ($named) name="{{ $n('min') }}" @endif @disabled($locked)></label>
        <label class="block"><span class="label text-xs">Maksimum</span><input type="number" step="any" class="input py-2 text-sm" value="{{ $it['max'] ?? '' }}" data-name="items[__i__][max]" @if ($named) name="{{ $n('max') }}" @endif @disabled($locked)></label>
    </div>
    <div class="flex flex-wrap gap-x-5 gap-y-2 text-sm">
        <label class="inline-flex items-center gap-2">
            <input type="hidden" value="0" data-name="items[__i__][required]" @if ($named) name="{{ $n('required') }}" @endif @disabled($locked)>
            <input type="checkbox" value="1" class="accent-primary" data-name="items[__i__][required]" @if ($named) name="{{ $n('required') }}" @endif @checked($it['required'] ?? true) @disabled($locked)>Wajib diisi
        </label>
        <label class="inline-flex items-center gap-2" data-show-for="likert choice number" @if (! in_array($type, ['likert', 'choice', 'number'])) hidden @endif>
            <input type="hidden" value="0" data-name="items[__i__][reverse]" @if ($named) name="{{ $n('reverse') }}" @endif @disabled($locked)>
            <input type="checkbox" value="1" class="accent-primary" data-name="items[__i__][reverse]" @if ($named) name="{{ $n('reverse') }}" @endif @checked($it['reverse'] ?? false) @disabled($locked)>Skor terbalik (reverse)
        </label>
    </div>
    @foreach (['choices', 'max', 'reverse'] as $f)
        @if ($named) @error("items.$i.$f")<p class="mt-2 text-sm text-rose-deep">{{ $message }}</p>@enderror @endif
    @endforeach
</div>
