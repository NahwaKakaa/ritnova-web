@extends('layouts.admin', ['title' => 'Skrining & rekrutmen'])

@section('content')
    <div class="mb-7">
        @include('admin.partials.page-head', ['heading' => 'Skrining & rekrutmen', 'sub' => 'Hasil skrining kelayakan (Lampiran 02) dan rekap alur rekrutmen per kelas. Hasil skrining hanya penanda untuk petugas; peserta tetap lanjut ke kuesioner awal.'])
    </div>

    <section class="card mb-6 overflow-hidden p-0">
        <div class="px-5 pt-5 pb-3">
            <h2 class="font-bold">Rekap alur rekrutmen</h2>
            <p class="text-sm muted">Dihitung per kelas dari profil dasar peserta.@if ($withoutProfile) {{ $withoutProfile }} akun belum mengisi profil sehingga belum masuk hitungan kelas.@endif</p>
        </div>
        <div class="overflow-x-auto">
            <table class="data-table">
                <thead><tr><th>Tahap</th>@foreach ($classes as $c)<th class="text-right">Kelas {{ $c }}</th>@endforeach<th class="text-right">Total</th></tr></thead>
                <tbody>
                    @foreach ($recap as [$label, $row])
                        <tr>
                            <td>{{ $label }}</td>
                            @foreach ($classes as $c)<td class="text-right num">{{ $row[$c] }}</td>@endforeach
                            <td class="text-right font-semibold num">{{ $row['total'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    <section class="card overflow-hidden p-0">
        <div class="px-5 pt-5 pb-3">
            <h2 class="font-bold">Hasil skrining per peserta</h2>
            <p class="text-sm muted">Yang perlu ditindaklanjuti tampil lebih dulu. Buka kode peserta untuk melihat jawaban lengkap dan kontak.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="data-table">
                <thead><tr><th>Peserta</th><th>Kelas</th><th class="text-right">Usia</th><th>Hasil</th><th>Catatan</th><th>Dikirim</th></tr></thead>
                <tbody>
                    @forelse ($rows as $u)
                        <tr>
                            <td><a href="{{ route('admin.participants.show', $u) }}" class="font-semibold text-brand hover:underline">{{ $u->username }}</a></td>
                            <td>{{ $u->profile?->class_group ?? '—' }}</td>
                            <td class="text-right num">{{ $u->profile?->ageOn(now()) ?? '—' }}</td>
                            <td>
                                @if ($u->screening)<span class="{{ $u->screening->resultClass() }}">{{ $u->screening->resultLabel() }}</span>
                                @elseif ($u->profile)<span class="pill-muted">Belum skrining</span>
                                @else<span class="pill-muted">Belum isi profil</span>@endif
                            </td>
                            <td class="text-sm">{{ $u->screening?->flagSummary() ?: '—' }}</td>
                            <td class="whitespace-nowrap text-sm muted">{{ $u->screening?->submitted_at->translatedFormat('j M Y') ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="py-8 text-center muted">Belum ada peserta di kohort ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
