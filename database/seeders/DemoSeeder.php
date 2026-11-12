<?php

namespace Database\Seeders;

use App\Models\Cohort;
use App\Models\Consent;
use App\Models\ConsentVersion;
use App\Models\Goal;
use App\Models\Instrument;
use App\Models\LearningSession;
use App\Models\SessionProgress;
use App\Models\SleepDiary;
use App\Models\User;
use App\Models\WeeklyReflection;
use App\Notifications\SessionReleased;
use App\Services\ScheduleGenerator;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

/**
 * Data DEMO untuk presentasi & uji tampilan (bukan data penelitian).
 * Jalankan: php artisan db:seed --class=DemoSeeder   (hanya APP_ENV=local)
 *
 * Kohort digeser sehingga sekarang berada di minggu ke-2 program.
 *  - RV-001 intervensi : 3 sesi selesai, sesi 4 berjalan, diary 12 malam, target & refleksi
 *  - RV-002 pembanding : diary 8 malam
 *  - RV-003 intervensi : baru menyetujui consent, belum onboarding
 *  - RV-004 pembanding : belum menyetujui consent
 *  - RV-005..RV-024     : peserta tambahan dengan pola beragam untuk panel peneliti
 *  - peneliti / password : akun peneliti (lihat & kelola konten, tanpa ubah protokol)
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command?->error('DemoSeeder tidak boleh dijalankan di production.');

            return;
        }

        $this->call(DatabaseSeeder::class);

        $tz = config('ritnova.timezone');
        $cohort = Cohort::first();
        $cohort->update(['starts_on' => CarbonImmutable::now($tz)->startOfWeek()->subWeek(), 'status' => 'active']);
        app(ScheduleGenerator::class)->generate($cohort->fresh());
        app(ScheduleGenerator::class)->windows($cohort->fresh());

        $consent = ConsentVersion::current();
        $users = [];
        foreach (['RV-001' => 'intervention', 'RV-002' => 'comparison', 'RV-003' => 'intervention', 'RV-004' => 'comparison'] as $code => $group) {
            $users[$code] = User::updateOrCreate(['username' => $code], [
                'role' => User::ROLE_PARTICIPANT, 'cohort_id' => $cohort->id, 'study_group' => $group,
                'password' => 'password', 'status' => 'active',
            ]);
        }

        foreach (['RV-001', 'RV-002', 'RV-003'] as $code) {
            Consent::firstOrCreate(['user_id' => $users[$code]->id, 'consent_version_id' => $consent->id], ['agreed_at' => now()->subDays(9)]);
        }
        foreach (['RV-001', 'RV-002'] as $code) {
            $users[$code]->forceFill(['onboarded_at' => now()->subDays(9), 'reminder_time' => '20:00', 'reminder_channel' => 'both'])->save();
        }

        $this->diaries($users['RV-001'], 12, withGap: true);
        $this->diaries($users['RV-002'], 8);

        // Progres sesi RV-001.
        foreach (LearningSession::orderBy('number')->take(4)->get() as $session) {
            $done = $session->number <= 3;
            SessionProgress::updateOrCreate(['user_id' => $users['RV-001']->id, 'learning_session_id' => $session->id], [
                'content_version' => $session->content_version,
                'first_open_at' => now()->subDays(10 - $session->number * 2)->setTime(19, 7),
                'started_at' => now()->subDays(10 - $session->number * 2)->setTime(19, 8),
                'media_completion_pct' => $done ? 1 : 0.4,
                'media_completed_at' => $done ? now()->subDays(10 - $session->number * 2) : null,
                'quiz_completed_at' => $done ? now()->subDays(10 - $session->number * 2) : null,
                'action_response' => $done ? 'Saya akan mematikan layar 30 menit sebelum tidur.' : null,
                'action_completed_at' => $done ? now()->subDays(10 - $session->number * 2) : null,
                'completed_at' => $done ? now()->subDays(10 - $session->number * 2)->setTime(19, 21) : null,
                'active_seconds' => $done ? 420 + $session->number * 37 : 160,
            ]);

            if ($session->quizItems()->doesntExist()) {
                $session->quizItems()->create([
                    'order' => 1,
                    'question' => '(Contoh) Midpoint tidur adalah…',
                    'options' => ['Jam tengah antara mulai tidur dan bangun', 'Jam mulai tidur', 'Lama tidur dibagi dua'],
                    'correct_index' => 0,
                ]);
            }
        }

        Goal::updateOrCreate(['user_id' => $users['RV-001']->id, 'week' => 2], [
            'target' => 'Mulai tidur sebelum 23.30 minimal 4 hari',
            'action_plan' => ['Matikan layar 30 menit lebih awal', 'Siapkan rutinitas malam 10 menit'],
            'if_then_plan' => 'Jika tugas menumpuk, maka saya buat batas belajar pukul 22.30',
            'status' => 'active',
        ]);
        WeeklyReflection::updateOrCreate(['user_id' => $users['RV-001']->id, 'week' => 1], [
            'ease_rating' => 3, 'barriers' => ['tugas_malam', 'perangkat'], 'adjustment' => 'Menyiapkan bahan kuliah sebelum jam 21.00',
        ]);

        $users['RV-001']->notifications()->delete();
        $users['RV-001']->notify(new SessionReleased(LearningSession::firstWhere('number', 4)));

        // Instrumen contoh agar alur pengukuran bisa dicoba. BUKAN instrumen penelitian.
        $instrument = Instrument::updateOrCreate(['code' => 'DEMO'], [
            'name' => 'Instrumen contoh (demo)', 'description' => 'Hanya untuk mencoba tampilan. Ganti dengan instrumen tervalidasi.',
            'version' => 'demo', 'phases' => ['T0', 'T1', 'T2'],
        ]);
        if ($instrument->items()->doesntExist()) {
            $instrument->items()->createMany([
                ['order' => 1, 'text' => '(Contoh) Saya yakin dapat tidur pada jam yang saya rencanakan.', 'type' => 'likert', 'options' => ['values' => [1, 2, 3, 4, 5], 'labels' => ['Sangat tidak setuju', 'Tidak setuju', 'Netral', 'Setuju', 'Sangat setuju']]],
                ['order' => 2, 'text' => '(Contoh) Pada hari bebas, jam berapa biasanya kamu bangun?', 'type' => 'time'],
            ]);
        }

        User::updateOrCreate(['username' => 'peneliti'], [
            'name' => 'Peneliti Demo', 'role' => User::ROLE_RESEARCHER, 'status' => 'active', 'password' => 'password',
        ]);
        $this->crowd($cohort->fresh(), $consent, $instrument);

        $this->command?->info('Data demo siap. Peserta: RV-001 s.d. RV-024, peneliti: "peneliti", kata sandi "password".');
    }

    /**
     * 20 peserta tambahan dengan pola yang beragam (deterministik): rajin, terlambat,
     * tertinggal, selesai terlalu cepat, diary bolong, durasi tak wajar.
     */
    private function crowd(Cohort $cohort, ConsentVersion $consent, Instrument $instrument): void
    {
        mt_srand(2026);
        $tz = config('ritnova.timezone');
        $released = \App\Models\CohortSessionSchedule::with('learningSession')->where('cohort_id', $cohort->id)
            ->where('release_at', '<=', now())->orderBy('release_at')->get();
        $items = $instrument->items()->get();

        foreach (range(5, 24) as $n) {
            $code = 'RV-'.str_pad((string) $n, 3, '0', STR_PAD_LEFT);
            $group = $n % 2 === 1 ? User::GROUP_INTERVENTION : User::GROUP_COMPARISON;
            $user = User::updateOrCreate(['username' => $code], [
                'role' => User::ROLE_PARTICIPANT, 'cohort_id' => $cohort->id, 'study_group' => $group,
                'password' => 'password', 'status' => $n === 22 ? 'withdrawn' : 'active',
                'last_login_at' => now()->subHours(mt_rand(2, 90)),
            ]);
            if ($n >= 23) {
                continue; // belum memberi persetujuan
            }
            Consent::firstOrCreate(['user_id' => $user->id, 'consent_version_id' => $consent->id], ['agreed_at' => now()->subDays(mt_rand(9, 12))]);
            if ($n === 21) {
                continue; // belum menyelesaikan persiapan
            }
            $user->forceFill(['onboarded_at' => now()->subDays(9), 'reminder_time' => '20:00', 'reminder_channel' => 'whatsapp'])->save();

            // Diary: sebagian rajin, sebagian bolong; RV-009 punya durasi tak wajar.
            $nights = [14, 13, 12, 11, 9, 7, 5, 13, 12, 10, 8, 14, 6, 11, 12, 4][$n - 5] ?? 10;
            $this->diaries($user, $nights, withGap: $n % 4 === 0, shift: mt_rand(-40, 50));
            if ($n === 9) {
                SleepDiary::where('user_id', $user->id)->orderByDesc('diary_date')->first()
                    ?->update(['sleep_offset' => now()->setTime(17, 40)]);
            }

            // T0: sebagian besar sudah mengisi.
            if ($n % 5 !== 0 && $items->isNotEmpty()) {
                $response = \App\Models\AssessmentResponse::updateOrCreate(
                    ['user_id' => $user->id, 'instrument_id' => $instrument->id, 'phase' => 'T0'],
                    ['instrument_version' => $instrument->version, 'started_at' => now()->subDays(10), 'submitted_at' => now()->subDays(10)->addMinutes(6)],
                );
                foreach ($items as $item) {
                    \App\Models\AssessmentItemResponse::updateOrCreate(
                        ['assessment_response_id' => $response->id, 'instrument_item_id' => $item->id],
                        ['value' => $item->type === 'time' ? '0'.mt_rand(7, 9).':30' : (string) mt_rand(2, 5), 'answered_at' => now()->subDays(10)->addMinutes(3)],
                    );
                }
            }

            if ($group !== User::GROUP_INTERVENTION) {
                continue;
            }

            // Paparan: berapa sesi yang dirilis sudah diselesaikan, dan seberapa cepat mulai.
            $doneCount = [5 => 99, 7 => 99, 9 => 2, 11 => 99, 13 => 1, 15 => 99, 17 => 0, 19 => 3, 21 => 0][$n] ?? 2;
            foreach ($released as $i => $schedule) {
                $session = $schedule->learningSession;
                if ($i >= $doneCount + 1) {
                    break;
                }
                $done = $i < $doneCount;
                $delay = [2, 9, 17, 34, 75, 140, 26 * 60][($n + $i) % 7];
                $start = $schedule->release_at->copy()->addMinutes($delay);
                if ($start->isFuture()) {
                    break;
                }
                $active = $n === 15 && $i === 1 ? 70 : mt_rand(330, 760);
                SessionProgress::updateOrCreate(['user_id' => $user->id, 'learning_session_id' => $session->id], [
                    'content_version' => $session->content_version,
                    'first_open_at' => $start->copy()->subMinute(),
                    'started_at' => $start,
                    'media_completion_pct' => $done ? 1 : 0.5,
                    'media_completed_at' => $done ? $start->copy()->addMinutes(6) : null,
                    'quiz_completed_at' => $done ? $start->copy()->addMinutes(8) : null,
                    'action_completed_at' => $done ? $start->copy()->addMinutes(10) : null,
                    'completed_at' => $done ? $start->copy()->addMinutes(11) : null,
                    'active_seconds' => $done ? $active : 140,
                    'is_late' => $schedule->close_at !== null && $start->gt($schedule->close_at),
                ]);
            }
        }
    }

    private function diaries(User $user, int $nights, bool $withGap = false, int $shift = 0): void
    {
        $tz = config('ritnova.timezone');
        SleepDiary::where('user_id', $user->id)->delete();

        foreach (range(1, $nights) as $i) {
            $night = CarbonImmutable::now($tz)->subDays($i)->startOfDay();
            $free = $night->addDay()->isWeekend();
            $jitter = (($i * 17) % 50) - 20 + $shift; // variasi deterministik per peserta

            $onset = $free ? $night->addDay()->setTime(1, 30)->addMinutes($jitter) : $night->setTime(23, 30)->addMinutes($jitter);
            $offset = $free ? $night->addDay()->setTime(9, 30)->addMinutes($jitter) : $night->addDay()->setTime(6, 30)->addMinutes((int) ($jitter / 2));
            $partial = $withGap && $i === 3;

            SleepDiary::create([
                'user_id' => $user->id,
                'diary_date' => $night->toDateString(),
                'day_type' => $free ? SleepDiary::FREE_DAY : SleepDiary::WORKDAY,
                'sleep_onset' => $onset,
                'sleep_offset' => $partial ? null : $offset,
                'used_alarm' => ! $free,
                'status' => $partial ? 'partial' : 'complete',
            ]);
        }
    }
}
