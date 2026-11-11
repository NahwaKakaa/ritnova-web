@php
    $night = $theme === 'night';
    $track = $night ? 'rgb(255 255 255 / 0.10)' : '#EBE7FF';
    $tick = $night ? 'rgb(255 255 255 / 0.35)' : 'rgb(30 27 58 / 0.25)';
    $text = $night ? 'rgb(255 255 255 / 0.55)' : 'rgb(30 27 58 / 0.45)';
    $title = 'Jam ritme tidur 24 jam'
        .($work ? '. Hari kuliah '.\App\View\Components\RhythmDial::clock($work['onset']).'–'.\App\View\Components\RhythmDial::clock($work['offset']) : '')
        .($free ? '. Hari bebas '.\App\View\Components\RhythmDial::clock($free['onset']).'–'.\App\View\Components\RhythmDial::clock($free['offset']) : '')
        .($value ? '. Selisih midpoint '.$value : '');
@endphp
<figure {{ $attributes->merge(['class' => 'relative']) }}>
    <svg viewBox="-12 -12 264 264" class="h-auto w-full" role="img" aria-label="{{ $title }}">
        {{-- Lingkar jam --}}
        <circle cx="120" cy="120" r="98" fill="none" stroke="{{ $track }}" stroke-width="14" />
        <circle cx="120" cy="120" r="76" fill="none" stroke="{{ $track }}" stroke-width="14" />
        @foreach ($ticks as $t)
            <line x1="{{ $t['inner'][0] }}" y1="{{ $t['inner'][1] }}" x2="{{ $t['outer'][0] }}" y2="{{ $t['outer'][1] }}" stroke="{{ $tick }}" stroke-width="{{ $t['h'] % 6 === 0 ? 1.6 : 1 }}" stroke-linecap="round" />
            @if ($t['h'] % 6 === 0)
                <text x="{{ $t['label'][0] }}" y="{{ $t['label'][1] }}" text-anchor="middle" dominant-baseline="central" font-size="9.5" font-weight="600" fill="{{ $text }}">{{ sprintf('%02d', $t['h']) }}</text>
            @endif
        @endforeach

        {{-- Busur tidur --}}
        @if ($freeArc)
            <path d="{{ $freeArc['d'] }}" fill="none" stroke="#F29A6B" stroke-width="14" stroke-linecap="round" class="rhythm-arc rhythm-arc-late cursor-help" style="--arc-length: {{ $freeArc['length'] }}"
                  data-tip="Hari bebas: tidur {{ \App\View\Components\RhythmDial::clock($free['onset']) }}, bangun {{ \App\View\Components\RhythmDial::clock($free['offset']) }}" />
        @endif
        @if ($workArc)
            <path d="{{ $workArc['d'] }}" fill="none" stroke="#5AA9E6" stroke-width="14" stroke-linecap="round" class="rhythm-arc cursor-help" style="--arc-length: {{ $workArc['length'] }}"
                  data-tip="Hari kuliah: tidur {{ \App\View\Components\RhythmDial::clock($work['onset']) }}, bangun {{ \App\View\Components\RhythmDial::clock($work['offset']) }}" />
        @endif

        {{-- Pergeseran midpoint = social jetlag --}}
        @if ($shift)
            <path d="{{ $shift['d'] }}" fill="none" stroke="{{ $night ? '#BFB4FF' : '#5948B8' }}" stroke-width="2.5" stroke-dasharray="3 4" stroke-linecap="round" />
        @endif
        @if ($workMid)
            <circle cx="{{ $workMid[0] }}" cy="{{ $workMid[1] }}" r="6.5" fill="{{ $night ? '#1E1B3A' : '#fff' }}" stroke="#5AA9E6" stroke-width="3" class="cursor-help" data-tip="Midpoint hari kuliah {{ \App\View\Components\RhythmDial::clock($work['mid']) }}" />
        @endif
        @if ($freeMid)
            <circle cx="{{ $freeMid[0] }}" cy="{{ $freeMid[1] }}" r="6.5" fill="{{ $night ? '#1E1B3A' : '#fff' }}" stroke="#F29A6B" stroke-width="3" class="cursor-help" data-tip="Midpoint hari bebas {{ \App\View\Components\RhythmDial::clock($free['mid']) }}" />
        @endif

        {{-- Jarum waktu sekarang --}}
        @if ($now)
            <g data-now-hand transform="rotate({{ $nowAngle }} 120 120)" class="cursor-help" data-tip="Sekarang {{ now(config('ritnova.timezone'))->format('H.i') }} WITA">
                <line x1="120" y1="6" x2="120" y2="30" stroke="{{ $night ? '#FFFFFF' : '#5948B8' }}" stroke-width="2.5" stroke-linecap="round" />
                <circle cx="120" cy="3" r="4" fill="{{ $night ? '#FFDAC3' : '#5948B8' }}" />
            </g>
        @endif

        {{-- Nilai tengah --}}
        @if ($value)
            <text x="120" y="114" text-anchor="middle" font-size="22" font-weight="700" fill="{{ $night ? '#fff' : '#1E1B3A' }}" style="font-variant-numeric: tabular-nums">{{ $value }}</text>
            <text x="120" y="134" text-anchor="middle" font-size="9.5" fill="{{ $text }}">{{ $caption }}</text>
        @endif
    </svg>
    @if ($work || $free)
        <figcaption class="mt-3 flex flex-wrap justify-center gap-x-5 gap-y-1 text-xs {{ $night ? 'text-white/70' : 'muted' }}">
            @if ($work)
                <span class="inline-flex items-center gap-1.5 num"><span class="h-2.5 w-2.5 rounded-full bg-sky-deep"></span>Hari kuliah {{ \App\View\Components\RhythmDial::clock($work['onset']) }}–{{ \App\View\Components\RhythmDial::clock($work['offset']) }}</span>
            @endif
            @if ($free)
                <span class="inline-flex items-center gap-1.5 num"><span class="h-2.5 w-2.5 rounded-full bg-peach-deep"></span>Hari bebas {{ \App\View\Components\RhythmDial::clock($free['onset']) }}–{{ \App\View\Components\RhythmDial::clock($free['offset']) }}</span>
            @endif
        </figcaption>
    @endif
</figure>
