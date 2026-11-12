@extends('layouts.base', ['title' => $code.' · '.$heading])

@section('body')
    <main class="flex min-h-screen items-center justify-center px-5 py-12">
        <div class="panel-night w-full max-w-lg p-8 text-center sm:p-10">
            <div class="mx-auto mb-6 flex justify-center"><x-logo light /></div>
            <p class="mb-2 text-sm font-semibold text-[#BFB4FF] num">{{ $code }}</p>
            <h1 class="mb-3 text-3xl leading-tight font-bold">{{ $heading }}</h1>
            <p class="mb-8 text-white/65">{{ $message }}</p>
            <div class="flex flex-col justify-center gap-3 sm:flex-row">
                @auth
                    <a href="{{ auth()->user()->isStaff() ? route('admin.dashboard') : route('dashboard') }}" class="btn-on-night">{{ auth()->user()->isStaff() ? 'Ke panel peneliti' : 'Ke beranda' }}</a>
                    <form method="POST" action="{{ route('logout') }}">@csrf<button class="btn w-full bg-white/10 text-white hover:bg-white/15">Keluar & ganti akun</button></form>
                @else
                    <a href="{{ route('login') }}" class="btn-on-night">Masuk</a>
                    <a href="{{ url('/') }}" class="btn bg-white/10 text-white hover:bg-white/15">Halaman utama</a>
                @endauth
            </div>
        </div>
    </main>
@endsection
