@extends('layouts.participant', ['title' => 'Catat Tidurmu'])

@section('content')
    <div class="card mx-auto max-w-lg">
        <h1 class="mb-4 text-xl font-bold">Catat Tidurmu</h1>
        <form method="POST" action="{{ $diary->exists ? route('diary.update', $diary) : route('diary.store') }}" class="space-y-4">
            @csrf
            @if ($diary->exists) @method('PUT') @endif

            <div>
                <label class="label" for="diary_date">Tanggal tidur (malam)</label>
                <input class="input" type="date" id="diary_date" name="diary_date" value="{{ old('diary_date', $diary->diary_date?->toDateString()) }}" max="{{ now()->toDateString() }}" required>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="label" for="sleep_onset_time">Mulai benar-benar tidur</label>
                    <input class="input" type="time" id="sleep_onset_time" name="sleep_onset_time" value="{{ old('sleep_onset_time', $diary->sleep_onset?->format('H:i')) }}">
                </div>
                <div>
                    <label class="label" for="sleep_offset_time">Bangun terakhir</label>
                    <input class="input" type="time" id="sleep_offset_time" name="sleep_offset_time" value="{{ old('sleep_offset_time', $diary->sleep_offset?->format('H:i')) }}">
                </div>
            </div>
            @error('sleep_offset_time') <p class="text-sm text-red-600">{{ $message }}</p> @enderror

            <div>
                <span class="label">Hari setelah bangun</span>
                <div class="grid grid-cols-2 gap-3">
                    @foreach (['workday' => 'Hari kuliah', 'free_day' => 'Hari bebas'] as $value => $label)
                        <label class="flex items-center gap-2 rounded-xl border border-black/10 bg-white px-4 py-3 text-sm">
                            <input type="radio" name="day_type" value="{{ $value }}" @checked(old('day_type', $diary->day_type) === $value) required> {{ $label }}
                        </label>
                    @endforeach
                </div>
            </div>

            <details class="rounded-xl bg-canvas p-3 text-sm">
                <summary class="cursor-pointer font-medium">Opsional</summary>
                <div class="mt-3 space-y-3">
                    <div>
                        <label class="label" for="bed_time">Masuk tempat tidur</label>
                        <input class="input" type="time" id="bed_time" name="bed_time" value="{{ old('bed_time', $diary->bed_at?->format('H:i')) }}">
                    </div>
                    <label class="flex items-center gap-2"><input type="hidden" name="used_alarm" value="0"><input type="checkbox" name="used_alarm" value="1" @checked(old('used_alarm', $diary->used_alarm))> Bangun dengan alarm</label>
                    <div>
                        <label class="label" for="nap_minutes">Tidur siang (menit)</label>
                        <input class="input" type="number" min="0" max="600" id="nap_minutes" name="nap_minutes" value="{{ old('nap_minutes', $diary->nap_minutes) }}">
                    </div>
                    <div>
                        <label class="label" for="notes">Catatan / gangguan</label>
                        <textarea class="input" id="notes" name="notes" rows="2">{{ old('notes', $diary->notes) }}</textarea>
                    </div>
                </div>
            </details>

            <button class="btn-primary w-full">Simpan catatan</button>
            <p class="text-xs text-ink/50">Koreksi bisa dilakukan kapan saja; riwayat perubahan disimpan.</p>
        </form>
    </div>
@endsection
