@extends('layouts.participant', ['title' => $instrument->name])

@section('content')
    <div class="mx-auto max-w-2xl">
        <a href="{{ route('assessments.index') }}" class="btn-ghost btn-sm -ml-3.5"><x-icon name="arrow-left" class="h-4 w-4" />Pengukuran</a>
        <p class="mt-4 mb-1 font-semibold text-secondary">{{ $phase }} · {{ config('ritnova.phase_labels')[$phase] }}</p>
        <h1 class="page-title mb-2">{{ $instrument->name }}</h1>
        @if ($instrument->description)<p class="mb-8 muted">{{ $instrument->description }}</p>@endif

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

                        @if (in_array($item->type, ['likert', 'choice']))
                            <div @class(['grid gap-2', $cols.' text-center text-xs' => $item->type === 'likert', 'text-sm' => $item->type === 'choice'])>
                                @foreach ($choices as $value => $label)
                                    <label class="flex cursor-pointer items-center justify-center gap-2 rounded-2xl border border-night/10 bg-white px-2 py-3 leading-tight transition has-checked:border-primary has-checked:bg-lavender has-checked:font-semibold has-checked:text-primary">
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
