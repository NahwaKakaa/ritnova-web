@extends('layouts.admin', ['title' => 'Peserta '.$p->username])

@php
    $groups = config('ritnova.groups');
    $consent = $p->consents->sortByDesc('agreed_at')->first();
    $isIntervention = $p->study_group === 'intervention';
    $dur = function ($d) {
        if (! $d->sleep_onset || ! $d->sleep_offset) return null;
        $m = (int) $d->sleep_onset->diffInMinutes($d->sleep_offset);
        return intdiv($m, 60).'j '.str_pad($m % 60, 2, '0', STR_PAD_LEFT).'m';
    };
@endphp

@section('content')
    <a href="{{ route('admin.participants.index') }}" class="mb-5 inline-flex items-center gap-2 text-sm font-medium text-brand hover:underline"><x-icon name="arrow-left" class="h-4 w-4" />Semua peserta</a>

    <section class="panel-night mb-6 p-6 sm:p-8">
        <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <div class="mb-3 flex flex-wrap items-center gap-2">
                    @if ($p->study_group)<span class="pill bg-white/10 text-white">{{ $groups[$p->study_group] }}</span>@else<span class="pill bg-peach/80 text-night">Kelompok belum ditetapkan</span>@endif
                    @if ($p->status === 'withdrawn')<span class="pill bg-rose text-rose-deep">Mengundurkan diri</span>@else<span class="pill bg-[#123A31] text-[#7BE0B9]">Aktif</span>@endif
                </div>
                <h1 class="mb-1 text-4xl font-bold tracking-tight">{{ $p->username }}</h1>
                <p class="text-white/60">{{ $p->cohort?->name ?? 'Tanpa kohort' }}</p>
            </div>
            <dl class="grid grid-cols-2 gap-x-8 gap-y-3 text-sm sm:grid-cols-4">
                <div><dt class="text-white/50">Consent</dt><dd class="font-semibold">{{ $consent ? ($consent->withdrawn_at ? 'Ditarik '.$consent->withdrawn_at->translatedFormat('j M') : $consent->agreed_at->translatedFormat('j M Y')) : '—' }}</dd></div>
                <div><dt class="text-white/50">Persiapan</dt><dd class="font-semibold">{{ $p->onboarded_at?->translatedFormat('j M Y') ?? '—' }}</dd></div>
                <div><dt class="text-white/50">Login terakhir</dt><dd class="font-semibold">{{ $p->last_login_at?->diffForHumans() ?? '—' }}</dd></div>
                <div><dt class="text-white/50">Diary lengkap</dt><dd class="font-semibold num">{{ $diaries->where('status', 'complete')->count() }} malam</dd></div>
            </dl>
        </div>
    </section>

    @include('admin.partials.credentials')

    @if ($canManage)
        @error('delete')<div class="mb-4 rounded-[var(--radius-control)] bg-peach/60 px-4 py-3 text-sm text-warn-ink" role="alert">{{ $message }}</div>@enderror
        <section class="mb-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4" aria-label="Kelola peserta">
            <div class="card flex flex-col">
                <h2 class="mb-1 font-bold">Kode peserta</h2>
                <form method="POST" action="{{ route('admin.participants.code', $p) }}" class="mb-4" data-confirm="Ubah kode {{ $p->username }}? Peserta harus login dengan kode baru.">
                    @csrf @method('PATCH')
                    <p class="mb-3 text-sm muted">Perbaiki bila salah ketik. Data peserta tetap sama.</p>
                    <label class="sr-only" for="username">Kode baru</label>
                    <input id="username" name="username" value="{{ old('username', $p->username) }}" class="input mb-3 py-2.5 uppercase" maxlength="20" required>
                    <button class="btn-secondary btn-sm w-full"><x-icon name="pencil" class="h-4 w-4" />Simpan kode</button>
                </form>
                <div class="mt-auto border-t border-ink/5 pt-4">
                    @if ($researchData)
                        <p class="text-xs muted">Tidak bisa dihapus karena sudah ada {{ implode(', ', array_keys($researchData)) }}. Gunakan "Catat mengundurkan diri".</p>
                    @else
                        <form method="POST" action="{{ route('admin.participants.destroy', $p) }}" data-confirm="Hapus peserta {{ $p->username }} secara permanen? Tindakan ini tidak bisa dibatalkan.">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm w-full bg-rose text-rose-deep hover:brightness-95"><x-icon name="x" class="h-4 w-4" />Hapus peserta</button>
                        </form>
                        <p class="mt-2 text-xs muted">Hanya untuk akun yang belum punya data penelitian, misalnya akun uji atau salah impor.</p>
                    @endif
                </div>
            </div>
            <form method="POST" action="{{ route('admin.participants.group', $p) }}" class="card" data-confirm="Ubah kelompok {{ $p->username }}? Perubahan dicatat di audit log.">
                @csrf @method('PATCH')
                <h2 class="mb-1 font-bold">Kelompok</h2>
                <p class="mb-4 text-sm muted">Penetapan mengikuti hasil randomisasi protokol.</p>
                <label class="label" for="study_group">Kelompok</label>
                <select id="study_group" name="study_group" class="input mb-3 py-2.5">
                    @foreach ($groups as $k => $label)<option value="{{ $k }}" @selected($p->study_group === $k)>{{ $label }}</option>@endforeach
                </select>
                <label class="label" for="reason-group">Alasan</label>
                <input id="reason-group" name="reason" class="input mb-4 py-2.5" placeholder="Contoh: hasil randomisasi blok 3" required minlength="5">
                @if ($progress->isNotEmpty())<p class="mb-3 text-xs text-warn-ink">Peserta ini sudah punya aktivitas sesi. Pindah kelompok sekarang adalah penyimpangan protokol.</p>@endif
                <button class="btn-secondary btn-sm w-full">Simpan kelompok</button>
            </form>

            <form method="POST" action="{{ route('admin.participants.password', $p) }}" class="card flex flex-col" data-confirm="Buat kata sandi baru untuk {{ $p->username }}? Sesi login peserta akan diakhiri.">
                @csrf
                <h2 class="mb-1 font-bold">Kata sandi</h2>
                <p class="mb-4 text-sm muted">Buat kata sandi sementara bila peserta lupa. Kata sandi tampil sekali dan login lama diakhiri.</p>
                <button class="btn-secondary btn-sm mt-auto w-full"><x-icon name="key" class="h-4 w-4" />Buat kata sandi baru</button>
            </form>

            <form method="POST" action="{{ route('admin.participants.status', $p) }}" class="card flex flex-col" data-confirm="{{ $p->status === 'withdrawn' ? 'Aktifkan kembali '.$p->username.'?' : 'Catat '.$p->username.' mengundurkan diri? Peserta tidak bisa login lagi.' }}">
                @csrf @method('PATCH')
                <h2 class="mb-1 font-bold">Keikutsertaan</h2>
                @if ($p->status === 'withdrawn')
                    <p class="mb-4 text-sm muted">Peserta tercatat mengundurkan diri. Data yang sudah ada tetap disimpan.</p>
                    <input type="hidden" name="status" value="active">
                    <button class="btn-secondary btn-sm mt-auto w-full">Aktifkan kembali</button>
                @else
                    <p class="mb-3 text-sm muted">Data yang sudah terkumpul tetap disimpan; peserta tidak bisa login.</p>
                    <input type="hidden" name="status" value="withdrawn">
                    <label class="sr-only" for="reason-status">Alasan</label>
                    <input id="reason-status" name="reason" class="input mb-3 py-2.5" placeholder="Alasan pengunduran diri" required>
                    <button class="btn btn-sm mt-auto w-full bg-rose text-rose-deep hover:brightness-95">Catat mengundurkan diri</button>
                @endif
            </form>
        </section>
    @endif

    <div class="grid gap-6 xl:grid-cols-[1.25fr_1fr]">
        <div class="space-y-6">
            {{-- Sesi --}}
            <section class="card overflow-hidden p-0">
                <div class="flex items-center justify-between px-5 pt-5 pb-3">
                    <h2 class="font-bold">Paparan 12 sesi</h2>
                    @if ($isIntervention)<span class="text-sm muted num">{{ $progress->whereNotNull('completed_at')->count() }}/12 selesai</span>@endif
                </div>
                @if (! $isIntervention)
                    <p class="px-5 pb-5 text-sm muted">Kelompok pembanding tidak menerima modul edukasi.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="data-table">
                            <thead><tr><th>Sesi</th><th>Rilis</th><th>Mulai</th><th class="text-right">Aktif</th><th>Status</th></tr></thead>
                            <tbody>
                                @foreach ($sessions as $s)
                                    @php
                                        $sch = $schedules->get($s->id);
                                        $pr = $progress->get($s->id);
                                        $released = $sch && $sch->release_at->isPast();
                                    @endphp
                                    <tr>
                                        <td><span class="mr-2 font-semibold num">{{ str_pad($s->number, 2, '0', STR_PAD_LEFT) }}</span><span class="text-ink/80">{{ \Illuminate\Support\Str::limit($s->title, 34) }}</span></td>
                                        <td class="whitespace-nowrap text-ink/70 num">{{ $sch?->release_at->translatedFormat('D j M, H.i') ?? '—' }}</td>
                                        <td class="whitespace-nowrap num">
                                            @if ($pr?->started_at && $sch)
                                                @php $delay = (int) $sch->release_at->diffInMinutes($pr->started_at); @endphp
                                                <span data-tip="Jeda dari rilis">{{ $delay < 60 ? '+'.$delay.' mnt' : ($delay < 1440 ? '+'.intdiv($delay, 60).' jam' : '+'.intdiv($delay, 1440).' hari') }}</span>
                                            @else<span class="muted">—</span>@endif
                                        </td>
                                        <td class="text-right whitespace-nowrap num">{{ $pr ? round($pr->active_seconds / 60).' mnt' : '—' }}</td>
                                        <td class="whitespace-nowrap">
                                            @if ($pr?->completed_at)<span class="pill-done">Selesai</span>
                                            @elseif ($pr?->started_at)<span class="pill-open">Berjalan {{ round($pr->media_completion_pct * 100) }}%</span>
                                            @elseif ($released)<span class="pill-warn">Belum mulai</span>
                                            @else<span class="pill-muted">Belum rilis</span>@endif
                                            @if ($pr?->is_late)<span class="pill-alert ml-1">Terlambat</span>@endif
                                            @if ($pr?->protocol_deviation)<span class="pill-alert ml-1">Deviasi</span>@endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>

            {{-- Diary --}}
            <section class="card overflow-hidden p-0">
                <div class="px-5 pt-5 pb-3"><h2 class="font-bold">Catatan tidur terbaru</h2></div>
                @if ($diaries->isEmpty())
                    <p class="px-5 pb-5 text-sm muted">Belum ada catatan tidur.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="data-table">
                            <thead><tr><th>Malam</th><th>Jenis hari</th><th>Tidur</th><th>Bangun</th><th class="text-right">Durasi</th><th>Status</th></tr></thead>
                            <tbody>
                                @foreach ($diaries as $d)
                                    <tr>
                                        <td class="whitespace-nowrap">{{ $d->diary_date->translatedFormat('D j M') }}</td>
                                        <td><span @class(['pill', 'bg-sky/60 text-ink/80' => $d->day_type === 'workday', 'bg-peach/70 text-warn-ink' => $d->day_type === 'free_day'])>{{ $d->day_type === 'workday' ? 'Kuliah' : 'Bebas' }}</span></td>
                                        <td class="num">{{ $d->sleep_onset?->format('H.i') ?? '—' }}</td>
                                        <td class="num">{{ $d->sleep_offset?->format('H.i') ?? '—' }}</td>
                                        <td class="text-right num">{{ $dur($d) ?? '—' }}</td>
                                        <td class="whitespace-nowrap">
                                            @if ($d->status === 'complete')<span class="pill-done">Lengkap</span>@else<span class="pill-warn">Belum lengkap</span>@endif
                                            @if ($d->revisions_count)<span class="pill-muted ml-1" data-tip="Riwayat revisi disimpan">{{ $d->revisions_count }} revisi</span>@endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>
        </div>

        <div class="space-y-6">
            <section class="card">
                <h2 class="mb-4 font-bold">Hasil SJL tersimpan</h2>
                @forelse ($sjl as $r)
                    <div class="flex items-center justify-between border-b border-ink/5 py-2.5 text-sm last:border-0">
                        <span><span class="font-semibold">{{ $r->phase ?? 'Berjalan' }}</span> <span class="muted num">{{ $r->period_start->translatedFormat('j M') }}–{{ $r->period_end->translatedFormat('j M') }}</span></span>
                        <span class="font-semibold num">{{ $r->sjl_minutes === null ? 'Data belum cukup' : intdiv($r->sjl_minutes, 60).' jam '.($r->sjl_minutes % 60).' menit' }}</span>
                    </div>
                @empty
                    <p class="text-sm muted">Belum ada hasil SJL per fase.</p>
                @endforelse
            </section>

            <section class="card">
                <h2 class="mb-4 font-bold">Pengukuran</h2>
                @forelse ($assessments as $a)
                    <div class="flex items-center justify-between border-b border-ink/5 py-2.5 text-sm last:border-0">
                        <span><span class="font-semibold">{{ $a->phase }}</span> <span class="muted">{{ $a->instrument?->code }}</span></span>
                        <span>@if ($a->submitted_at)<span class="pill-done">{{ $a->submitted_at->translatedFormat('j M, H.i') }}</span>@else<span class="pill-warn">Belum dikirim</span>@endif</span>
                    </div>
                @empty
                    <p class="text-sm muted">Belum ada instrumen yang diisi.</p>
                @endforelse
            </section>

            @if ($isIntervention)
                <section class="card">
                    <h2 class="mb-4 font-bold">Target & refleksi</h2>
                    @forelse ($goals as $g)
                        <div class="mb-3 rounded-2xl bg-canvas p-3 text-sm ring-1 ring-ink/5">
                            <p class="mb-1 text-xs muted">Minggu {{ $g->week }}</p>
                            <p class="font-medium">{{ $g->target }}</p>
                        </div>
                    @empty
                        <p class="mb-3 text-sm muted">Belum ada target.</p>
                    @endforelse
                    <p class="text-sm muted">{{ $reflections->count() }} refleksi mingguan terisi.</p>
                </section>
            @endif

            <section class="card">
                <h2 class="mb-4 font-bold">Jejak audit</h2>
                <ol class="space-y-3">
                    @forelse ($logs as $log)
                        <li class="flex gap-3 text-sm">
                            <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-secondary"></span>
                            <div class="min-w-0">
                                <p><span class="font-medium">{{ \App\Support\AuditLabel::action($log) }}</span> <span class="muted">oleh {{ $log->user?->username ?? 'sistem' }}</span></p>
                                <p class="text-xs muted num">{{ $log->created_at->translatedFormat('j M Y, H.i') }}</p>
                            </div>
                        </li>
                    @empty
                        <li class="text-sm muted">Belum ada catatan.</li>
                    @endforelse
                </ol>
            </section>
        </div>
    </div>
@endsection
