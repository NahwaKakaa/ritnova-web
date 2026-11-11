@extends('layouts.base')

@php
    $user = auth()->user();
    $intervention = $user->isIntervention();
    $unread = $user->unreadNotifications()->count();

    // Navigasi utama (Opsi A). Menu intervensi disembunyikan untuk kelompok pembanding.
    $primary = array_values(array_filter([
        ['route' => 'dashboard', 'active' => 'dashboard', 'label' => 'Beranda', 'short' => 'Beranda', 'icon' => 'home'],
        $intervention ? ['route' => 'learning.index', 'active' => 'learning.*', 'label' => 'Belajar', 'short' => 'Belajar', 'icon' => 'book'] : null,
        ['route' => 'diary.index', 'active' => 'diary.*', 'label' => 'Sleep Tracker', 'short' => 'Tracker', 'icon' => 'moon'],
        $intervention ? ['route' => 'sjl', 'active' => 'sjl*', 'label' => 'SJL Check', 'short' => 'SJL', 'icon' => 'clock'] : null,
        $intervention ? ['route' => 'progress', 'active' => 'progress', 'label' => 'Progres', 'short' => null, 'icon' => 'chart'] : null,
        $intervention ? null : ['route' => 'assessments.index', 'active' => 'assessments.*', 'label' => 'Pengukuran', 'short' => 'Ukur', 'icon' => 'clipboard'],
    ]));
@endphp

@section('body')
    <a href="#konten" class="sr-only focus:not-sr-only focus:fixed focus:top-2 focus:left-2 focus:z-50 focus:rounded-lg focus:bg-white focus:p-3">Lewati ke konten</a>

    <header class="sticky top-0 z-30 border-b border-night/5 bg-canvas/85 backdrop-blur-md">
        <div class="mx-auto flex h-16 max-w-6xl items-center justify-between gap-4 px-5">
            <a href="{{ route('dashboard') }}" aria-label="Beranda RITNOVA"><x-logo /></a>

            <nav class="hidden items-center gap-1 md:flex" aria-label="Navigasi utama">
                @foreach ($primary as $item)
                    <a href="{{ route($item['route']) }}" @class(['nav-link', 'nav-link-active' => request()->routeIs($item['active'])]) @if (request()->routeIs($item['active'])) aria-current="page" @endif>{{ $item['label'] }}</a>
                @endforeach
            </nav>

            <div class="flex items-center gap-1.5">
                <a href="{{ route('notifications.index') }}" @class(['relative rounded-full p-2.5 transition hover:bg-lavender hover:text-primary', 'bg-lavender text-primary' => request()->routeIs('notifications.*'), 'text-night/60' => ! request()->routeIs('notifications.*')]) aria-label="Notifikasi{{ $unread ? ", $unread belum dibaca" : '' }}">
                    <x-icon name="bell" />
                    @if ($unread)
                        <span class="absolute top-1.5 right-1.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-peach-deep px-1 text-[10px] font-bold text-white">{{ $unread }}</span>
                    @endif
                </a>
                <a href="{{ route('menu') }}" @class(['hidden items-center gap-2 rounded-full py-1.5 pr-3.5 pl-1.5 text-sm font-semibold transition md:inline-flex', 'bg-lavender text-primary' => request()->routeIs('menu'), 'bg-white text-night ring-1 ring-night/10 hover:bg-lavender' => ! request()->routeIs('menu')])>
                    <span class="flex h-7 w-7 items-center justify-center rounded-full bg-primary text-[11px] text-white">{{ substr($user->username, -2) }}</span>
                    {{ $user->username }}
                </a>
            </div>
        </div>
    </header>

    <main id="konten" class="mx-auto max-w-6xl px-5 pt-6 pb-32 md:pt-10 md:pb-16">
        @if (session('status'))
            <div class="mb-6 flex items-center gap-3 rounded-[var(--radius-control)] bg-mint px-4 py-3 text-sm text-mint-deep" role="status">
                <x-icon name="check" class="h-4 w-4" />{{ session('status') }}
            </div>
        @endif
        @yield('content')
    </main>

    @if ($celebrate = session('celebrate'))
        @include('participant.partials.celebrate', ['celebrate' => $celebrate])
    @endif

    {{-- Navigasi bawah (mobile): jangkauan ibu jari --}}
    <nav class="fixed inset-x-3 bottom-3 z-30 rounded-[1.5rem] bg-white/95 shadow-[var(--shadow-lift)] ring-1 ring-night/5 backdrop-blur md:hidden" aria-label="Navigasi bawah">
        <div class="flex justify-around px-1 py-1.5">
            @foreach ($primary as $item)
                @continue($item['short'] === null)
                @php($on = request()->routeIs($item['active']))
                <a href="{{ route($item['route']) }}" @class(['flex flex-1 flex-col items-center gap-1 rounded-2xl py-2 text-[11px] font-medium', 'bg-lavender text-primary' => $on, 'text-night/50' => ! $on])>
                    <x-icon :name="$item['icon']" />{{ $item['short'] }}
                </a>
            @endforeach
            @php($on = request()->routeIs('menu'))
            <a href="{{ route('menu') }}" @class(['flex flex-1 flex-col items-center gap-1 rounded-2xl py-2 text-[11px] font-medium', 'bg-lavender text-primary' => $on, 'text-night/50' => ! $on])>
                <x-icon name="grid" />Menu
            </a>
        </div>
    </nav>
@endsection
