<label class="choice">
    <input type="hidden" name="reminder_opt_in" value="0">
    <input type="checkbox" name="reminder_opt_in" value="1" class="h-4 w-4 accent-primary" @checked(old('reminder_opt_in', $user->reminder_opt_in))>
    Saya bersedia menerima pengingat aktivitas
</label>
<fieldset>
    <legend class="label">Kirim pengingat lewat</legend>
    <div class="grid gap-2 sm:grid-cols-3">
        @foreach (['web' => 'Website saja', 'whatsapp' => 'WhatsApp', 'both' => 'Website dan WhatsApp'] as $value => $label)
            <label class="choice"><input type="radio" name="reminder_channel" value="{{ $value }}" class="h-4 w-4 accent-primary" @checked(old('reminder_channel', $user->reminder_channel ?? 'web') === $value)>{{ $label }}</label>
        @endforeach
    </div>
</fieldset>
<div>
    <label class="label" for="reminder_time">Jam pengingat catatan tidur</label>
    <input class="input max-w-40 text-lg font-semibold" type="time" id="reminder_time" name="reminder_time" min="06:00" max="21:00" value="{{ old('reminder_time', $user->reminder_time ?? '08:00') }}">
    <p class="mt-1.5 text-xs muted">Antara 06.00 dan 21.00.</p>
    @error('reminder_time') <p class="mt-1.5 text-sm text-rose-deep">{{ $message }}</p> @enderror
</div>
<p class="flex gap-2 rounded-2xl bg-canvas p-3 text-xs muted"><x-icon name="shield" class="h-4 w-4 text-brand" />Pengingat tidak pernah memuat skor SJL atau informasi sensitif, dan dikirim dengan frekuensi terbatas.</p>
