@extends('layouts.participant', ['title' => 'Persiapan Awal'])

@section('content')
    <h1 class="mb-1 text-2xl font-bold">Persiapan Awal</h1>
    <p class="mb-6 text-ink/60">Langkah {{ $current }} dari 4</p>

    @error('onboarding') <div class="mb-4 rounded-xl bg-peach px-4 py-3 text-sm">{{ $message }}</div> @enderror

    <div class="grid gap-6 md:grid-cols-[1fr_18rem]">
        <div class="space-y-3">
            @foreach ($steps as $n => $step)
                <div @class(['card', 'ring-2 ring-primary/40' => $n === $current])>
                    <div class="flex items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <span @class(['flex h-8 w-8 items-center justify-center rounded-full text-sm font-bold', 'bg-primary text-white' => $step['done'], 'bg-lavender text-primary' => ! $step['done']])>{{ $step['done'] ? '✓' : $n }}</span>
                            <div><p class="font-semibold">{{ $step['label'] }}</p><p class="text-sm text-ink/60">{{ $step['detail'] }}</p></div>
                        </div>
                        <span class="badge {{ $step['done'] ? 'bg-green-100 text-green-800' : ($n === $current ? 'bg-lavender text-primary' : 'bg-canvas text-ink/50') }}">{{ $step['done'] ? 'Selesai' : ($n === $current ? 'Aktif' : 'Belum') }}</span>
                    </div>

                    @if ($n === 2 && ! $step['done'])
                        <form method="POST" action="{{ route('onboarding.profile') }}" class="mt-4 space-y-4 border-t border-black/5 pt-4">
                            @csrf
                            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="code_confirmed" value="1"> Kode peserta saya <b>{{ $user->username }}</b> sudah benar.</label>
                            @error('code_confirmed') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                            @include('participant.partials.reminder-fields', ['user' => $user])
                            <button class="btn-primary">Simpan profil</button>
                        </form>
                    @elseif ($n === 3 && ! $step['done'])
                        <a href="{{ route('diary.create') }}" class="btn-secondary mt-4 px-4 py-2 text-sm">Catat tidur</a>
                    @elseif ($n === 4 && ! $step['done'])
                        <a href="{{ route('assessments.index') }}" class="btn-secondary mt-4 px-4 py-2 text-sm">Lihat pengukuran</a>
                    @endif
                </div>
            @endforeach

            @if (! $user->onboarded_at)
                <form method="POST" action="{{ route('onboarding.complete') }}">
                    @csrf
                    <button class="btn-primary w-full" @disabled(! $steps[2]['done'])>Lanjutkan ke dashboard</button>
                    <p class="mt-2 text-center text-xs text-ink/50">Langkah 3 dan 4 bisa dilanjutkan dari dashboard sesuai jadwal pengukuran.</p>
                </form>
            @endif
        </div>

        <aside class="space-y-3">
            @foreach (config('ritnova.phase_labels') as $phase => $label)
                @php($w = $windows->get($phase))
                <div class="card p-4 text-sm">
                    <p class="font-semibold">{{ $phase }} — {{ $label }}</p>
                    <p class="text-ink/60">{{ $w ? $w->opens_at->translatedFormat('d M').' – '.($w->closes_at?->translatedFormat('d M Y') ?? '…') : 'Jadwal ditetapkan peneliti' }}</p>
                </div>
            @endforeach
            <p class="text-xs text-ink/50">Definisi jendela diary dan instrumen ditetapkan di protokol penelitian.</p>
        </aside>
    </div>
@endsection
