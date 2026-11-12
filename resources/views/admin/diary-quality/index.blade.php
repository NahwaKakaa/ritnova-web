@extends('layouts.admin', ['title' => 'Kualitas diary'])

@php $tone = ['partial' => 'pill-warn', 'implausible' => 'pill-alert', 'revised' => 'pill-open']; @endphp

@section('content')
    <div class="mb-7">
        @include('admin.partials.page-head', ['heading' => 'Kualitas diary', 'sub' => 'Catatan yang perlu diperiksa sebelum analisis. Data mentah tidak diubah dari sini; setiap perubahan peserta tersimpan sebagai revisi.'])
    </div>

    <div class="mb-6 grid gap-4 sm:grid-cols-3">
        @foreach ($kinds as $k => $label)
            <a href="{{ route('admin.diary-quality.index', $kind === $k ? [] : ['jenis' => $k]) }}" @class(['card flex items-center justify-between transition hover:-translate-y-0.5', 'ring-2 ring-primary' => $kind === $k])>
                <div>
                    <p class="stat-label mb-2">{{ $label }}</p>
                    <p class="text-3xl font-bold num">{{ $counts[$k] ?? 0 }}</p>
                </div>
                <span class="{{ $tone[$k] }}">{{ $kind === $k ? 'Difilter' : 'Lihat' }}</span>
            </a>
        @endforeach
    </div>

    <div class="grid gap-6 xl:grid-cols-[1fr_20rem]">
        <section class="card overflow-hidden p-0">
            <div class="flex items-center justify-between px-5 pt-5 pb-3">
                <h2 class="font-bold">{{ $kind ? $kinds[$kind] : 'Semua temuan' }}</h2>
                @if ($kind)<a href="{{ route('admin.diary-quality.index') }}" class="text-sm font-medium text-brand hover:underline">Tampilkan semua</a>@endif
            </div>
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead><tr><th>Peserta</th><th>Malam</th><th>Jenis hari</th><th>Temuan</th><th class="text-right">Revisi</th></tr></thead>
                    <tbody>
                        @forelse ($issues as $row)
                            @php $d = $row['diary']; @endphp
                            <tr>
                                <td><a href="{{ route('admin.participants.show', $d->user_id) }}" class="font-semibold text-brand hover:underline">{{ $d->user->username }}</a></td>
                                <td class="whitespace-nowrap">{{ $d->diary_date->translatedFormat('D j M') }}</td>
                                <td>{{ $d->day_type === 'workday' ? 'Kuliah' : 'Bebas' }}</td>
                                <td><span class="{{ $tone[$row['kind']] }}">{{ $row['issue'] }}</span></td>
                                <td class="text-right num">{{ $d->revisions_count }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="py-12 text-center muted">Tidak ada temuan. Data diary terlihat bersih.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="card self-start">
            <h2 class="mb-1 font-bold">Malam terlewat</h2>
            <p class="mb-4 text-sm muted">7 malam terakhir, dari {{ $participantCount }} peserta aktif.</p>
            <ul class="space-y-2.5">
                @forelse ($missing as $r)
                    <li class="flex items-center gap-3">
                        <a href="{{ route('admin.participants.show', $r['user']) }}" class="w-20 shrink-0 text-sm font-semibold text-brand hover:underline">{{ $r['user']->username }}</a>
                        <div class="flex flex-1 gap-1" aria-hidden="true">
                            @foreach (range(1, 7) as $i)<span @class(['h-2 flex-1 rounded-full', 'bg-rose-deep/70' => $i <= $r['missing'], 'bg-ink/10' => $i > $r['missing']])></span>@endforeach
                        </div>
                        <span class="w-14 text-right text-xs muted num">{{ $r['missing'] }} malam</span>
                    </li>
                @empty
                    <li class="text-sm muted">Semua peserta mengisi 7 malam terakhir.</li>
                @endforelse
            </ul>
        </section>
    </div>
@endsection
