@extends('layouts.participant', ['title' => 'Notifikasi'])

@section('content')
    <div class="mx-auto max-w-2xl">
        <div class="mb-8 flex items-end justify-between gap-4">
            <h1 class="page-title">Notifikasi</h1>
            @if (auth()->user()->unreadNotifications()->exists())
                <form method="POST" action="{{ route('notifications.read') }}">@csrf<button type="submit" class="btn-ghost btn-sm">Tandai semua dibaca</button></form>
            @endif
        </div>

        @if ($notifications->isEmpty())
            <div class="card py-12 text-center">
                <span class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-2xl bg-lavender text-primary"><x-icon name="bell" /></span>
                <p class="font-semibold">Belum ada notifikasi</p>
                <p class="mt-1 text-sm muted">Kamu akan diberi tahu saat sesi baru dibuka.</p>
            </div>
        @else
            <div class="card overflow-hidden p-0">
                @foreach ($notifications as $n)
                    <a href="{{ $n->data['url'] ?? '#' }}" class="flex items-center gap-4 border-b border-night/5 px-5 py-4 last:border-0 hover:bg-canvas">
                        <span @class(['flex h-10 w-10 shrink-0 items-center justify-center rounded-xl', 'bg-primary text-white' => ! $n->read_at, 'bg-lavender text-primary' => $n->read_at])><x-icon name="book" class="h-4 w-4" /></span>
                        <span @class(['flex-1', 'font-semibold' => ! $n->read_at])>{{ $n->data['message'] ?? '' }}</span>
                        <span class="shrink-0 text-xs muted">{{ $n->created_at->diffForHumans() }}</span>
                    </a>
                @endforeach
            </div>
            <div class="mt-4">{{ $notifications->links() }}</div>
        @endif
    </div>
@endsection
