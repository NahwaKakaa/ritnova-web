@extends('layouts.participant', ['title' => 'Persiapan awal'])

@section('content')
    <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="mb-1 font-semibold text-secondary num">Langkah {{ $current }} dari {{ count($steps) }}</p>
            <h1 class="page-title">Persiapan awal</h1>
            <p class="mt-2 muted">Selesaikan langkah-langkah ini sebelum program dimulai.</p>
        </div>
        <div class="flex gap-1.5" aria-hidden="true">
            @foreach ($steps as $n => $step)
                <span @class(['h-2 w-10 rounded-full', 'bg-primary' => $step['done'], 'bg-secondary' => ! $step['done'] && $n === $current, 'bg-lavender' => ! $step['done'] && $n !== $current])></span>
            @endforeach
        </div>
    </div>

    @error('onboarding') <div class="mb-6 rounded-[var(--radius-control)] bg-peach/70 px-4 py-3 text-sm">{{ $message }}</div> @enderror

    <div class="grid gap-8 lg:grid-cols-[1fr_20rem]">
        <ol class="space-y-3">
            @foreach ($steps as $n => $step)
                <li @class(['card sm:p-6', 'ring-2 ring-primary/40' => $n === $current && ! $step['done']])>
                    <div class="flex items-start gap-4">
                        <span @class(['flex h-10 w-10 shrink-0 items-center justify-center rounded-full font-bold num', 'bg-primary text-white' => $step['done'], 'bg-lavender text-brand' => ! $step['done']])>
                            @if ($step['done']) <x-icon name="check" class="h-4 w-4" /> @else {{ $n }} @endif
                        </span>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <h2 class="text-lg font-bold">{{ $step['label'] }}</h2>
                                <span class="{{ $step['done'] ? 'pill-done' : ($n === $current ? 'pill-open' : 'pill-muted') }}">{{ $step['done'] ? 'Selesai' : ($n === $current ? 'Sekarang' : 'Belum') }}</span>
                            </div>
                            <p class="mt-1 muted">{{ $step['detail'] }}</p>

                            @if ($n === 2 && ! $step['done'])
                                <form method="POST" action="{{ route('onboarding.profile') }}" class="mt-5 space-y-5 border-t border-ink/5 pt-5">
                                    @csrf
                                    <label class="choice"><input type="checkbox" name="code_confirmed" value="1" class="h-4 w-4 accent-primary">Kode peserta saya <b class="num">{{ $user->username }}</b> sudah benar.</label>
                                    @error('code_confirmed') <p class="text-sm text-rose-deep">{{ $message }}</p> @enderror
                                    @include('participant.partials.profile-fields', ['user' => $user])
                                    <p class="border-t border-ink/5 pt-5 font-semibold">Pengingat</p>
                                    @include('participant.partials.reminder-fields', ['user' => $user])
                                    <button type="submit" class="btn-primary">Simpan profil</button>
                                </form>
                            @elseif ($n === 3 && ! $step['done'] && $steps[2]['done'])
                                <form method="POST" action="{{ route('onboarding.screening') }}" class="mt-5 space-y-5 border-t border-ink/5 pt-5">
                                    @csrf
                                    <p class="text-sm muted">Jawab sesuai keadaanmu. Jawaban hanya dilihat tim peneliti dan tidak bisa diubah setelah dikirim.</p>
                                    @foreach ($questions as $q => [$text, $options])
                                        <fieldset>
                                            <legend class="mb-2 font-medium"><span class="num">{{ $q }}.</span> {{ $text }}</legend>
                                            <div class="grid gap-2 sm:grid-cols-{{ min(count($options), 3) }}">
                                                @foreach ($options as $value => $label)
                                                    <label class="choice"><input type="radio" name="q[{{ $q }}]" value="{{ $value }}" class="h-4 w-4 accent-primary" @checked(old("q.$q") === $value) required>{{ $label }}</label>
                                                @endforeach
                                            </div>
                                            @error("q.$q") <p class="mt-1.5 text-sm text-rose-deep">{{ $message }}</p> @enderror
                                        </fieldset>
                                    @endforeach
                                    <button type="submit" class="btn-primary">Kirim jawaban skrining</button>
                                </form>
                            @elseif ($n === 4 && $instruments->isNotEmpty())
                                <ul class="mt-4 divide-y divide-ink/5 border-t border-ink/5">
                                    @foreach ($instruments as $ins)
                                        <li class="flex flex-wrap items-center justify-between gap-3 py-3">
                                            <span class="font-semibold">{{ $ins['name'] }}</span>
                                            @if ($ins['done'])
                                                <span class="pill-done">Selesai</span>
                                            @elseif ($t0Phase === 'open')
                                                <a href="{{ $ins['url'] }}" class="btn-secondary btn-sm">Isi kuesioner</a>
                                            @else
                                                <span class="pill-muted">Belum</span>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            @elseif ($n === 5 && $diary)
                                <ol class="mt-4 grid grid-cols-7 gap-1.5" aria-label="Hari pencatatan T0">
                                    @foreach ($diary['days'] as $day)
                                        <li @class(['flex flex-col items-center rounded-[var(--radius-control)] px-1 py-2 text-center text-xs', 'bg-primary text-white' => $day['filled'], 'bg-lavender/60' => ! $day['filled']])
                                            title="Malam {{ $day['date']->translatedFormat('l, d M') }}{{ $day['filled'] ? ' sudah dicatat' : '' }}">
                                            <span class="font-bold num">{{ $day['n'] }}</span>
                                            <span class="mt-1 flex h-4 items-center">
                                                @if ($day['filled']) <x-icon name="check" class="h-3.5 w-3.5" /> @else <span class="num opacity-70">{{ $day['date']->format('d/m') }}</span> @endif
                                            </span>
                                        </li>
                                    @endforeach
                                </ol>
                                @if (! $step['done'])
                                    <a href="{{ route('diary.create') }}" class="btn-secondary btn-sm mt-4">Catat tidur semalam</a>
                                @endif
                            @endif
                        </div>
                    </div>
                </li>
            @endforeach

            @if (! $user->onboarded_at)
                <li>
                    <form method="POST" action="{{ route('onboarding.complete') }}" class="pt-2">
                        @csrf
                        <button type="submit" class="btn-primary w-full py-3.5" @disabled(! $steps[2]['done'] || ! $steps[3]['done'])>Lanjut ke beranda</button>
                        <p class="mt-2 text-center text-xs muted">Kuesioner awal dan catatan tidur bisa dilanjutkan dari beranda sesuai jadwal.</p>
                    </form>
                </li>
            @endif
        </ol>

        <aside class="lg:sticky lg:top-24 lg:self-start">
            <h2 class="mb-4 font-bold">Jadwal pengukuran</h2>
            <ol class="space-y-4 border-l-2 border-lavender pl-5">
                @foreach (config('ritnova.phase_labels') as $phase => $label)
                    @php($w = $windows->get($phase))
                    <li class="relative">
                        <span class="absolute top-1 -left-[27px] h-3 w-3 rounded-full bg-primary ring-4 ring-canvas"></span>
                        <p class="font-semibold">{{ $phase }} <span class="font-normal muted">{{ $label }}</span></p>
                        <p class="text-sm muted num">{{ $w ? $w->opens_at->translatedFormat('d M').' – '.($w->closes_at?->translatedFormat('d M Y') ?? '…') : 'Ditetapkan peneliti' }}</p>
                    </li>
                @endforeach
            </ol>
            @if ($baselineDone)
                <div class="mt-6 rounded-[var(--radius-control)] bg-mint/50 p-4 text-sm">Pengukuran awal telah selesai. Terima kasih sudah menyelesaikan tahap persiapan.</div>
            @endif
        </aside>
    </div>
@endsection
