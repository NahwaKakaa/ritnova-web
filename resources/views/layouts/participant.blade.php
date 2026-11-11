@extends('layouts.base')

@php
    $user = auth()->user();
    $intervention = $user->isIntervention();
    $unread = $user->unreadNotifications()->count();

    // Navigasi utama (Opsi A): menu intervensi disembunyikan untuk kelompok pembanding.
    $primary = array_values(array_filter([
        ['route' => 'dashboard', 'active' => 'dashboard', 'label' => 'Beranda', 'short' => 'Beranda', 'icon' => '⌂'],
        $intervention ? ['route' => 'learning.index', 'active' => 'learning.*', 'label' => 'Belajar', 'short' => 'Belajar', 'icon' => '▤'] : null,
        ['route' => 'diary.index', 'active' => 'diary.*', 'label' => 'Sleep Tracker', 'short' => 'Tracker', 'icon' => '☽'],
        $intervention ? ['route' => 'sjl', 'active' => 'sjl*', 'label' => 'SJL Check', 'short' => 'SJL', 'icon' => '◷'] : null,
        $intervention ? ['route' => 'progress', 'active' => 'progress', 'label' => 'Progres', 'short' => null, 'icon' => null] : null,
        $intervention ? null : ['route' => 'assessments.index', 'active' => 'assessments.*', 'label' => 'Pengukuran', 'short' => 'Ukur', 'icon' => '✎'],
    ]));
@endphp

@section('body')
    <a href="#konten" class="sr-only focus:not-sr-only focus:absolute focus:p-3">Lewati ke konten</a>

    <header class="sticky top-0 z-20 border-b border-black/5 bg-white/90 backdrop-blur">
        <div class="mx-auto flex max-w-5xl items-center justify-between gap-4 px-5 py-3">
            <a href="{{ route('dashboard') }}" class="text-lg font-bold text-primary">RITNOVA ☾</a>

            <nav class="hidden items-center gap-6 text-sm font-medium md:flex" aria-label="Navigasi utama">
                @foreach ($primary as $item)
                    <a href="{{ route($item['route']) }}" @class([
                        'text-primary' => request()->routeIs($item['active']),
                        'text-ink/60 hover:text-primary' => ! request()->routeIs($item['active']),
                    ])>{{ $item['label'] }}</a>
                @endforeach
            </nav>

            <div class="flex items-center gap-3 text-sm">
                <a href="{{ route('notifications.index') }}" class="relative rounded-full p-2 text-ink/60 hover:bg-lavender hover:text-primary" aria-label="Notifikasi">
                    🔔
                    @if ($unread)
                        <span class="absolute -top-0.5 -right-0.5 rounded-full bg-primary px-1.5 text-[10px] font-bold text-white">{{ $unread }}</span>
                    @endif
                </a>
                <a href="{{ route('menu') }}" @class(['hidden rounded-lg px-3 py-2 md:block', 'bg-lavender text-primary' => request()->routeIs('menu'), 'text-ink/60 hover:bg-lavender' => ! request()->routeIs('menu')])>Menu ▾</a>
            </div>
        </div>
    </header>

    <main id="konten" class="mx-auto max-w-5xl px-5 pt-6 pb-28 md:pb-12">
        @if (session('status'))
            <div class="mb-4 rounded-xl bg-sky/60 px-4 py-3 text-sm" role="status">{{ session('status') }}</div>
        @endif
        @yield('content')
    </main>

    {{-- Navigasi bawah (mobile): jangkauan ibu jari --}}
    <nav class="fixed inset-x-0 bottom-0 z-20 border-t border-black/5 bg-white md:hidden" aria-label="Navigasi bawah">
        <div class="mx-auto flex max-w-md justify-around py-2 text-xs">
            @foreach ($primary as $item)
                @continue($item['short'] === null)
                <a href="{{ route($item['route']) }}" @class([
                    'flex min-w-14 flex-col items-center gap-0.5 px-2',
                    'font-semibold text-primary' => request()->routeIs($item['active']),
                    'text-ink/50' => ! request()->routeIs($item['active']),
                ])>
                    <span class="text-lg leading-none">{{ $item['icon'] }}</span>{{ $item['short'] }}
                </a>
            @endforeach
            <a href="{{ route('menu') }}" @class(['flex min-w-14 flex-col items-center gap-0.5 px-2', 'font-semibold text-primary' => request()->routeIs('menu'), 'text-ink/50' => ! request()->routeIs('menu')])>
                <span class="text-lg leading-none">≡</span>Menu
            </a>
        </div>
    </nav>
@endsection
