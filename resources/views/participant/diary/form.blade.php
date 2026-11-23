@extends('layouts.participant', ['title' => 'Catat tidur'])

@section('content')
    <div class="mx-auto max-w-xl">
        <a href="{{ route('diary.index') }}" class="btn-ghost btn-sm mb-4 -ml-3.5"><x-icon name="arrow-left" class="h-4 w-4" />Catatan tidur</a>
        <h1 class="page-title mb-2">{{ $diary->exists ? 'Ubah catatan tidur' : 'Catat tidurmu' }}</h1>
        <p class="mb-8 muted">Isi setiap pagi untuk tidur semalam. Perkiraan terbaikmu sudah cukup.</p>

        <form method="POST" action="{{ $diary->exists ? route('diary.update', $diary) : route('diary.store') }}" class="space-y-6" data-diary-form>
            @csrf
            @if ($diary->exists) @method('PUT') @endif

            <div class="card space-y-5 sm:p-6">
                <div>
                    <label class="label" for="diary_date">Malam tanggal</label>
                    <input class="input" type="date" id="diary_date" name="diary_date" value="{{ old('diary_date', $diary->diary_date?->toDateString()) }}" max="{{ now()->toDateString() }}" required>
                </div>

                <x-time-select name="bed_time" label="Mulai berbaring atau bersiap tidur" :value="$diary->bed_at?->format('H:i')" />
                <x-time-select name="sleep_onset_time" label="Jam benar-benar mulai tertidur" :value="$diary->sleep_onset?->format('H:i')" />
                <x-time-select name="sleep_offset_time" label="Jam terakhir terbangun" :value="$diary->sleep_offset?->format('H:i')" hint="Jam bangun dan tidak tidur lagi. Tidur yang melewati tengah malam dihitung otomatis." />
                <p class="-mt-2 text-sm muted" data-duration aria-live="polite"></p>

                <fieldset>
                    <legend class="label">Saat bangun, apakah ada kewajiban kuliah atau kegiatan lain?</legend>
                    <div class="grid grid-cols-2 gap-3">
                        @foreach (['workday' => ['Ada kewajiban', 'Harus bangun pada jam tertentu'], 'free_day' => ['Tidak ada', 'Bisa bangun tanpa kewajiban']] as $value => [$label, $hint])
                            <label class="choice flex-col items-start gap-0.5">
                                <span class="flex items-center gap-2"><input type="radio" name="day_type" value="{{ $value }}" class="h-4 w-4 accent-primary" @checked(old('day_type', $diary->day_type) === $value) required>{{ $label }}</span>
                                <span class="pl-6 text-xs font-normal muted">{{ $hint }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('day_type') <p class="mt-1.5 text-sm text-rose-deep">{{ $message }}</p> @enderror
                </fieldset>

                <fieldset>
                    <legend class="label">Apakah kamu bangun dengan alarm?</legend>
                    @php($alarm = old('used_alarm', $diary->used_alarm === null ? null : (int) $diary->used_alarm))
                    <div class="grid grid-cols-2 gap-3">
                        @foreach ([1 => 'Ya', 0 => 'Tidak'] as $value => $label)
                            <label class="choice"><input type="radio" name="used_alarm" value="{{ $value }}" class="h-4 w-4 accent-primary" @checked($alarm !== null && (int) $alarm === $value) required>{{ $label }}</label>
                        @endforeach
                    </div>
                    @error('used_alarm') <p class="mt-1.5 text-sm text-rose-deep">{{ $message }}</p> @enderror
                </fieldset>

                <div>
                    <label class="label" for="notes">Catatan tambahan</label>
                    <textarea class="input" id="notes" name="notes" rows="2" placeholder="Misalnya: sedang sakit, begadang karena tugas, terbangun karena hujan">{{ old('notes', $diary->notes) }}</textarea>
                    <p class="mt-1.5 text-xs muted">Isi bila ada kondisi khusus. Kosongkan bila tidak ada.</p>
                </div>
            </div>

            <button type="submit" class="btn-primary w-full py-3.5 text-[15px]">Simpan catatan</button>
            <p class="text-center text-xs muted">Kamu bisa mengoreksinya kapan saja. Riwayat perubahan tetap disimpan.</p>
        </form>
    </div>
@endsection
