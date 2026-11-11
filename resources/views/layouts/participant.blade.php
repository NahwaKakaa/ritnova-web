@extends('layouts.base')

@php
    $user = auth()->user();
    $nav = [
        ['route' => 'dashboard', 'label' => 'Beranda', 'icon' => '⌂', 'show' => true],
        ['route' => 'learning.index', 'label' => 'Belajar', 'icon' => '▤', 'show' => $user->isIntervention()],
        ['route' => 'diary.index', 'label' => 'Catat Tidur', 'icon' => '☽', 'show' => true],
        ['route' => 'sjl', 'label' => 'SJL Check', 'icon' => '◷', 'show' => $user->isIntervention()],
        ['route' => 'assessments.index', 'label' => 'Pengukuran', 'icon' => '✎', 'show' => true],
    ];
@endphp

@section('body')
    <header class="sticky top-0 z-10 border-b border-black/5 bg-white/90 backdrop-blur">
        <div class="mx-auto flex max-w-5xl items-center justify-between px-5 py-3">
            <a href="{{ route('dashboard') }}" class="text-lg font-bold text-primary">RITNOVA ☾</a>
            <nav class="hidden gap-6 text-sm font-medium md:flex">
                @foreach ($nav as $item)
                    @if ($item['show'])
                        <a href="{{ route($item['route']) }}" @class(['text-primary' => request()->routeIs($item['route'].'*'), 'text-ink/60 hover:text-primary' => ! request()->routeIs($item['route'].'*')])>{{ $item['label'] }}</a>
                    @endif
                @endforeach
            </nav>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="text-sm text-ink/60 hover:text-primary">Keluar</button>
            </form>
        </div>
    </header>

    <main class="mx-auto max-w-5xl px-5 pt-6 pb-28 md:pb-10">
        @if (session('status'))
            <div class="mb-4 rounded-xl bg-sky/60 px-4 py-3 text-sm">{{ session('status') }}</div>
        @endif
        @yield('content')
    </main>

    {{-- Navigasi bawah (mobile) --}}
    <nav class="fixed inset-x-0 bottom-0 border-t border-black/5 bg-white md:hidden">
        <div class="mx-auto flex max-w-md justify-around py-2 text-xs">
            @foreach ($nav as $item)
                @if ($item['show'])
                    <a href="{{ route($item['route']) }}" @class(['flex flex-col items-center gap-0.5 px-2', 'text-primary font-semibold' => request()->routeIs($item['route'].'*'), 'text-ink/50' => ! request()->routeIs($item['route'].'*')])>
                        <span class="text-lg">{{ $item['icon'] }}</span>{{ $item['label'] }}
                    </a>
                @endif
            @endforeach
        </div>
    </nav>
@endsection
