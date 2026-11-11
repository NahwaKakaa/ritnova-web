@extends('layouts.participant', ['title' => 'Refleksi Mingguan'])

@section('content')
    <div class="mx-auto max-w-2xl">
        <h1 class="mb-1 text-2xl font-bold">Evaluasi Minggu Ini</h1>
        <p class="mb-6 text-ink/60">Minggu {{ $week }} · kurang dari 2 menit</p>

        <form method="POST" action="{{ route('reflection.store') }}" class="card space-y-6">
            @csrf
            <fieldset>
                <legend class="mb-3 font-semibold">Seberapa mudah target minggu ini dijalankan?</legend>
                <div class="grid grid-cols-5 gap-2 text-center text-xs">
                    @foreach ([1 => 'Sangat sulit', 2 => 'Sulit', 3 => 'Cukup', 4 => 'Mudah', 5 => 'Sangat mudah'] as $value => $label)
                        <label class="cursor-pointer rounded-xl border border-black/10 p-3 has-checked:border-primary has-checked:bg-lavender">
                            <input type="radio" name="ease_rating" value="{{ $value }}" class="sr-only" @checked(old('ease_rating', $reflection?->ease_rating) == $value) required>
                            <span class="block text-lg font-bold">{{ $value }}</span>{{ $label }}
                        </label>
                    @endforeach
                </div>
                @error('ease_rating') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </fieldset>

            <fieldset>
                <legend class="mb-3 font-semibold">Hambatan utama yang muncul?</legend>
                <div class="grid gap-2 sm:grid-cols-2">
                    @foreach (config('ritnova.reflection_barriers') as $key => $label)
                        <label class="flex items-center gap-2 rounded-xl border border-black/10 px-3 py-2 text-sm has-checked:border-primary has-checked:bg-lavender">
                            <input type="checkbox" name="barriers[]" value="{{ $key }}" @checked(in_array($key, old('barriers', $reflection?->barriers ?? [])))> {{ $label }}
                        </label>
                    @endforeach
                </div>
            </fieldset>

            <div>
                <label class="label" for="adjustment">Satu penyesuaian kecil untuk minggu depan</label>
                <input class="input" id="adjustment" name="adjustment" maxlength="500" placeholder="Contoh: menyiapkan bahan kuliah sebelum jam 21.00" value="{{ old('adjustment', $reflection?->adjustment) }}">
            </div>

            <button class="btn-primary w-full">{{ $reflection ? 'Perbarui refleksi' : 'Simpan refleksi' }}</button>
            <p class="text-center text-xs text-ink/50">Refleksi bersifat dukungan perilaku dan tidak digunakan untuk menilai karakter peserta.</p>
        </form>

        @if ($history->isNotEmpty())
            <h2 class="mt-8 mb-3 font-semibold">Refleksi sebelumnya</h2>
            @foreach ($history as $past)
                <div class="card mb-2 p-4 text-sm"><b>Minggu {{ $past->week }}</b> · kemudahan {{ $past->ease_rating }}/5{{ $past->adjustment ? ' · '.$past->adjustment : '' }}</div>
            @endforeach
        @endif
    </div>
@endsection
