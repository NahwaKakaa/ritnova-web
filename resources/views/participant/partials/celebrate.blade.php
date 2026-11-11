{{-- Perayaan singkat setelah sesi selesai. Tanpa JS, tombol tutup memuat ulang halaman (flash sudah hilang). --}}
@php
    $n = (int) $celebrate['number'];
    $done = (int) $celebrate['done'];
    $sparks = [];
    for ($k = 0; $k < 12; $k++) {
        $a = deg2rad($k * 30 + 8);
        $dist = 110 + ($k % 3) * 34;
        $sparks[] = ['dx' => round(cos($a) * $dist), 'dy' => round(sin($a) * $dist), 'delay' => ($k % 4) * 60, 'color' => ['#BFB4FF', '#FFDAC3', '#BCE4FF'][$k % 3]];
    }
@endphp

<div class="fixed inset-0 z-50 flex items-center justify-center bg-night/55 p-5 backdrop-blur-sm" role="dialog" aria-modal="true" aria-labelledby="celebrate-title" data-celebrate>
    @foreach ($sparks as $s)
        <span class="celebrate-star h-2.5 w-2.5 rounded-full" style="--dx: {{ $s['dx'] }}px; --dy: {{ $s['dy'] }}px; background: {{ $s['color'] }}; animation-delay: {{ $s['delay'] }}ms" aria-hidden="true"></span>
    @endforeach

    <div class="celebrate-card panel-night w-full max-w-sm p-8 text-center">
        <div class="mx-auto mb-5 flex h-20 w-20 items-center justify-center rounded-full bg-white/10 ring-1 ring-white/15">
            <svg viewBox="0 0 40 40" class="h-12 w-12" aria-hidden="true">
                <circle cx="20" cy="20" r="16" fill="#2B2752" />
                <path d="{{ \App\Support\MoonPhase::path($n / 12) }}" fill="#EBE7FF" />
            </svg>
        </div>
        <p class="mb-1 text-sm font-semibold text-[#BFB4FF] num">Sesi {{ str_pad((string) $n, 2, '0', STR_PAD_LEFT) }} selesai</p>
        <h2 id="celebrate-title" class="mb-3 text-2xl leading-tight font-bold">{{ $celebrate['title'] }}</h2>
        <p class="mb-6 text-white/65">
            @if ($done >= 12)
                Bulanmu sudah purnama. Semua 12 sesi selesai.
            @else
                Bulanmu bertambah terang: {{ $done }} dari 12 sesi selesai.
            @endif
        </p>
        <a href="{{ url()->current() }}" class="btn-primary w-full" data-celebrate-close autofocus>Lanjutkan</a>
    </div>
</div>
