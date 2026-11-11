@extends('layouts.participant', ['title' => 'Target Minggu Ini'])

@section('content')
    <div class="mx-auto max-w-2xl">
        <h1 class="mb-1 text-2xl font-bold">Tujuan Minggu Ini</h1>
        <p class="mb-6 text-ink/60">Minggu {{ $week }} · target perilaku yang spesifik dan masuk akal — tidak harus sama dengan orang lain.</p>

        <form method="POST" action="{{ route('goals.store') }}" class="card space-y-4">
            @csrf
            <div>
                <label class="label" for="target">Target</label>
                <input class="input" id="target" name="target" maxlength="255" placeholder="Contoh: mulai tidur sebelum 23.30 minimal 4 hari" value="{{ old('target', $goal?->target) }}" required>
                @error('target') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <span class="label">Rencana aksi (kapan, di mana, bagaimana)</span>
                @foreach (range(0, 2) as $i)
                    <input class="input mb-2" name="action_plan[]" maxlength="255" placeholder="{{ ['Matikan layar 30 menit lebih awal', 'Siapkan rutinitas malam 10 menit', 'Atur alarm pengingat tidur'][$i] }}" value="{{ old('action_plan.'.$i, $goal?->action_plan[$i] ?? '') }}">
                @endforeach
            </div>
            <div>
                <label class="label" for="if_then_plan">Antisipasi hambatan (jika–maka)</label>
                <input class="input" id="if_then_plan" name="if_then_plan" maxlength="500" placeholder="Jika tugas menumpuk, maka saya buat batas waktu belajar pukul 22.30" value="{{ old('if_then_plan', $goal?->if_then_plan) }}">
            </div>
            <button class="btn-primary w-full">Simpan target</button>
        </form>

        @if ($goal)
            <div class="card mt-4">
                <p class="mb-1 font-semibold">Review akhir minggu</p>
                <p class="mb-3 text-sm text-ink/60">Bagaimana targetmu minggu ini? Evaluasi tanpa menghakimi diri sendiri.</p>
                <form method="POST" action="{{ route('goals.review', $goal) }}" class="flex flex-wrap gap-2">
                    @csrf
                    @method('PATCH')
                    @foreach (['achieved' => 'Tercapai', 'partial' => 'Sebagian', 'not_achieved' => 'Belum tercapai'] as $value => $label)
                        <button name="status" value="{{ $value }}" @class(['rounded-xl px-4 py-2 text-sm font-semibold', 'bg-primary text-white' => $goal->status === $value, 'bg-lavender text-primary' => $goal->status !== $value])>{{ $label }}</button>
                    @endforeach
                </form>
            </div>
        @endif

        @if ($history->isNotEmpty())
            <h2 class="mt-8 mb-3 font-semibold">Target sebelumnya</h2>
            <div class="space-y-2">
                @foreach ($history as $past)
                    <div class="card flex items-center justify-between gap-3 p-4 text-sm">
                        <span><b>Minggu {{ $past->week }}:</b> {{ $past->target }}</span>
                        <span class="shrink-0 text-ink/60">{{ ['achieved' => 'Tercapai', 'partial' => 'Sebagian', 'not_achieved' => 'Belum tercapai', 'active' => 'Belum dievaluasi'][$past->status] ?? $past->status }}</span>
                    </div>
                @endforeach
            </div>
        @endif

        <a href="{{ route('reflection.index') }}" class="mt-6 inline-block text-sm font-semibold text-primary">Isi refleksi mingguan →</a>
    </div>
@endsection
