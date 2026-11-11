{{--
    Simulator SJL interaktif. Slider hanya mengubah input; hasil dihitung server
    (route sjl.simulate) dan jam ritme dirender ulang dari server.
    Parameter: $theme (light|night), $compact (bool)
--}}
@php
    $theme ??= 'light';
    $defaults = ['work_onset' => 1410, 'work_offset' => 390, 'free_onset' => 90, 'free_offset' => 570];
    $sim = app(\App\Services\SjlSimulator::class)->simulate(array_map(fn ($m) => \App\Services\SjlSimulator::time($m), $defaults));
    $fmt = fn ($m) => str_replace(':', '.', \App\Services\SjlSimulator::time($m));
    $sliders = [
        'work' => ['Hari kuliah', 'bg-sky-deep', 'accent-sky-deep'],
        'free' => ['Hari bebas', 'bg-peach-deep', 'accent-peach-deep'],
    ];
@endphp
<div data-sjl-sim data-url="{{ route('sjl.simulate') }}" data-theme="{{ $theme }}" class="grid items-center gap-8 md:grid-cols-[minmax(0,17rem)_1fr]">
    <div class="mx-auto w-full max-w-[17rem]" data-sim-dial aria-live="polite">
        <x-rhythm-dial :work="$sim['work']" :free="$sim['free']" :value="$sim['result']->sjlLabel()" :theme="$theme" />
    </div>

    <div>
        <p @class(['mb-1 text-sm', 'text-white/60' => $theme === 'night', 'muted' => $theme !== 'night'])>Selisih midpoint</p>
        <p class="mb-6 text-3xl font-bold num {{ $theme === 'night' ? 'text-white' : 'text-primary' }}" data-sim-result>{{ \App\Services\SjlSimulator::human($sim['result']->sjlMinutes) }}</p>

        <div class="space-y-6">
            @foreach ($sliders as $key => [$label, $dot, $accent])
                <fieldset>
                    <legend class="mb-3 flex items-center gap-2 font-semibold"><span class="h-2.5 w-2.5 rounded-full {{ $dot }}"></span>{{ $label }}</legend>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <label class="block">
                            <span class="mb-1 flex justify-between text-sm"><span class="{{ $theme === 'night' ? 'text-white/60' : 'muted' }}">Mulai tidur</span><b class="num" data-sim-label="{{ $key }}_onset">{{ $fmt($defaults[$key.'_onset']) }}</b></span>
                            {{-- Jam tidur: 20.00 sampai 05.00 (nilai menit dihitung dari 00.00, melewati tengah malam) --}}
                            <input type="range" class="w-full {{ $accent }}" min="1200" max="1740" step="15" value="{{ $defaults[$key.'_onset'] < 720 ? $defaults[$key.'_onset'] + 1440 : $defaults[$key.'_onset'] }}" data-sim-input="{{ $key }}_onset" aria-label="Jam mulai tidur {{ strtolower($label) }}">
                        </label>
                        <label class="block">
                            <span class="mb-1 flex justify-between text-sm"><span class="{{ $theme === 'night' ? 'text-white/60' : 'muted' }}">Bangun</span><b class="num" data-sim-label="{{ $key }}_offset">{{ $fmt($defaults[$key.'_offset']) }}</b></span>
                            <input type="range" class="w-full {{ $accent }}" min="240" max="780" step="15" value="{{ $defaults[$key.'_offset'] }}" data-sim-input="{{ $key }}_offset" aria-label="Jam bangun {{ strtolower($label) }}">
                        </label>
                    </div>
                </fieldset>
            @endforeach
        </div>
        <noscript><p class="mt-4 text-sm">Aktifkan JavaScript untuk mencoba simulator.</p></noscript>
    </div>
</div>
