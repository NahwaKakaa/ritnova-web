@extends('layouts.admin', ['title' => 'Jadwal rilis'])

@php $dis = ! $canManage; @endphp

@section('content')
    <div class="mb-7 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        @include('admin.partials.page-head', ['heading' => 'Jadwal sesi, '.$cohort->name, 'sub' => 'Tanggal buka, batas opsional, dan prasyarat tiap sesi. Semua jam dalam WITA (Asia/Makassar) dan dihitung dari jam server.'])
        @if ($canManage && $cohort->starts_on)
            <form method="POST" action="{{ route('admin.schedule.regenerate') }}" data-confirm="Timpa semua jadwal dengan pola standar Senin/Rabu/Jumat 19.00 mulai {{ $cohort->starts_on->translatedFormat('j F Y') }}?">
                @csrf
                <button class="btn-secondary btn-sm"><x-icon name="calendar" class="h-4 w-4" />Buat ulang jadwal standar</button>
            </form>
        @endif
    </div>

    <form method="POST" action="{{ route('admin.schedule.update') }}">
        @csrf @method('PUT')

        <section class="card mb-6">
            <h2 class="mb-4 font-bold">Pengaturan kohort</h2>
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div>
                    <label class="label" for="name">Nama kohort</label>
                    <input id="name" name="name" value="{{ old('name', $cohort->name) }}" class="input py-2.5" @disabled($dis) required>
                </div>
                <div>
                    <label class="label" for="starts_on">Senin minggu pertama</label>
                    <input id="starts_on" name="starts_on" type="date" value="{{ old('starts_on', $cohort->starts_on?->toDateString()) }}" class="input py-2.5" @disabled($dis) required>
                </div>
                <div>
                    <label class="label" for="late_policy">Sesi terlambat</label>
                    <select id="late_policy" name="late_policy" class="input py-2.5" @disabled($dis)>
                        <option value="catch_up" @selected(old('late_policy', $cohort->late_policy) === 'catch_up')>Catch-up terkontrol</option>
                        <option value="strict" @selected(old('late_policy', $cohort->late_policy) === 'strict')>Strict window</option>
                    </select>
                </div>
                <div>
                    <label class="label" for="status">Status kohort</label>
                    <select id="status" name="status" class="input py-2.5" @disabled($dis)>
                        @foreach (['draft' => 'Draf', 'active' => 'Berjalan', 'finished' => 'Selesai'] as $k => $l)
                            <option value="{{ $k }}" @selected(old('status', $cohort->status) === $k)>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <p class="mt-4 rounded-xl bg-canvas px-4 py-3 text-sm muted ring-1 ring-ink/5">
                <b class="text-ink">Strict:</b> sesi kedaluwarsa setelah batas dan dicatat sebagai penyimpangan protokol.
                <b class="text-ink">Catch-up:</b> sesi tetap bisa dikejar berurutan; keterlambatan dicatat sebagai deviasi paparan.
            </p>
        </section>

        <section class="card overflow-hidden p-0">
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead><tr><th>Sesi</th><th>Prasyarat</th><th>Buka (WITA)</th><th>Batas (opsional)</th><th>Status</th></tr></thead>
                    <tbody>
                        @foreach ($schedules as $row)
                            @php
                                $released = $row->release_at->isPast();
                                $closed = $row->close_at?->isPast();
                            @endphp
                            <tr>
                                <td class="min-w-52">
                                    <span class="mr-2 font-bold num">{{ str_pad($row->learningSession->number, 2, '0', STR_PAD_LEFT) }}</span>
                                    <span class="text-ink/80">{{ \Illuminate\Support\Str::limit($row->learningSession->title, 28) }}</span>
                                </td>
                                <td class="min-w-40">
                                    <label class="sr-only" for="pre-{{ $row->id }}">Prasyarat sesi {{ $row->learningSession->number }}</label>
                                    <select id="pre-{{ $row->id }}" name="schedules[{{ $row->id }}][prerequisite_session_id]" class="input px-3 py-2 text-sm" @disabled($dis)>
                                        <option value="">{{ $row->learningSession->number === 1 ? 'T0 (tanpa sesi)' : 'Tanpa prasyarat' }}</option>
                                        @foreach ($sessions as $s)
                                            @continue($s->id === $row->learning_session_id)
                                            <option value="{{ $s->id }}" @selected((int) old("schedules.{$row->id}.prerequisite_session_id", $row->prerequisite_session_id) === $s->id)>Sesi {{ str_pad($s->number, 2, '0', STR_PAD_LEFT) }} selesai</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="min-w-52">
                                    <label class="sr-only" for="rel-{{ $row->id }}">Buka sesi {{ $row->learningSession->number }}</label>
                                    <input id="rel-{{ $row->id }}" type="datetime-local" name="schedules[{{ $row->id }}][release_at]" value="{{ old("schedules.{$row->id}.release_at", $row->release_at->format('Y-m-d\TH:i')) }}" class="input px-3 py-2 text-sm" @disabled($dis) required>
                                    <p class="mt-1 text-xs muted">{{ $row->release_at->translatedFormat('l') }}</p>
                                </td>
                                <td class="min-w-52">
                                    <label class="sr-only" for="close-{{ $row->id }}">Batas sesi {{ $row->learningSession->number }}</label>
                                    <input id="close-{{ $row->id }}" type="datetime-local" name="schedules[{{ $row->id }}][close_at]" value="{{ old("schedules.{$row->id}.close_at", $row->close_at?->format('Y-m-d\TH:i')) }}" class="input px-3 py-2 text-sm" @disabled($dis)>
                                    <p class="mt-1 text-xs muted">{{ $row->close_at?->translatedFormat('l') ?? 'Tanpa batas' }}</p>
                                </td>
                                <td>
                                    @if ($closed)<span class="pill-muted">Lewat batas</span>
                                    @elseif ($released)<span class="pill-done">Rilis</span>
                                    @else<span class="pill-wait">Jadwal</span>@endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        <div class="sticky bottom-4 z-10 mt-6 flex flex-col gap-3 rounded-[var(--radius-card)] bg-surface/95 p-4 shadow-[var(--shadow-lift)] ring-1 ring-ink/5 backdrop-blur sm:flex-row sm:items-center sm:justify-between" data-sticky-complete>
            <p class="text-sm muted">{{ $canManage ? 'Perubahan berlaku segera untuk semua peserta di kohort ini dan tercatat di audit log.' : 'Hanya admin yang dapat mengubah jadwal.' }}</p>
            @if ($canManage)<button class="btn-primary shrink-0">Simpan jadwal</button>@endif
        </div>
    </form>
@endsection
