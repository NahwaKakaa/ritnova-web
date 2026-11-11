@extends('layouts.participant', ['title' => 'Refleksi mingguan'])

@section('content')
    <div class="mx-auto max-w-2xl">
        <p class="mb-1 font-semibold text-secondary num">Minggu {{ $week }}</p>
        <h1 class="page-title mb-2">Evaluasi minggu ini</h1>
        <p class="mb-8 muted">Tiga pertanyaan, kurang dari dua menit.</p>

        <form method="POST" action="{{ route('reflection.store') }}" class="space-y-6">
            @csrf
            <fieldset class="card sm:p-6">
                <legend class="sr-only">Kemudahan target</legend>
                <p class="mb-4 text-lg font-bold">Seberapa mudah target minggu ini dijalankan?</p>
                <div class="grid grid-cols-5 gap-2">
                    @foreach ([1 => 'Sangat sulit', 2 => 'Sulit', 3 => 'Cukup', 4 => 'Mudah', 5 => 'Sangat mudah'] as $value => $label)
                        <label class="flex cursor-pointer flex-col items-center gap-1 rounded-2xl border border-night/10 bg-white px-1 py-4 text-center text-[11px] leading-tight transition has-checked:border-primary has-checked:bg-primary has-checked:text-white sm:text-xs">
                            <input type="radio" name="ease_rating" value="{{ $value }}" class="sr-only" @checked(old('ease_rating', $reflection?->ease_rating) == $value) required>
                            <span class="text-2xl font-bold num">{{ $value }}</span>{{ $label }}
                        </label>
                    @endforeach
                </div>
                @error('ease_rating') <p class="mt-2 text-sm text-rose-deep">{{ $message }}</p> @enderror
            </fieldset>

            <fieldset class="card sm:p-6">
                <legend class="sr-only">Hambatan</legend>
                <p class="mb-4 text-lg font-bold">Hambatan utama yang muncul?</p>
                <div class="flex flex-wrap gap-2">
                    @foreach (config('ritnova.reflection_barriers') as $key => $label)
                        <label class="cursor-pointer rounded-full border border-night/10 bg-white px-4 py-2 text-sm transition has-checked:border-primary has-checked:bg-lavender has-checked:font-semibold has-checked:text-primary">
                            <input type="checkbox" name="barriers[]" value="{{ $key }}" class="sr-only" @checked(in_array($key, old('barriers', $reflection?->barriers ?? [])))>{{ $label }}
                        </label>
                    @endforeach
                </div>
            </fieldset>

            <div class="card sm:p-6">
                <label class="mb-4 block text-lg font-bold" for="adjustment">Satu penyesuaian kecil untuk minggu depan</label>
                <input class="input" id="adjustment" name="adjustment" maxlength="500" placeholder="Menyiapkan bahan kuliah sebelum jam 21.00" value="{{ old('adjustment', $reflection?->adjustment) }}">
            </div>

            <button type="submit" class="btn-primary w-full py-3.5">{{ $reflection ? 'Perbarui refleksi' : 'Simpan refleksi' }}</button>
            <p class="text-center text-xs muted">Refleksi membantu menyusun langkah berikutnya dan tidak dipakai untuk menilai dirimu.</p>
        </form>

        @if ($history->isNotEmpty())
            <h2 class="mt-12 mb-3 font-bold">Refleksi sebelumnya</h2>
            <div class="card overflow-hidden p-0">
                @foreach ($history as $past)
                    <div class="flex items-center gap-4 border-b border-night/5 px-5 py-4 text-sm last:border-0">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-lavender font-bold text-primary num">{{ $past->ease_rating }}</span>
                        <span><span class="block font-semibold num">Minggu {{ $past->week }}</span><span class="muted">{{ $past->adjustment ?: 'Tanpa catatan penyesuaian' }}</span></span>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
@endsection
