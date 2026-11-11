@php
    $d = $row['decision'];
    $prereq = $row['schedule']->prerequisite?->number;
    [$text, $class] = match ($d->status->value) {
        'completed' => ['Selesai', 'pill-done'],
        'available' => [$d->isLate ? 'Tersedia, terlambat' : 'Tersedia', $d->isLate ? 'pill-warn' : 'pill-open'],
        'locked_time' => ['Rilis '.$row['schedule']->release_at->translatedFormat('D, H.i'), 'pill-wait'],
        'locked_prerequisite' => ['Selesaikan sesi '.str_pad((string) $prereq, 2, '0', STR_PAD_LEFT), 'pill-warn'],
        'expired' => ['Kedaluwarsa', 'pill-muted'],
    };
@endphp
<span class="{{ $class }} num">
    @if ($d->status->value === 'completed')<x-icon name="check" class="h-3.5 w-3.5" />@elseif (str_starts_with($d->status->value, 'locked'))<x-icon name="lock" class="h-3.5 w-3.5" />@endif
    {{ $text }}
</span>
