@extends('layouts.participant', ['title' => 'Progres'])

@section('content')
    {{-- Layar 18: progres 12 sesi + alasan status --}}
    <h1 class="mb-1 text-2xl font-bold">Perjalanan Ritme Tidurmu</h1>
    <p class="mb-3 text-ink/60">{{ $completedCount }} dari 12 sesi selesai{{ $week ? ' · Minggu '.$week : '' }}</p>
    <div class="mb-6 flex items-center gap-3">
        <div class="h-2.5 flex-1 rounded-full bg-lavender"><div class="h-2.5 rounded-full bg-primary" style="width: {{ round($completedCount / 12 * 100) }}%"></div></div>
        <span class="text-sm font-semibold text-primary">{{ round($completedCount / 12 * 100) }}%</span>
    </div>

    <div class="mb-10 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
        @foreach ($sessions as $row)
            <div class="card p-4">
                <p class="text-xs font-semibold text-ink/50">SESI {{ str_pad($row['session']->number, 2, '0', STR_PAD_LEFT) }}</p>
                <p class="mb-2 text-sm font-semibold">{{ $row['session']->title }}</p>
                @include('participant.learning.partials.status-badge', ['row' => $row])
                @if ($row['decision']->canStart())
                    <a href="{{ route('learning.show', $row['session']) }}" class="mt-2 block text-sm font-semibold text-primary">Mulai →</a>
                @endif
            </div>
        @endforeach
    </div>

    {{-- Layar 14: Insights --}}
    <h2 class="mb-1 text-xl font-bold">Perkembangan Social Jetlag</h2>
    <p class="mb-4 text-sm text-ink/60">Dihitung dari catatan tidurmu pada setiap periode pengukuran.</p>

    <div class="card mb-4">
        @php($max = max(180, collect($insights)->max('minutes') ?? 0))
        <div class="flex h-56 items-end gap-6 border-b border-l border-black/10 px-4 pt-4" role="img" aria-label="Grafik SJL per periode pengukuran (jam)">
            @foreach ($insights as $phase => $item)
                <div class="flex flex-1 flex-col items-center justify-end gap-2">
                    @if ($item['minutes'] !== null)
                        <span class="text-sm font-semibold text-primary">{{ number_format($item['minutes'] / 60, 1, ',', '') }} j</span>
                        <div class="w-full max-w-16 rounded-t-lg bg-primary" style="height: {{ max(4, round($item['minutes'] / $max * 160)) }}px"></div>
                    @else
                        <span class="text-xs text-ink/40">belum cukup</span>
                        <div class="w-full max-w-16 rounded-t-lg border-2 border-dashed border-black/15" style="height: 24px"></div>
                    @endif
                </div>
            @endforeach
        </div>
        <div class="flex gap-6 px-4 pt-2 text-center text-sm">
            @foreach ($insights as $phase => $item)
                <div class="flex-1">
                    <p class="font-semibold">{{ $phase }}</p>
                    <p class="text-xs text-ink/50">{{ $item['range'] ?? 'belum dijadwalkan' }}</p>
                    <p class="text-xs text-ink/50">{{ $item['nights'] }} malam valid</p>
                </div>
            @endforeach
        </div>
        <p class="mt-3 text-xs text-ink/50">Sumbu: jam. Nilai "belum cukup" berarti catatan hari kuliah/bebas belum memenuhi syarat — bukan SJL nol.</p>
    </div>

    <p class="text-xs text-ink/50">Perubahan dipengaruhi banyak faktor; grafik ini tidak menyatakan kondisi medis.</p>
@endsection
