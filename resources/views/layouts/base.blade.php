<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}?v=1">
    <link rel="apple-touch-icon" href="{{ asset('favicon.svg') }}?v=1">
    {{-- Mode terang/malam dipasang sebelum halaman tampil agar tidak berkedip --}}
    <script>try{var t=localStorage.getItem('ritnova-theme');if(t!=='dark'&&t!=='light'){t=matchMedia('(prefers-color-scheme: dark)').matches?'dark':'light';}document.documentElement.dataset.theme=t;}catch(e){document.documentElement.dataset.theme='light';}</script>
    <title>{{ isset($title) ? $title.' · ' : '' }}RITNOVA</title>
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-canvas font-sans text-ink antialiased" data-daypart="{{ \App\Support\Daypart::at() }}">
    <div class="ambient" aria-hidden="true"><span class="ambient-blob ambient-blob-a"></span><span class="ambient-blob ambient-blob-b"></span><span class="ambient-blob ambient-blob-c"></span><span class="ambient-stars"></span></div>
    <div class="tooltip" role="tooltip" hidden></div>
    {{-- Kembali ke atas: muncul setelah halaman digulir; cincinnya menunjukkan seberapa jauh halaman sudah dibaca --}}
    <button type="button" class="to-top" data-to-top aria-label="Kembali ke atas" data-tip="Kembali ke atas" tabindex="-1">
        <svg viewBox="0 0 48 48" class="absolute inset-0 h-full w-full -rotate-90" aria-hidden="true">
            <circle cx="24" cy="24" r="21" fill="none" stroke-width="3" class="stroke-ink/10" />
            <circle cx="24" cy="24" r="21" fill="none" stroke-width="3" stroke-linecap="round" class="stroke-brand" pathLength="100" stroke-dasharray="100" stroke-dashoffset="100" data-to-top-ring />
        </svg>
        <svg viewBox="0 0 24 24" class="relative h-5 w-5" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 19V5M5.5 11.5 12 5l6.5 6.5" /></svg>
    </button>
    @yield('body')
</body>
</html>
