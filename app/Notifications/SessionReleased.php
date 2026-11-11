<?php

namespace App\Notifications;

use App\Models\LearningSession;
use Illuminate\Notifications\Notification;

/** Notifikasi di dalam website (tanpa biaya). Tidak memuat skor/hal sensitif. */
class SessionReleased extends Notification
{
    public function __construct(public LearningSession $session) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'message' => 'Sesi '.str_pad((string) $this->session->number, 2, '0', STR_PAD_LEFT).' sudah tersedia untuk dipelajari.',
            'url' => route('learning.show', $this->session, false),
        ];
    }
}
