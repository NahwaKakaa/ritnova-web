<?php

namespace App\Console\Commands;

use App\Models\CohortSessionSchedule;
use App\Models\User;
use App\Notifications\SessionReleased;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

/** Dijalankan scheduler setiap menit: kirim notifikasi web saat sesi dirilis. */
class NotifySessionReleases extends Command
{
    protected $signature = 'ritnova:notify-releases';

    protected $description = 'Kirim notifikasi web untuk sesi yang baru dirilis';

    public function handle(): int
    {
        $due = CohortSessionSchedule::with('learningSession')
            ->where('release_at', '<=', now())
            ->where('release_at', '>=', now()->subDay())
            ->get();

        foreach ($due as $schedule) {
            // Hanya sekali per jadwal.
            if (! Cache::add('release-notified:'.$schedule->id, true, now()->addDays(2))) {
                continue;
            }

            User::where('cohort_id', $schedule->cohort_id)
                ->where('role', User::ROLE_PARTICIPANT)
                ->where('study_group', User::GROUP_INTERVENTION)
                ->where('status', 'active')
                ->each(fn (User $user) => $user->notify(new SessionReleased($schedule->learningSession)));

            $this->info('Notifikasi sesi '.$schedule->learningSession->number.' terkirim.');
        }

        return self::SUCCESS;
    }
}
