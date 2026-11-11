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
    <svg viewBox="0 0 240 240" class="h-auto w-full" role="img" aria-label="{{ $title }}">
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
            <path d="{{ $freeArc['d'] }}" fill="none" stroke="#F29A6B" stroke-width="14" stroke-linecap="round" class="rhythm-arc rhythm-arc-late" style="--arc-length: {{ $freeArc['length'] }}" />
        @endif
        @if ($workArc)
            <path d="{{ $workArc['d'] }}" fill="none" stroke="#5AA9E6" stroke-width="14" stroke-linecap="round" class="rhythm-arc" style="--arc-length: {{ $workArc['length'] }}" />
        @endif

        {{-- Pergeseran midpoint = social jetlag --}}
        @if ($shift)
            <path d="{{ $shift['d'] }}" fill="none" stroke="{{ $night ? '#BFB4FF' : '#5948B8' }}" stroke-width="2.5" stroke-dasharray="3 4" stroke-linecap="round" />
        @endif
        @if ($workMid)
            <circle cx="{{ $workMid[0] }}" cy="{{ $workMid[1] }}" r="6.5" fill="{{ $night ? '#1E1B3A' : '#fff' }}" stroke="#5AA9E6" stroke-width="3" />
        @endif
        @if ($freeMid)
            <circle cx="{{ $freeMid[0] }}" cy="{{ $freeMid[1] }}" r="6.5" fill="{{ $night ? '#1E1B3A' : '#fff' }}" stroke="#F29A6B" stroke-width="3" />
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
