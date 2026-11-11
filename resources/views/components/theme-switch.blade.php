@props(['onNight' => false])
{{--
    Saklar mode terang/malam.
    Desktop: lampu gantung, tarik talinya. Ponsel: stop kontak, tancap/cabut stekernya.
    Lampu menyala = mode terang. Tampilan mengikuti html[data-theme]; JS (app.js) mengganti atribut itu.
--}}
<div {{ $attributes->merge(['class' => 'flex shrink-0 items-center']) }}>
    {{-- Lampu gantung (md ke atas) --}}
    <div class="relative hidden h-16 w-10 md:block" data-lamp>
        <svg class="lamp-shade absolute top-0 left-[2px] h-[38px] w-9 overflow-visible" viewBox="0 0 36 38" aria-hidden="true">
            <line x1="18" y1="0" x2="18" y2="13" stroke="currentColor" stroke-width="1.5" @class(['text-white/40' => $onNight, 'text-ink/30' => ! $onNight]) />
            <rect x="14.5" y="11.5" width="7" height="6" rx="1.5" @class(['fill-white/50' => $onNight, 'fill-ink/40' => ! $onNight]) />
            <path d="M5 30 C5 21 10 16.5 18 16.5 C26 16.5 31 21 31 30 Z" fill="#5948B8" />
            <path d="M9 27 C9.5 22.5 12.5 19.5 16.5 19" fill="none" stroke="#8474E5" stroke-width="1.5" stroke-linecap="round" />
            <ellipse class="lamp-bulb" cx="18" cy="31" rx="5.5" ry="3.5" />
        </svg>
        <span class="lamp-cone" aria-hidden="true"></span>
        <button type="button" class="lamp-cord absolute top-[27px] left-[27px] flex h-[54px] w-6 -translate-x-1/2 flex-col items-center" data-theme-toggle aria-pressed="true" aria-label="Saklar mode terang dan malam">
            <span @class(['w-px flex-1', 'bg-white/45' => $onNight, 'bg-ink/35' => ! $onNight])></span>
            <span class="h-3.5 w-3 rounded-full bg-[#FFDAC3] shadow-[0_2px_6px_rgb(30_27_58/0.35)] ring-2 ring-[#F29A6B]/60"></span>
        </button>
    </div>

    {{-- Stop kontak (ponsel) --}}
    <button type="button" class="relative h-11 w-11 md:hidden" data-theme-toggle data-socket aria-pressed="true" aria-label="Saklar mode terang dan malam">
        <span @class(['absolute inset-1.5 rounded-[10px] shadow-[0_2px_6px_rgb(30_27_58/0.12)] ring-1', 'bg-white/10 ring-white/15' => $onNight, 'bg-surface ring-ink/10' => ! $onNight])></span>
        <span @class(['absolute top-1/2 left-1/2 h-5 w-5 -translate-x-1/2 -translate-y-1/2 rounded-full shadow-[inset_0_1px_3px_rgb(0_0_0/0.25)]', 'bg-white/10' => $onNight, 'bg-ink/10' => ! $onNight])>
            <span @class(['absolute top-1/2 left-[4px] h-1.5 w-1.5 -translate-y-1/2 rounded-full', 'bg-white/50' => $onNight, 'bg-ink/50' => ! $onNight])></span>
            <span @class(['absolute top-1/2 right-[4px] h-1.5 w-1.5 -translate-y-1/2 rounded-full', 'bg-white/50' => $onNight, 'bg-ink/50' => ! $onNight])></span>
        </span>
        <span class="socket-led absolute top-[9px] right-[9px] h-1 w-1 rounded-full"></span>
        <span class="socket-spark absolute top-1/2 left-1/2 -mt-3.5 -ml-3.5 h-7 w-7 rounded-full bg-[radial-gradient(circle,rgb(255_231_166/0.9),transparent_65%)]" aria-hidden="true"></span>
        <span class="socket-plug absolute top-1/2 left-1/2 -mt-[9px] -ml-[9px] h-[18px] w-[18px]" aria-hidden="true">
            <span class="absolute top-[15px] left-1/2 h-5 w-[3px] -translate-x-1/2 rounded-b-full bg-primary"></span>
            <span class="relative block h-full w-full rounded-full bg-primary shadow-[0_2px_5px_rgb(30_27_58/0.35)] ring-2 ring-[#8474E5]/50"></span>
        </span>
    </button>
</div>
