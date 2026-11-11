@extends('layouts.participant', ['title' => 'Beranda'])

@section('content')
    <h1 class="mb-1 text-2xl font-bold">Halo, {{ $user->username }} ☾</h1>
    <p class="mb-6 text-ink/60">Ayo kenali pola tidurmu.</p>

    @foreach ($notifications as $n)
        <a href="{{ $n->data['url'] ?? '#' }}" class="mb-3 block rounded-xl bg-peach/60 px-4 py-3 text-sm">{{ $n->data['message'] }}</a>
    @endforeach

    <div class="grid gap-4 md:grid-cols-2">
        <div class="card">
            <p class="mb-2 text-sm font-semibold text-secondary">Misi hari ini</p>
            @if ($diaryFilledLastNight)
                <p class="mb-4">Catatan tidur semalam sudah terisi. 👍</p>
            @else
                <p class="mb-4">Isi catatan tidur semalam.</p>
                <a href="{{ route('diary.create') }}" class="btn-primary">Catat Sekarang</a>
            @endif
        </div>

        @if ($user->isIntervention())
            <div class="card">
                <p class="mb-2 text-sm font-semibold text-secondary">Materi</p>
                <p class="mb-1 text-3xl font-bold">{{ $completedCount }}/12</p>
                <p class="mb-4 text-sm text-ink/60">sesi selesai</p>
                @if ($nextSession)
                    <a href="{{ route('learning.show', $nextSession['session']) }}" class="btn-secondary">Lanjutkan Sesi {{ str_pad($nextSession['session']->number, 2, '0', STR_PAD_LEFT) }}</a>
                @endif
            </div>
        @endif

        <div class="card">
            <p class="mb-2 text-sm font-semibold text-secondary">Diary valid</p>
            <p class="text-3xl font-bold">{{ $validDiaryCount }} <span class="text-base font-normal text-ink/60">malam</span></p>
        </div>

        <div class="card">
            <p class="mb-2 text-sm font-semibold text-secondary">Pengukuran</p>
            <a href="{{ route('assessments.index') }}" class="text-primary underline">Lihat jadwal T0/T1/T2</a>
        </div>
    </div>
@endsection
