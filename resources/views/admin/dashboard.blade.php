@extends('layouts.admin', ['title' => 'Ringkasan Kohort'])

@section('content')
    <h1 class="mb-6 text-2xl font-bold">Ringkasan Kohort</h1>
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="card"><p class="text-sm text-ink/60">Peserta</p><p class="text-3xl font-bold">{{ $participantCount }}</p></div>
        <div class="card"><p class="text-sm text-ink/60">Consent</p><p class="text-3xl font-bold">{{ $consentCount }}</p></div>
        <div class="card"><p class="text-sm text-ink/60">Diary valid</p><p class="text-3xl font-bold">{{ $validDiaryCount }}</p></div>
        <div class="card"><p class="text-sm text-ink/60">Sesi selesai</p><p class="text-3xl font-bold">{{ $completedSessions }}</p></div>
    </div>
    <div class="card mt-4 text-sm">
        @foreach (config('ritnova.groups') as $key => $label)
            <span class="mr-6">{{ $label }}: <b>{{ $byGroup[$key] ?? 0 }}</b></span>
        @endforeach
    </div>
@endsection
