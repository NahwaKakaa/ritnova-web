@props(['light' => false])
<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-2 font-bold tracking-tight']) }}>
    <svg viewBox="0 0 64 64" class="h-8 w-8 shrink-0" aria-hidden="true">
        <rect width="64" height="64" rx="16" fill="{{ $light ? '#FFFFFF' : '#5948B8' }}" />
        <path d="M28.85 16.04 A18 18 0 1 0 47.96 35.15 A14 14 0 0 1 28.85 16.04 Z" fill="{{ $light ? '#5948B8' : '#FFFFFF' }}" />
        <circle cx="48" cy="15" r="3" fill="#FFDAC3" />
    </svg>
    <span class="{{ $light ? 'text-white' : 'text-ink' }} text-[17px]">RITNOVA</span>
</span>
