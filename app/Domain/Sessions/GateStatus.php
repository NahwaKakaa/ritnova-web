<?php

namespace App\Domain\Sessions;

enum GateStatus: string
{
    case Completed = 'completed';                    // Selesai
    case Available = 'available';                    // Tersedia sekarang
    case LockedTime = 'locked_time';                 // Menunggu rilis (tampilkan tanggal & countdown)
    case LockedPrerequisite = 'locked_prerequisite'; // Sesi sebelumnya belum selesai
    case Expired = 'expired';                        // Lewat batas (hanya mode strict)

    public function label(): string
    {
        return match ($this) {
            self::Completed => 'Selesai',
            self::Available => 'Tersedia sekarang',
            self::LockedTime => 'Menunggu rilis',
            self::LockedPrerequisite => 'Selesaikan sesi sebelumnya',
            self::Expired => 'Kedaluwarsa',
        };
    }

    public function canStart(): bool
    {
        return $this === self::Available;
    }
}
