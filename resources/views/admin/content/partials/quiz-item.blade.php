{{-- Satu butir cek pemahaman. $i bisa '__i__' untuk template baris baru. --}}
@php $named = $i !== '__i__'; @endphp
<div class="rounded-2xl bg-canvas p-4 ring-1 ring-ink/5" data-repeater-item>
    <div class="mb-3 flex items-center justify-between">
        <span class="text-sm font-semibold">Butir <span data-repeater-index>{{ $named ? $i + 1 : '' }}</span></span>
        <button type="button" class="rounded-full p-1.5 text-ink/50 hover:bg-rose hover:text-rose-deep" data-repeater-remove aria-label="Hapus butir"><x-icon name="x" class="h-4 w-4" /></button>
    </div>
    <div class="grid gap-3 lg:grid-cols-[1fr_1fr_8rem]">
        <label class="block">
            <span class="label text-xs">Pertanyaan</span>
            <textarea rows="3" class="input text-sm" data-name="quiz[__i__][question]" @if ($named) name="quiz[{{ $i }}][question]" @endif required>{{ $q['question'] }}</textarea>
        </label>
        <label class="block">
            <span class="label text-xs">Pilihan (satu per baris)</span>
            <textarea rows="3" class="input text-sm" data-name="quiz[__i__][options]" @if ($named) name="quiz[{{ $i }}][options]" @endif required>{{ $q['options'] }}</textarea>
        </label>
        <label class="block">
            <span class="label text-xs">Jawaban benar</span>
            <input type="number" min="1" max="8" class="input text-sm" placeholder="No." data-name="quiz[__i__][correct]" @if ($named) name="quiz[{{ $i }}][correct]" @endif value="{{ $q['correct'] }}">
        </label>
    </div>
</div>
