@php $profile = $user->profile; @endphp

<fieldset class="space-y-4">
    <legend class="mb-1 font-semibold">Data diri</legend>
    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label class="label" for="birth_place">Tempat lahir</label>
            <input class="input" id="birth_place" name="birth_place" maxlength="100" value="{{ old('birth_place', $profile?->birth_place) }}" required>
            @error('birth_place') <p class="mt-1.5 text-sm text-rose-deep">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="label" for="birth_date">Tanggal lahir</label>
            <input class="input" type="date" id="birth_date" name="birth_date" value="{{ old('birth_date', $profile?->birth_date?->toDateString()) }}" required>
            <p class="mt-1.5 text-xs muted">Usia dihitung otomatis dari tanggal lahir.</p>
            @error('birth_date') <p class="mt-1.5 text-sm text-rose-deep">{{ $message }}</p> @enderror
        </div>
    </div>
    <fieldset>
        <legend class="label">Jenis kelamin</legend>
        <div class="grid gap-2 sm:grid-cols-2">
            @foreach (\App\Models\ParticipantProfile::SEXES as $value => $label)
                <label class="choice"><input type="radio" name="sex" value="{{ $value }}" class="h-4 w-4 accent-primary" @checked(old('sex', $profile?->sex) === $value) required>{{ $label }}</label>
            @endforeach
        </div>
        @error('sex') <p class="mt-1.5 text-sm text-rose-deep">{{ $message }}</p> @enderror
    </fieldset>
    <fieldset>
        <legend class="label">Kelas</legend>
        <div class="grid grid-cols-3 gap-2">
            @foreach (\App\Models\ParticipantProfile::CLASSES as $value => $label)
                <label class="choice"><input type="radio" name="class_group" value="{{ $value }}" class="h-4 w-4 accent-primary" @checked(old('class_group', $profile?->class_group) === $value) required>{{ $label }}</label>
            @endforeach
        </div>
        @error('class_group') <p class="mt-1.5 text-sm text-rose-deep">{{ $message }}</p> @enderror
    </fieldset>
    <fieldset>
        <legend class="label">Tempat tinggal saat ini</legend>
        <div class="grid gap-2 sm:grid-cols-2">
            @foreach (\App\Models\ParticipantProfile::RESIDENCES as $value => $label)
                <label class="choice"><input type="radio" name="residence" value="{{ $value }}" class="h-4 w-4 accent-primary" @checked(old('residence', $profile?->residence) === $value) required>{{ $label }}</label>
            @endforeach
        </div>
        <label class="sr-only" for="residence_other">Tempat tinggal lainnya</label>
        <input class="input mt-2" id="residence_other" name="residence_other" maxlength="100" placeholder="Jika memilih Lainnya, tuliskan di sini" value="{{ old('residence_other', $profile?->residence_other) }}">
        @error('residence') <p class="mt-1.5 text-sm text-rose-deep">{{ $message }}</p> @enderror
        @error('residence_other') <p class="mt-1.5 text-sm text-rose-deep">{{ $message }}</p> @enderror
    </fieldset>
    <div>
        <label class="label" for="whatsapp">Nomor WhatsApp</label>
        <input class="input max-w-64 num" type="tel" inputmode="numeric" id="whatsapp" name="whatsapp" maxlength="13" pattern="08[0-9]{8,11}" placeholder="08xxxxxxxxxx" value="{{ old('whatsapp', $profile?->whatsapp) }}" required>
        <p class="mt-1.5 text-xs muted">Diawali 08, hanya angka. Dipakai tim peneliti untuk pengingat.</p>
        @error('whatsapp') <p class="mt-1.5 text-sm text-rose-deep">{{ $message }}</p> @enderror
    </div>
</fieldset>
