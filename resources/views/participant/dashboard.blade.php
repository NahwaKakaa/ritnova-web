@extends('layouts.participant', ['title' => 'Beranda'])

@section('content')
    <h1 class="mb-1 text-2xl font-bold">Halo, Peserta {{ $user->username }} ☾</h1>
    <p class="mb-6 text-ink/60">
        @if ($tasks->isEmpty())
            Semua tugas hari ini selesai. Sampai jumpa besok!
        @else
            Hari ini: {{ $tasks->pluck('label')->map(fn ($l) => lcfirst($l))->join(', ', ', lalu ') }}.
        @endif
    </p>

    @if (! $user->onboarded_at)
        <a href="{{ route('onboarding') }}" class="mb-4 block rounded-xl bg-peach/70 px-4 py-3 text-sm">Persiapan awal belum selesai — lanjutkan langkahnya di sini →</a>
    @endif

    @foreach ($notifications as $n)
        <a href="{{ $n->data['url'] ?? route('notifications.index') }}" class="mb-3 block rounded-xl bg-sky/50 px-4 py-3 text-sm">🔔 {{ $n->data['message'] }}</a>
    @endforeach

    {{-- Ringkasan --}}
    <div class="mb-6 grid grid-cols-2 gap-3 md:grid-cols-4">
        @if ($user->isIntervention())
            <div class="card p-4"><p class="text-xs text-ink/60">Sesi selesai</p><p class="text-2xl font-bold">{{ str_pad($completedCount, 2, '0', STR_PAD_LEFT) }}/12</p></div>
        @endif
        <div class="card p-4"><p class="text-xs text-ink/60">Diary valid</p><p class="text-2xl font-bold">{{ $validNights }} <span class="text-sm font-normal">malam</span></p></div>
        @if ($user->isIntervention())
            <div class="card p-4"><p class="text-xs text-ink/60">Target aktif</p><p class="text-2xl font-bold">{{ $activeGoal ? 1 : 0 }}</p></div>
        @endif
        <div class="card p-4">
            <p class="text-xs text-ink/60">Pengukuran</p>
            <p class="text-lg font-bold">
                @foreach ($phases as $phase => $state)
                    <span class="{{ $state === 'done' ? 'text-primary' : 'text-ink/30' }}">{{ $phase }}{{ $state === 'done' ? ' ✓' : '' }}</span>
                @endforeach
            </p>
        </div>
    </div>

    <div class="grid gap-4 md:grid-cols-2">
        {{-- 1. Tugas harian prioritas --}}
        <div class="card">
            <p class="mb-3 text-sm font-semibold text-secondary">Misi harian</p>
            @forelse ($tasks as $task)
                <div class="flex items-center justify-between gap-3 border-b border-black/5 py-2 last:border-0">
                    <span>{{ $task['label'] }}</span>
                    <a href="{{ $task['route'] }}" class="btn-primary px-4 py-2 text-sm">{{ $task['cta'] }}</a>
                </div>
            @empty
                <p class="text-ink/60">Tidak ada tugas tertunda. 👍</p>
            @endforelse
        </div>

        {{-- 2. Status modul minggu ini --}}
        @if ($user->isIntervention())
            <div class="card">
                <p class="mb-3 text-sm font-semibold text-secondary">Progres minggu {{ $week ?? '–' }}{{ $week ? ' · '.config('ritnova.weeks')[$week] : '' }}</p>
                @forelse ($weekSessions as $row)
                    <div class="flex items-center justify-between py-1.5 text-sm">
                        <span>Sesi {{ str_pad($row['session']->number, 2, '0', STR_PAD_LEFT) }} · {{ $row['session']->title }}</span>
                        <span class="text-ink/60">{{ $row['decision']->status->label() }}</span>
                    </div>
                @empty
                    <p class="text-sm text-ink/60">
                        @if ($upcoming)
                            Sesi pertama dibuka {{ $upcoming['schedule']->release_at->translatedFormat('l, d F · H.i') }} WITA.
                        @else
                            Jadwal sesi belum tersedia.
                        @endif
                    </p>
                @endforelse
                <a href="{{ route('learning.index') }}" class="mt-3 inline-block text-sm font-semibold text-primary">Lihat semua sesi →</a>
            </div>

            {{-- 4. Target pekanan --}}
            <div class="card">
                <p class="mb-3 text-sm font-semibold text-secondary">Target minggu ini</p>
                @if ($activeGoal)
                    <p class="font-semibold">{{ $activeGoal->target }}</p>
                    @if ($activeGoal->if_then_plan)<p class="mt-1 text-sm text-ink/60">{{ $activeGoal->if_then_plan }}</p>@endif
                @else
                    <p class="mb-3 text-sm text-ink/60">Belum ada target aktif.</p>
                @endif
                <a href="{{ route('goals.index') }}" class="mt-3 inline-block text-sm font-semibold text-primary">{{ $activeGoal ? 'Kelola target →' : 'Buat target →' }}</a>
            </div>
        @endif

        {{-- 5. Jadwal pengukuran --}}
        <div class="card">
            <p class="mb-3 text-sm font-semibold text-secondary">Jadwal pengukuran</p>
            @foreach (config('ritnova.phase_labels') as $phase => $label)
                @php($w = $windows->get($phase))
                <div class="flex items-center justify-between py-1.5 text-sm">
                    <span><b>{{ $phase }}</b> · {{ $label }}</span>
                    <span class="text-ink/60">{{ $w ? $w->opens_at->translatedFormat('d M') : '–' }}</span>
                </div>
            @endforeach
            <a href="{{ route('assessments.index') }}" class="mt-3 inline-block text-sm font-semibold text-primary">Buka pengukuran →</a>
        </div>
    </div>
@endsection
