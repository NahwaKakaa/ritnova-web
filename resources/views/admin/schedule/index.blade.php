@extends('layouts.admin', ['title' => 'Jadwal Sesi'])

@section('content')
    <h1 class="mb-6 text-2xl font-bold">Jadwal Sesi</h1>
    @foreach ($cohorts as $cohort)
        <div class="card mb-6 overflow-x-auto p-0">
            <div class="p-4"><b>{{ $cohort->name }}</b> · {{ $cohort->timezone }} · kebijakan: {{ $cohort->late_policy }}</div>
            <table class="w-full text-left text-sm">
                <thead class="bg-canvas text-ink/60"><tr><th class="p-3">Sesi</th><th class="p-3">Prasyarat</th><th class="p-3">Buka (WITA)</th><th class="p-3">Batas</th></tr></thead>
                <tbody>
                    @foreach ($cohort->schedules->sortBy('learningSession.number') as $s)
                        <tr class="border-t border-black/5"><td class="p-3">{{ str_pad($s->learningSession->number, 2, '0', STR_PAD_LEFT) }} · {{ $s->learningSession->title }}</td><td class="p-3">{{ $s->prerequisite ? 'Sesi '.str_pad($s->prerequisite->number, 2, '0', STR_PAD_LEFT) : 'T0' }}</td><td class="p-3">{{ $s->release_at->translatedFormat('D d/m H.i') }}</td><td class="p-3">{{ $s->close_at?->translatedFormat('D d/m H.i') ?? '-' }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endforeach
@endsection
