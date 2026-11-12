@extends('layouts.base')

@php
    $me = auth()->user();
    $groups = [
        'Pemantauan' => [
            ['admin.dashboard', 'Ringkasan kohort', 'chart', 'admin.dashboard'],
            ['admin.participants.index', 'Peserta & kelompok', 'users', 'admin.participants.*'],
            ['admin.exposure.index', 'Paparan sesi', 'activity', 'admin.exposure.*'],
            ['admin.measurements.index', 'Pengukuran T0–T2', 'clipboard', 'admin.measurements.*'],
            ['admin.diary-quality.index', 'Kualitas diary', 'moon', 'admin.diary-quality.*'],
        ],
        'Pengaturan studi' => [
            ['admin.content.index', 'Konten sesi', 'book', 'admin.content.*'],
            ['admin.schedule.index', 'Jadwal rilis', 'calendar', 'admin.schedule.*'],
        ],
        'Data' => [
            ['admin.export.index', 'Ekspor dataset', 'database', 'admin.export.*'],
            ['admin.audit.index', 'Audit log', 'list', 'admin.audit.*'],
        ],
    ];
    if ($me->role === \App\Models\User::ROLE_ADMIN) {
        $groups['Data'][] = ['admin.staff.index', 'Akses tim', 'key', 'admin.staff.*'];
    }
    $cohorts = \App\Support\AdminCohort::all();
    $currentCohort = \App\Support\AdminCohort::current();
    $crumb = $crumb ?? null;
@endphp

@section('body')
    <a href="#konten" class="sr-only focus:not-sr-only focus:fixed focus:top-2 focus:left-2 focus:z-50 focus:rounded-lg focus:bg-surface focus:p-3">Lewati ke konten</a>

    <div class="min-h-screen md:grid md:grid-cols-[17rem_1fr]">
        {{-- Sidebar --}}
        <aside id="admin-drawer" class="fixed inset-y-0 left-0 z-40 w-[17rem] -translate-x-full transition-transform duration-300 md:sticky md:top-0 md:h-screen md:translate-x-0" data-drawer>
            <div class="panel-night flex h-full flex-col rounded-none md:rounded-r-[2rem]">
                <div class="flex items-center justify-between px-5 pt-5 pb-4">
                    <a href="{{ route('admin.dashboard') }}" class="flex flex-col">
                        <x-logo light />
                        <span class="mt-1 pl-10 text-xs text-white/50">Panel peneliti</span>
                    </a>
                    <button type="button" class="rounded-full p-2 text-white/70 hover:bg-white/10 md:hidden" data-drawer-close aria-label="Tutup menu"><x-icon name="x" /></button>
                </div>

                @if ($cohorts->isNotEmpty())
                    <form method="GET" action="{{ url()->current() }}" class="mx-4 mb-4">
                        <label for="kohort" class="mb-1 block text-xs text-white/50">Kohort</label>
                        <select id="kohort" name="kohort" class="w-full rounded-xl border-0 bg-white/10 px-3 py-2.5 text-sm text-white ring-1 ring-white/10 focus:ring-2 focus:ring-[#BFB4FF]" data-autosubmit>
                            @foreach ($cohorts as $c)
                                <option value="{{ $c->id }}" class="text-ink" @selected($currentCohort?->id === $c->id)>{{ $c->name }}</option>
                            @endforeach
                        </select>
                        <noscript><button class="btn-on-night btn-sm mt-2 w-full">Ganti kohort</button></noscript>
                    </form>
                @endif

                <nav class="flex-1 space-y-5 overflow-y-auto px-3 pb-4" aria-label="Menu peneliti">
                    @foreach ($groups as $title => $links)
                        <div>
                            <p class="mb-1.5 px-3 text-[11px] font-semibold text-white/40">{{ $title }}</p>
                            <ul class="space-y-0.5">
                                @foreach ($links as [$route, $label, $icon, $pattern])
                                    @php $active = request()->routeIs($pattern); @endphp
                                    <li>
                                        <a href="{{ route($route) }}" @class(['admin-link', 'admin-link-active' => $active]) @if ($active) aria-current="page" @endif>
                                            <x-icon :name="$icon" class="h-[18px] w-[18px] shrink-0" />{{ $label }}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                </nav>

                <div class="flex items-center gap-3 border-t border-white/10 px-5 py-4">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-white/10 text-sm font-bold">{{ mb_strtoupper(mb_substr($me->name ?: $me->username, 0, 1)) }}</span>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-semibold">{{ $me->name ?: $me->username }}</p>
                        <p class="text-xs text-white/50">{{ $me->role === 'admin' ? 'Admin' : 'Peneliti' }}</p>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="rounded-full p-2 text-white/60 hover:bg-white/10 hover:text-white" aria-label="Keluar" data-tip="Keluar"><x-icon name="logout" class="h-[18px] w-[18px]" /></button>
                    </form>
                </div>
            </div>
        </aside>
        <div class="fixed inset-0 z-30 hidden bg-night/50 backdrop-blur-sm md:hidden" data-drawer-backdrop></div>

        <div class="min-w-0">
            <header class="sticky top-0 z-20 border-b border-ink/5 bg-canvas/85 backdrop-blur-md">
                <div class="flex h-16 items-center gap-3 px-4 sm:px-6 lg:px-10">
                    <button type="button" class="-ml-1 rounded-full p-2 hover:bg-lavender md:hidden" data-drawer-open aria-controls="admin-drawer" aria-expanded="false" aria-label="Buka menu"><x-icon name="menu" /></button>
                    <p class="min-w-0 flex-1 truncate text-sm text-ink/55">
                        <span class="hidden sm:inline">ritnova / </span><span class="font-medium text-ink">{{ $crumb ?? ($title ?? 'Panel peneliti') }}</span>
                        @if ($currentCohort)<span class="hidden lg:inline"> · {{ $currentCohort->name }}</span>@endif
                    </p>
                    <span class="hidden items-center gap-1.5 rounded-full bg-surface px-3 py-1.5 text-xs font-medium ring-1 ring-ink/5 sm:inline-flex"><x-icon name="clock" class="h-3.5 w-3.5 text-brand" /><span data-clock class="num">{{ now()->format('H.i') }}</span> WITA</span>
                    <x-theme-switch />
                </div>
            </header>

            <main id="konten" class="px-4 py-6 sm:px-6 md:py-8 lg:px-10">
                @if (session('status'))
                    <div class="mb-6 flex items-center gap-3 rounded-[var(--radius-control)] bg-mint px-4 py-3 text-sm text-mint-deep" role="status"><x-icon name="check" class="h-4 w-4" />{{ session('status') }}</div>
                @endif
                @if ($errors->any())
                    <div class="mb-6 rounded-[var(--radius-control)] bg-rose px-4 py-3 text-sm text-rose-deep" role="alert">
                        <p class="font-semibold">Periksa lagi isian berikut:</p>
                        <ul class="mt-1 list-disc pl-5">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                    </div>
                @endif
                @yield('content')
            </main>
        </div>
    </div>
@endsection
