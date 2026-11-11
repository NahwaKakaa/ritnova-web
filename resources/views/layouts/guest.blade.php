@extends('layouts.base')

@section('body')
    <main class="mx-auto flex min-h-screen max-w-md flex-col justify-center px-5 py-10">
        <a href="{{ route('landing') }}" class="mb-8 text-2xl font-bold text-primary">RITNOVA ☾</a>
        @yield('content')
    </main>
@endsection
