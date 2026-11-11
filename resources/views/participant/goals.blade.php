@extends('layouts.participant', ['title' => 'Target minggu ini'])

@php($statusLabels = ['achieved' => ['Tercapai', 'pill-done'], 'partial' => ['Sebagian', 'pill-warn'], 'not_achieved' => ['Belum tercapai', 'pill-muted'], 'active' => ['Belum dievaluasi', 'pill-open']])

@section('content')
    <div class="mb-8">
        <p class="mb-1 font-semibold text-secondary num">Minggu {{ $week }}</p>
        <h1 class="page-title">Target minggu ini</h1>
        <p class="mt-2 max-w-xl muted">Satu target perilaku yang spesifik dan masuk akal. Tidak harus sama dengan orang lain.</p>
    </div>

    <div class="grid gap-8 lg:grid-cols-[1.2fr_0.8fr]">
        <form method="POST" action="{{ route('goals.store') }}" class="card space-y-6 sm:p-7">
            @csrf
            <div>
                <label class="label" for="target">Target</label>
                <input class="input text-lg font-semibold" id="target" name="target" maxlength="255" placeholder="Mulai tidur sebelum 23.30 minimal 4 hari" value="{{ old('target', $goal?->target) }}" required>
                @error('target') <p class="mt-1.5 text-sm text-rose-deep">{{ $message }}</p> @enderror
            </div>
            <fieldset>
                <legend class="label">Rencana aksi: kapan, di mana, bagaimana</legend>
                <div class="space-y-2">
                    @foreach (range(0, 2) as $i)
                        <div class="flex items-center gap-3">
                            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-lavender text-xs font-bold text-brand">{{ $i + 1 }}</span>
                            <input class="input" name="action_plan[]" maxlength="255" placeholder="{{ ['Matikan layar 30 menit lebih awal', 'Siapkan rutinitas malam 10 menit', 'Pasang alarm pengingat tidur'][$i] }}" value="{{ old('action_plan.'.$i, $goal?->action_plan[$i] ?? '') }}" aria-label="Langkah {{ $i + 1 }}">
                        </div>
                    @endforeach
                </div>
            </fieldset>
            <div>
                <label class="label" for="if_then_plan">Kalau ada hambatan</label>
                <input class="input" id="if_then_plan" name="if_then_plan" maxlength="500" placeholder="Jika tugas menumpuk, maka saya buat batas belajar pukul 22.30" value="{{ old('if_then_plan', $goal?->if_then_plan) }}">
            </div>
            <button type="submit" class="btn-primary w-full">Simpan target</button>
        </form>

        <div class="space-y-6">
            @if ($goal)
                <section class="panel-lavender">
                    <h2 class="mb-1 font-bold">Review akhir minggu</h2>
                    <p class="mb-4 text-sm text-ink/65">Bagaimana targetmu minggu ini? Nilai dengan jujur, tanpa menghakimi diri sendiri.</p>
                    <form method="POST" action="{{ route('goals.review', $goal) }}" class="grid gap-2">
                        @csrf
                        @method('PATCH')
                        @foreach (['achieved' => 'Tercapai', 'partial' => 'Sebagian tercapai', 'not_achieved' => 'Belum tercapai'] as $value => $label)
                            <button type="submit" name="status" value="{{ $value }}" @class(['rounded-[var(--radius-control)] px-4 py-3 text-left text-sm font-semibold transition', 'bg-primary text-white' => $goal->status === $value, 'bg-surface text-ink hover:bg-surface/70' => $goal->status !== $value])>{{ $label }}</button>
                        @endforeach
                    </form>
                </section>
            @endif

            @if ($history->isNotEmpty())
                <section>
                    <h2 class="mb-3 font-bold">Target sebelumnya</h2>
                    <div class="card overflow-hidden p-0">
                        @foreach ($history as $past)
                            <div class="flex items-center justify-between gap-3 border-b border-ink/5 px-5 py-4 text-sm last:border-0">
                                <span><span class="block text-xs muted num">Minggu {{ $past->week }}</span>{{ $past->target }}</span>
                                <span class="{{ $statusLabels[$past->status][1] ?? 'pill-muted' }}">{{ $statusLabels[$past->status][0] ?? $past->status }}</span>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

            <a href="{{ route('reflection.index') }}" class="card flex items-center gap-4 hover:ring-primary/30">
                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-peach/70 text-warn-ink"><x-icon name="sparkle" /></span>
                <span class="flex-1"><span class="block font-semibold">Refleksi mingguan</span><span class="text-sm muted">Kurang dari 2 menit</span></span>
                <x-icon name="arrow-right" class="h-4 w-4 text-ink/25" />
            </a>
        </div>
    </div>
@endsection
