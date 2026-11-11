@extends('layouts.participant', ['title' => 'Catat tidur'])

@section('content')
    <div class="mx-auto max-w-xl">
        <a href="{{ route('diary.index') }}" class="btn-ghost btn-sm mb-4 -ml-3.5"><x-icon name="arrow-left" class="h-4 w-4" />Catatan tidur</a>
        <h1 class="page-title mb-2">{{ $diary->exists ? 'Ubah catatan tidur' : 'Catat tidurmu' }}</h1>
        <p class="mb-8 muted">Isi perkiraan terbaikmu. Tidur lewat tengah malam dihitung otomatis.</p>

        <form method="POST" action="{{ $diary->exists ? route('diary.update', $diary) : route('diary.store') }}" class="space-y-6" data-diary-form>
            @csrf
            @if ($diary->exists) @method('PUT') @endif

            <div class="card space-y-5 sm:p-6">
                <div>
                    <label class="label" for="diary_date">Malam tanggal</label>
                    <input class="input" type="date" id="diary_date" name="diary_date" value="{{ old('diary_date', $diary->diary_date?->toDateString()) }}" max="{{ now()->toDateString() }}" required>
                </div>

                <div class="grid grid-cols-2 items-end gap-3">
                    <div>
                        <label class="label" for="sleep_onset_time">Mulai benar-benar tidur</label>
                        <input class="input py-4 text-center text-2xl font-bold" type="time" id="sleep_onset_time" name="sleep_onset_time" value="{{ old('sleep_onset_time', $diary->sleep_onset?->format('H:i')) }}">
                    </div>
                    <div>
                        <label class="label" for="sleep_offset_time">Bangun terakhir</label>
                        <input class="input py-4 text-center text-2xl font-bold" type="time" id="sleep_offset_time" name="sleep_offset_time" value="{{ old('sleep_offset_time', $diary->sleep_offset?->format('H:i')) }}">
                    </div>
                </div>
                {{-- Garis waktu 18.00 sampai 12.00: geser bulan dan matahari, jam di atas ikut berubah --}}
                <div class="hidden rounded-2xl bg-night px-4 pt-4 pb-3 text-white" data-timeline>
                    <p class="mb-4 text-xs text-white/60">Atau geser penandanya di garis waktu</p>
                    <div class="relative mx-3 h-12" data-timeline-track>
                        <span class="absolute inset-x-0 top-1/2 h-2 -translate-y-1/2 rounded-full bg-white/10"></span>
                        <span class="absolute top-1/2 h-2 -translate-y-1/2 rounded-full bg-gradient-to-r from-secondary to-[#BCE4FF]" data-timeline-fill></span>
                        <button type="button" class="timeline-handle absolute top-1/2 flex h-10 w-10 -translate-x-1/2 -translate-y-1/2 items-center justify-center rounded-full bg-secondary text-white shadow-lg" role="slider" aria-label="Mulai tidur" aria-valuemin="0" aria-valuemax="1080" data-handle="onset"><x-icon name="moon" class="h-5 w-5" /></button>
                        <button type="button" class="timeline-handle absolute top-1/2 flex h-10 w-10 -translate-x-1/2 -translate-y-1/2 items-center justify-center rounded-full bg-[#FFDAC3] text-night shadow-lg" role="slider" aria-label="Bangun" aria-valuemin="0" aria-valuemax="1080" data-handle="offset"><x-icon name="sun" class="h-5 w-5" /></button>
                    </div>
                    <div class="mx-3 mt-1 flex justify-between text-[11px] text-white/45 num" aria-hidden="true">
                        @foreach (['18', '21', '00', '03', '06', '09', '12'] as $h)
                            <span class="w-0 text-center whitespace-nowrap"><span class="-ml-3 inline-block w-6">{{ $h }}</span></span>
                        @endforeach
                    </div>
                </div>
                <p class="-mt-2 text-sm muted" data-duration aria-live="polite"></p>
                @error('sleep_offset_time') <p class="flex items-center gap-1.5 text-sm text-rose-deep"><x-icon name="alert" class="h-4 w-4" />{{ $message }}</p> @enderror

                <fieldset>
                    <legend class="label">Hari setelah bangun</legend>
                    <div class="grid grid-cols-2 gap-3">
                        @foreach (['workday' => ['Hari kuliah', 'Ada jadwal atau kewajiban pagi'], 'free_day' => ['Hari bebas', 'Bisa bangun tanpa kewajiban']] as $value => [$label, $hint])
                            <label class="choice flex-col items-start gap-0.5">
                                <span class="flex items-center gap-2"><input type="radio" name="day_type" value="{{ $value }}" class="h-4 w-4 accent-primary" @checked(old('day_type', $diary->day_type) === $value) required>{{ $label }}</span>
                                <span class="pl-6 text-xs font-normal muted">{{ $hint }}</span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>
            </div>

            <details class="group card sm:p-6" @if ($diary->bed_at || $diary->nap_minutes || $diary->notes) open @endif>
                <summary class="flex cursor-pointer list-none items-center justify-between font-semibold">Detail tambahan (opsional)<x-icon name="plus" class="h-5 w-5 text-brand transition group-open:rotate-45" /></summary>
                <div class="mt-5 space-y-4">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="label" for="bed_time">Masuk tempat tidur</label>
                            <input class="input" type="time" id="bed_time" name="bed_time" value="{{ old('bed_time', $diary->bed_at?->format('H:i')) }}">
                        </div>
                        <div>
                            <label class="label" for="nap_minutes">Tidur siang (menit)</label>
                            <input class="input" type="number" min="0" max="600" id="nap_minutes" name="nap_minutes" value="{{ old('nap_minutes', $diary->nap_minutes) }}">
                        </div>
                    </div>
                    <label class="choice"><input type="hidden" name="used_alarm" value="0"><input type="checkbox" name="used_alarm" value="1" class="h-4 w-4 accent-primary" @checked(old('used_alarm', $diary->used_alarm))>Bangun dengan alarm</label>
                    <div>
                        <label class="label" for="notes">Catatan atau gangguan</label>
                        <textarea class="input" id="notes" name="notes" rows="2" placeholder="Misalnya: terbangun karena hujan">{{ old('notes', $diary->notes) }}</textarea>
                    </div>
                </div>
            </details>

            <button type="submit" class="btn-primary w-full py-3.5 text-[15px]">Simpan catatan</button>
            <p class="text-center text-xs muted">Kamu bisa mengoreksinya kapan saja. Riwayat perubahan tetap disimpan.</p>
        </form>
    </div>
@endsection
