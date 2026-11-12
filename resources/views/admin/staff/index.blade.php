@extends('layouts.admin', ['title' => 'Akses tim'])

@section('content')
    <div class="mb-7">
        @include('admin.partials.page-head', ['heading' => 'Akses tim', 'sub' => 'Peneliti dapat melihat data, mengelola konten sesi, dan mengekspor. Admin juga dapat mengubah peserta, kelompok, jadwal, dan akun tim.'])
    </div>

    @include('admin.partials.credentials')

    <div class="grid gap-6 xl:grid-cols-[1fr_22rem]">
        <section class="card overflow-hidden p-0">
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead><tr><th>Akun</th><th>Peran</th><th>Login terakhir</th><th>Status</th><th class="text-right">Aksi</th></tr></thead>
                    <tbody>
                        @foreach ($staff as $s)
                            <tr>
                                <td><span class="font-semibold">{{ $s->username }}</span><span class="block text-xs muted">{{ $s->name }}</span></td>
                                <td><span @class(['pill', 'bg-lavender text-brand' => $s->role === 'admin', 'bg-sky/60 text-ink/80' => $s->role !== 'admin'])>{{ $s->role === 'admin' ? 'Admin' : 'Peneliti' }}</span></td>
                                <td class="whitespace-nowrap text-ink/70">{{ $s->last_login_at?->diffForHumans() ?? 'Belum pernah' }}</td>
                                <td>@if ($s->status === 'active')<span class="pill-done">Aktif</span>@else<span class="pill-muted">Nonaktif</span>@endif</td>
                                <td class="text-right whitespace-nowrap">
                                    <form method="POST" action="{{ route('admin.staff.password', $s) }}" class="inline" data-confirm-title="Buat kata sandi baru?" data-confirm="Kata sandi lama {{ $s->username }} tidak berlaku lagi. Kata sandi baru tampil sekali." data-confirm-ok="Buat kata sandi">
                                        @csrf
                                        <button class="btn-ghost btn-sm" data-tip="Buat kata sandi baru"><x-icon name="key" class="h-4 w-4" /><span class="sr-only">Kata sandi baru</span></button>
                                    </form>
                                    @unless ($s->is(auth()->user()))
                                        <form method="POST" action="{{ route('admin.staff.status', $s) }}" class="inline" @if ($s->status === 'active') data-confirm-tone="danger" data-confirm-title="Nonaktifkan {{ $s->username }}?" data-confirm="Akun ini tidak bisa login lagi sampai diaktifkan kembali." data-confirm-ok="Nonaktifkan" @else data-confirm-title="Aktifkan {{ $s->username }}?" data-confirm="Akun ini bisa login kembali ke panel peneliti." data-confirm-ok="Aktifkan" @endif>
                                            @csrf @method('PATCH')
                                            <button class="btn-ghost btn-sm">{{ $s->status === 'active' ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                                        </form>
                                    @endunless
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        <form method="POST" action="{{ route('admin.staff.store') }}" class="card space-y-4 self-start">
            @csrf
            <h2 class="font-bold">Tambah akun tim</h2>
            <div>
                <label class="label" for="name">Nama</label>
                <input id="name" name="name" value="{{ old('name') }}" class="input py-2.5" required>
            </div>
            <div>
                <label class="label" for="username">Username</label>
                <input id="username" name="username" value="{{ old('username') }}" class="input py-2.5" autocomplete="off" required>
            </div>
            <fieldset>
                <legend class="label">Peran</legend>
                <div class="grid grid-cols-2 gap-2">
                    <label class="choice"><input type="radio" name="role" value="researcher" class="accent-primary" @checked(old('role', 'researcher') === 'researcher')>Peneliti</label>
                    <label class="choice"><input type="radio" name="role" value="admin" class="accent-primary" @checked(old('role') === 'admin')>Admin</label>
                </div>
            </fieldset>
            <button class="btn-primary w-full"><x-icon name="plus" class="h-4 w-4" />Buat akun</button>
            <p class="text-xs muted">Kata sandi sementara tampil sekali setelah akun dibuat.</p>
        </form>
    </div>
@endsection
