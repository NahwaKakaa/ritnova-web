@props(['name', 'label', 'value' => null, 'hint' => null])
@php
    $value = old($name, $value);
    [$h, $m] = $value ? array_map('intval', explode(':', $value)) : [null, null];
    $minutes = range(0, 55, 5);
    if ($m !== null && ! in_array($m, $minutes, true)) {
        $minutes[] = $m;
        sort($minutes);
    }
@endphp
<fieldset data-time-select {{ $attributes }}>
    <legend class="label">{{ $label }}</legend>
    <input type="hidden" id="{{ $name }}" name="{{ $name }}" value="{{ $value }}">
    <div class="flex items-center gap-2">
        <select class="input w-auto flex-1 text-center text-lg font-bold num" data-part="hour" aria-label="{{ $label }}: jam" required>
            <option value="" @selected($h === null)>Jam</option>
            @foreach (range(0, 23) as $opt)
                <option value="{{ $opt }}" @selected($h === $opt)>{{ str_pad($opt, 2, '0', STR_PAD_LEFT) }}</option>
            @endforeach
        </select>
        <span class="text-lg font-bold" aria-hidden="true">:</span>
        <select class="input w-auto flex-1 text-center text-lg font-bold num" data-part="minute" aria-label="{{ $label }}: menit" required>
            <option value="" @selected($m === null)>Menit</option>
            @foreach ($minutes as $opt)
                <option value="{{ $opt }}" @selected($m === $opt)>{{ str_pad($opt, 2, '0', STR_PAD_LEFT) }}</option>
            @endforeach
        </select>
    </div>
    @if ($hint) <p class="mt-1.5 text-xs muted">{{ $hint }}</p> @endif
    @error($name) <p class="mt-1.5 flex items-center gap-1.5 text-sm text-rose-deep"><x-icon name="alert" class="h-4 w-4" />{{ $message }}</p> @enderror
</fieldset>
