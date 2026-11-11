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

        $this->command?->info('Data demo siap. Login: RV-001 / RV-002 / RV-003 / RV-004, kata sandi "password".');
    }

    private function diaries(User $user, int $nights, bool $withGap = false): void
    {
        $tz = config('ritnova.timezone');
        SleepDiary::where('user_id', $user->id)->delete();

        foreach (range(1, $nights) as $i) {
            $night = CarbonImmutable::now($tz)->subDays($i)->startOfDay();
            $free = $night->addDay()->isWeekend();
            $jitter = (($i * 17) % 50) - 20; // variasi -20..+29 menit, deterministik

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
