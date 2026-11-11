<label class="flex items-center gap-2 text-sm">
    <input type="hidden" name="reminder_opt_in" value="0">
    <input type="checkbox" name="reminder_opt_in" value="1" @checked(old('reminder_opt_in', $user->reminder_opt_in))>
    Saya bersedia menerima pengingat aktivitas
</label>
<div class="grid gap-3 sm:grid-cols-2">
    <div>
        <label class="label" for="reminder_channel">Kanal pengingat</label>
        <select class="input" id="reminder_channel" name="reminder_channel">
            @foreach (['web' => 'Website saja', 'whatsapp' => 'WhatsApp', 'both' => 'Website + WhatsApp'] as $value => $label)
                <option value="{{ $value }}" @selected(old('reminder_channel', $user->reminder_channel) === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="label" for="reminder_time">Jam pengingat diary</label>
        <input class="input" type="time" id="reminder_time" name="reminder_time" min="06:00" max="21:00" value="{{ old('reminder_time', $user->reminder_time ?? '08:00') }}">
        @error('reminder_time') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
</div>
<p class="text-xs text-ink/50">Pengingat tidak pernah memuat skor SJL atau informasi sensitif. Frekuensi dibatasi dan hanya dikirim pada jam wajar.</p>
