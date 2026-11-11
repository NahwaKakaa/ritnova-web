@extends('layouts.base')

@php
    $nav = [
        'admin.dashboard' => 'Kohort',
        'admin.participants.index' => 'Peserta',
        'admin.schedule.index' => 'Jadwal',
        'admin.exposure.index' => 'Paparan',
        'admin.export.index' => 'Ekspor',
        'admin.audit.index' => 'Audit',
    ];
@endphp

@section('body')
    <div class="flex min-h-screen">
        <aside class="hidden w-60 shrink-0 bg-night p-5 text-white md:block">
            <div class="mb-8 text-lg font-bold">RITNOVA <span class="text-white/50">Peneliti</span></div>
            <nav class="space-y-1 text-sm">
                @foreach ($nav as $route => $label)
                    <a href="{{ route($route) }}" @class(['block rounded-lg px-3 py-2', 'bg-white/15 font-semibold' => request()->routeIs($route), 'text-white/70 hover:bg-white/10' => ! request()->routeIs($route)])>{{ $label }}</a>
                @endforeach
            </nav>
            <form method="POST" action="{{ route('logout') }}" class="mt-8">
                @csrf
                <button class="text-sm text-white/60 hover:text-white">Keluar</button>
            </form>
        </aside>
        <main class="flex-1 p-6 md:p-10">
            <div class="mb-4 flex gap-3 overflow-x-auto text-sm md:hidden">
                @foreach ($nav as $route => $label)
                    <a href="{{ route($route) }}" class="whitespace-nowrap rounded-lg bg-surface px-3 py-2">{{ $label }}</a>
                @endforeach
            </div>
            @if (session('status'))
                <div class="mb-4 rounded-xl bg-sky/60 px-4 py-3 text-sm">{{ session('status') }}</div>
            @endif
            @yield('content')
        </main>
    </div>
@endsection
