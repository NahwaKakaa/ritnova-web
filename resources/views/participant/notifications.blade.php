@extends('layouts.participant', ['title' => 'Notifikasi'])

@section('content')
    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-2xl font-bold">Notifikasi</h1>
        @if (auth()->user()->unreadNotifications()->exists())
            <form method="POST" action="{{ route('notifications.read') }}">@csrf<button class="text-sm text-primary">Tandai semua dibaca</button></form>
        @endif
    </div>

    <div class="space-y-2">
        @forelse ($notifications as $n)
            <a href="{{ $n->data['url'] ?? '#' }}" @class(['card flex items-center justify-between gap-3 p-4', 'ring-2 ring-primary/30' => ! $n->read_at])>
                <span>{{ $n->data['message'] ?? '' }}</span>
                <span class="shrink-0 text-xs text-ink/50">{{ $n->created_at->diffForHumans() }}</span>
            </a>
        @empty
            <p class="text-ink/60">Belum ada notifikasi.</p>
        @endforelse
    </div>
    <div class="mt-4">{{ $notifications->links() }}</div>
@endsection
