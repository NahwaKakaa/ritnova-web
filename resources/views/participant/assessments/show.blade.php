@extends('layouts.participant', ['title' => $instrument->name])

@section('content')
    <div class="mx-auto max-w-2xl">
        <a href="{{ route('assessments.index') }}" class="btn-ghost btn-sm -ml-3.5"><x-icon name="arrow-left" class="h-4 w-4" />Pengukuran</a>
        <p class="mt-4 mb-1 font-semibold text-secondary">{{ $phase }} · {{ config('ritnova.phase_labels')[$phase] }}</p>
        <h1 class="page-title mb-2">{{ $instrument->name }}</h1>
        @if ($instrument->description)<p class="mb-8 whitespace-pre-line muted">{{ $instrument->description }}</p>@endif

        @if ($instrument->items->isEmpty())
            <div class="card muted">Butir instrumen belum diisi oleh tim peneliti.</div>
        @else
            <form method="POST" action="{{ route('assessments.store', [$phase, $instrument]) }}" class="space-y-4">
                @csrf
                @foreach ($instrument->items as $item)
                    @php
                        $name = 'answers['.$item->id.']';
                        $old = old('answers.'.$item->id);
                        $opts = $item->options ?? [];
                        $choices = isset($opts['values'])
                            ? array_combine($opts['values'], $opts['labels'] ?? $opts['values'])
                            : $opts;
                        $cols = ['grid-cols-2', 'grid-cols-2', 'grid-cols-3', 'grid-cols-4', 'grid-cols-5'][min(max(count($choices), 1), 5) - 1];
                    @endphp
                    <fieldset class="card sm:p-6">
                        <legend class="sr-only">Butir {{ $loop->iteration }}</legend>
                        <p class="mb-3 font-medium">{{ $loop->iteration }}. {{ $item->text }} @unless ($item->required)<span class="text-xs muted">(opsional)</span>@endunless</p>

                        @if ($item->type === 'likert' && count($choices) > 7)
                            {{-- Skala angka panjang (mis. 0–10): satu baris tombol angka, keterangan hanya di titik berlabel. --}}
                            @php
                                $anchors = collect($choices)->filter(fn ($label, $value) => (string) $label !== (string) $value)
                                    ->map(fn ($label, $value) => trim(preg_replace('/^'.preg_quote((string) $value, '/').'\s*=\s*/', '', $label)));
                            @endphp
                            <div class="grid gap-1 sm:gap-1.5" style="grid-template-columns: repeat({{ count($choices) }}, minmax(0, 1fr))">
                                @foreach ($choices as $value => $label)
                                    <label class="flex h-11 cursor-pointer items-center justify-center rounded-xl border border-ink/10 bg-surface text-sm font-semibold num transition has-checked:border-primary has-checked:bg-primary has-checked:text-white has-focus-visible:ring-2 has-focus-visible:ring-primary/50" title="{{ $label }}">
                                        <input type="radio" name="{{ $name }}" value="{{ $value }}" class="sr-only" @checked((string) $old === (string) $value) @required($item->required) aria-label="{{ $label }}">
                                        {{ $value }}
                                    </label>
                                @endforeach
                            </div>
                            @if ($anchors->isNotEmpty())
                                <div class="mt-2 flex justify-between gap-2 text-xs leading-tight muted">
                                    @foreach ($anchors as $value => $text)
                                        <span @class(['max-w-[30%]', 'text-center' => ! $loop->first && ! $loop->last, 'text-right' => $loop->last && ! $loop->first])><span class="font-semibold num">{{ $value }}</span> {{ $text }}</span>
                                    @endforeach
                                </div>
                            @endif
                        @elseif (in_array($item->type, ['likert', 'choice']))
                            <div @class(['grid gap-2', $cols.' text-center text-xs' => $item->type === 'likert', 'text-sm' => $item->type === 'choice'])>
                                @foreach ($choices as $value => $label)
                                    <label class="flex cursor-pointer items-center justify-center gap-2 rounded-2xl border border-ink/10 bg-surface px-2 py-3 leading-tight transition has-checked:border-primary has-checked:bg-lavender has-checked:font-semibold has-checked:text-brand">
                                        <input type="radio" name="{{ $name }}" value="{{ $value }}" @checked((string) $old === (string) $value) @class(['sr-only' => $item->type === 'likert']) @required($item->required)>
                                        {{ $label }}
                                    </label>
                                @endforeach
                            </div>
                        @elseif ($item->type === 'number')
                            <input class="input" type="number" name="{{ $name }}" value="{{ $old }}" min="{{ $opts['min'] ?? '' }}" max="{{ $opts['max'] ?? '' }}" step="any" @required($item->required)>
                        @elseif ($item->type === 'time')
                            <input class="input" type="time" name="{{ $name }}" value="{{ $old }}" @required($item->required)>
                        @else
                            <textarea class="input" name="{{ $name }}" rows="2" maxlength="2000" @required($item->required)>{{ $old }}</textarea>
                        @endif

                        @error('answers.'.$item->id) <p class="mt-1 text-sm text-rose-deep">{{ $message }}</p> @enderror
                    </fieldset>
                @endforeach

                <button type="submit" class="btn-primary w-full py-3.5">Kirim jawaban</button>
                <p class="text-center text-xs muted">Jawaban tidak dapat diubah setelah dikirim.</p>
            </form>
        @endif
    </div>
@endsection
