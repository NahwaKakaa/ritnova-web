@extends('layouts.participant', ['title' => $title])

@section('content')
    <h1 class="mb-2 text-2xl font-bold">{{ $title }}</h1>
    <div class="card"><p class="text-ink/70">{{ $description }}</p><p class="mt-2 text-sm text-ink/50">Fitur ini sedang dikembangkan.</p></div>
@endsection
