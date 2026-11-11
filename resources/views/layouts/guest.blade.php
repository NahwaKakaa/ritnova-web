@extends('layouts.base')

@section('body')
    <div class="grid min-h-screen lg:grid-cols-[minmax(0,5fr)_minmax(0,6fr)]">
        {{-- Panel malam (desktop) --}}
        <aside class="relative hidden overflow-hidden bg-night p-12 text-white lg:flex lg:flex-col lg:justify-between">
            <a href="{{ route('landing') }}"><x-logo light /></a>
            <div class="relative z-10 max-w-sm">
                <p class="mb-4 text-3xl leading-tight font-bold">Kenali ritmemu.<br>Selaraskan harimu.</p>
                <p class="text-white/65">Program 4 minggu untuk memahami dan menata ritme tidur mahasiswa.</p>
            </div>
            <p class="relative z-10 text-xs text-white/40">Program edukasi, bukan layanan diagnosis.</p>
            <svg class="pointer-events-none absolute -right-24 top-1/2 h-[30rem] w-[30rem] -translate-y-1/2 text-secondary/25" viewBox="0 0 64 64" aria-hidden="true">
                <path d="M28.85 16.04 A18 18 0 1 0 47.96 35.15 A14 14 0 0 1 28.85 16.04 Z" fill="currentColor" />
            </svg>
        </aside>

        <main class="relative flex flex-col justify-center px-5 py-10 sm:px-10">
            <x-theme-switch class="absolute top-2 right-4 sm:right-8 md:top-0" />
            <div class="mx-auto w-full max-w-md">
                <a href="{{ route('landing') }}" class="mb-10 inline-block lg:hidden"><x-logo /></a>
                @if (session('status'))
                    <div class="mb-5 rounded-[var(--radius-control)] bg-sky/50 px-4 py-3 text-sm" role="status">{{ session('status') }}</div>
                @endif
                @yield('content')
            </div>
        </main>
    </div>
@endsection
