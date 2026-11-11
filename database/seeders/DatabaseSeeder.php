<?php

namespace Database\Seeders;

use App\Models\AssessmentWindow;
use App\Models\Cohort;
use App\Models\ConsentVersion;
use App\Models\LearningSession;
use App\Models\User;
use App\Services\ScheduleGenerator;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Matriks 12 sesi (usulan dari mockup; isi final perlu telaah promosi
     * kesehatan dan kronobiologi).
     */
    private const SESSIONS = [
        [1, 'Apa Itu Social Jetlag?', 'infographic', 'Kenali pola hari kuliah dan hari bebasmu.'],
        [2, 'Ritme Sirkadian & Tidur', 'video', 'Tandai jam tidur pribadimu.'],
        [3, 'Membaca Data Tidur', 'podcast', 'Isi diary dan lihat midpoint tidurmu.'],
        [4, 'Aktivitas Digital Malam', 'video', 'Identifikasi pemicu tidur larut.'],
        [5, 'Sleep Hygiene yang Realistis', 'infographic', 'Pilih satu kebiasaan prioritas.'],
        [6, 'Jadwal Kuliah & Alarm', 'podcast', 'Buat peta hambatan mingguan.'],
        [7, 'Keyakinan Mengelola Tidur', 'video', 'Refleksikan tantangan dirimu.'],
        [8, 'Menetapkan Target Tidur', 'infographic', 'Buat goal yang spesifik.'],
        [9, 'Menyusun Action Plan', 'podcast', 'Siapkan rencana jika–maka.'],
        [10, 'Membaca Perkembangan', 'infographic', 'Evaluasi catatan minggu ini.'],
        [11, 'Strategi Hari Bebas', 'video', 'Rencanakan akhir pekanmu.'],
        [12, 'Review & Langkah Lanjut', 'podcast', 'Refleksikan target ke depan.'],
    ];

    public function run(): void
    {
        User::updateOrCreate(
            ['username' => strtolower(env('RITNOVA_ADMIN_USERNAME', 'admin'))],
            [
                'name' => 'Administrator RITNOVA',
                'role' => User::ROLE_ADMIN,
                'password' => env('RITNOVA_ADMIN_PASSWORD', 'ganti-password-ini'),
            ],
        );

        ConsentVersion::firstOrCreate(['version' => 'v0-draft'], [
            'title' => 'Persetujuan Keikutsertaan Penelitian (DRAF)',
            'body' => "DRAF — ganti dengan dokumen consent resmi yang telah disetujui komisi etik.\n\n"
                ."• Tujuan dan kegiatan penelitian\n• Durasi kegiatan dan kewajiban pengisian\n"
                ."• Jenis data tidur dan respons instrumen\n• Risiko, manfaat, kerahasiaan, dan hak berhenti",
            'is_active' => true,
            'published_at' => now(),
        ]);

        foreach (self::SESSIONS as [$number, $title, $media, $action]) {
            LearningSession::updateOrCreate(['number' => $number], [
                'week' => (int) ceil($number / 3),
                'title' => $title,
                'media_type' => $media,
                'action_prompt' => $action,
                'estimated_minutes' => 8,
            ]);
        }

        // Kohort contoh: mulai Senin depan. Ubah lewat panel admin sebelum penelitian.
        $startsOn = CarbonImmutable::now(config('ritnova.timezone'))->next('Monday');

        $cohort = Cohort::firstOrCreate(['name' => 'Kohort FKM 2026'], [
            'timezone' => config('ritnova.timezone'),
            'starts_on' => $startsOn,
            'late_policy' => config('ritnova.late_policy'),
            'status' => 'draft',
        ]);

        app(ScheduleGenerator::class)->generate($cohort);

        $start = CarbonImmutable::parse($cohort->starts_on->toDateString(), $cohort->timezone);
        $windows = [
            'T0' => [$start->subDays(7), $start->setTime(18, 59)],          // sebelum paparan pertama
            'T1' => [$start->addDays(5), $start->addDays(8)->endOfDay()],     // akhir minggu pertama
            'T2' => [$start->addDays(26), $start->addDays(30)->endOfDay()],   // akhir minggu keempat
        ];

        foreach ($windows as $phase => [$opens, $closes]) {
            AssessmentWindow::updateOrCreate(
                ['cohort_id' => $cohort->id, 'phase' => $phase],
                ['opens_at' => $opens, 'closes_at' => $closes],
            );
        }

        // Peserta contoh hanya untuk lingkungan lokal.
        if (app()->environment('local')) {
            foreach (['RV-001' => 'intervention', 'RV-002' => 'comparison', 'RV-003' => 'intervention', 'RV-004' => 'comparison'] as $code => $group) {
                User::updateOrCreate(['username' => $code], [
                    'role' => User::ROLE_PARTICIPANT,
                    'cohort_id' => $cohort->id,
                    'study_group' => $group,
                    'password' => 'password',
                ]);
            }
        }
    }
}
