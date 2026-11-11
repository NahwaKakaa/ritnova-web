@php
    $d = $row['decision'];
    $prereq = $row['schedule']->prerequisite?->number;
    $text = match ($d->status->value) {
        'completed' => 'Selesai',
        'available' => 'Tersedia sekarang',
        'locked_time' => 'Menunggu rilis '.$row['schedule']->release_at->translatedFormat('l'),
        'locked_prerequisite' => 'Selesaikan sesi '.str_pad((string) $prereq, 2, '0', STR_PAD_LEFT),
        'expired' => 'Kedaluwarsa',
    };
@endphp
<span @class(['badge',
    'bg-green-100 text-green-800' => $d->status->value === 'completed',
    'bg-lavender text-primary' => $d->status->value === 'available',
    'bg-sky/60 text-ink' => $d->status->value === 'locked_time',
    'bg-peach text-ink' => in_array($d->status->value, ['locked_prerequisite', 'expired']),
])>{{ $text }}{{ $d->isLate ? ' · terlambat' : '' }}</span>
