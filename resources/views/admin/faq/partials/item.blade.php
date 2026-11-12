{{-- Satu butir FAQ. $i bisa '__i__' untuk template baris baru. --}}
@php $named = $i !== '__i__'; $n = fn ($f) => $named ? "faqs[$i][$f]" : null; @endphp
<div class="rounded-2xl bg-canvas p-4 ring-1 ring-ink/5" data-repeater-item>
    <div class="mb-3 flex items-center gap-2">
        <span class="flex h-7 w-7 items-center justify-center rounded-full bg-lavender text-xs font-bold text-brand num" data-repeater-index>{{ $named ? $i + 1 : '' }}</span>
        <label class="ml-1 inline-flex cursor-pointer items-center gap-2 text-sm">
            <input type="hidden" value="0" data-name="faqs[__i__][is_published]" @if ($named) name="{{ $n('is_published') }}" @endif>
            <input type="checkbox" value="1" class="accent-primary" data-name="faqs[__i__][is_published]" @if ($named) name="{{ $n('is_published') }}" @endif @checked($f['is_published'] ?? true)>
            Tampilkan
        </label>
        <span class="ml-auto flex gap-1">
            <button type="button" class="rounded-full p-1.5 text-ink/50 hover:bg-lavender hover:text-brand" data-repeater-up aria-label="Pindah ke atas" data-tip="Pindah ke atas"><x-icon name="arrow-left" class="h-4 w-4 rotate-90" /></button>
            <button type="button" class="rounded-full p-1.5 text-ink/50 hover:bg-lavender hover:text-brand" data-repeater-down aria-label="Pindah ke bawah" data-tip="Pindah ke bawah"><x-icon name="arrow-right" class="h-4 w-4 rotate-90" /></button>
            <button type="button" class="rounded-full p-1.5 text-ink/50 hover:bg-rose hover:text-rose-deep" data-repeater-remove aria-label="Hapus pertanyaan" data-tip="Hapus"><x-icon name="x" class="h-4 w-4" /></button>
        </span>
    </div>
    <input type="hidden" value="{{ $f['id'] ?? '' }}" data-name="faqs[__i__][id]" @if ($named) name="{{ $n('id') }}" @endif>
    <label class="mb-3 block">
        <span class="label text-xs">Pertanyaan</span>
        <input class="input" maxlength="255" value="{{ $f['question'] ?? '' }}" data-name="faqs[__i__][question]" @if ($named) name="{{ $n('question') }}" @endif required>
    </label>
    <label class="block">
        <span class="label text-xs">Jawaban</span>
        <textarea rows="3" class="input" maxlength="3000" data-name="faqs[__i__][answer]" @if ($named) name="{{ $n('answer') }}" @endif required>{{ $f['answer'] ?? '' }}</textarea>
    </label>
</div>
