<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Cohort;
use App\Models\CohortSessionSchedule;
use App\Models\LearningSession;
use App\Models\SessionProgress;
use App\Models\SleepDiary;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Concerns\CreatesParticipants;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use CreatesParticipants, RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->admin = User::where('role', User::ROLE_ADMIN)->first();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function researcher(): User
    {
        return User::create(['username' => 'peneliti', 'name' => 'Peneliti', 'role' => User::ROLE_RESEARCHER, 'password' => 'password', 'status' => 'active']);
    }

    /** Pindah ke hari Kamis minggu pertama: sesi 1 & 2 sudah rilis. */
    private function travelIntoProgram(): void
    {
        Carbon::setTestNow(Cohort::first()->starts_on->copy()->addDays(3)->setTime(10, 0));
    }

    public function test_every_admin_page_renders_with_data(): void
    {
        $this->travelIntoProgram();
        $user = $this->participant('intervention');
        $this->participant('comparison');
        SleepDiary::create(['user_id' => $user->id, 'diary_date' => now()->subDay()->toDateString(), 'day_type' => 'workday',
            'sleep_onset' => now()->subDay()->setTime(23, 0), 'sleep_offset' => now()->setTime(6, 30), 'status' => 'complete']);
        $first = LearningSession::firstWhere('number', 1);
        SessionProgress::create(['user_id' => $user->id, 'learning_session_id' => $first->id, 'started_at' => now()->subDays(3)->setTime(19, 5),
            'completed_at' => now()->subDays(3)->setTime(19, 20), 'media_completion_pct' => 1, 'active_seconds' => 500]);

        $pages = [
            '/admin' => 'Penyelesaian modul per minggu',
            '/admin/peserta' => 'Peserta & kelompok',
            '/admin/peserta/'.$user->id => 'Paparan 12 sesi',
            '/admin/paparan' => 'Paparan intervensi, minggu 1',
            '/admin/pengukuran' => 'Pengukuran T0, T1, T2',
            '/admin/kualitas-diary' => 'Kualitas diary',
            '/admin/konten' => 'Konten 12 sesi',
            '/admin/konten/'.$first->id => 'Cek pemahaman',
            '/admin/jadwal' => 'Jadwal sesi',
            '/admin/ekspor' => 'Kamus data',
            '/admin/audit' => 'Audit log',
            '/admin/akses' => 'Akses tim',
        ];
        foreach ($pages as $url => $text) {
            $this->actingAs($this->admin)->get($url)->assertOk()->assertSee($text);
        }

        $this->actingAs($this->admin)->get('/admin/paparan?minggu=1')->assertSee($user->username)->assertSee('1/2');
    }

    public function test_researcher_can_view_but_not_change_protocol(): void
    {
        $researcher = $this->researcher();
        $this->actingAs($researcher)->get('/admin/peserta')->assertOk()->assertDontSee('Impor kode peserta');
        $this->actingAs($researcher)->get('/admin/akses')->assertForbidden();
        $this->actingAs($researcher)->post('/admin/peserta/impor', ['rows' => 'RV-900,intervensi'])->assertForbidden();
        $this->actingAs($researcher)->put('/admin/jadwal', [])->assertForbidden();
        $this->assertDatabaseMissing('users', ['username' => 'RV-900']);
    }

    public function test_import_creates_participants_once_and_shows_passwords(): void
    {
        $this->participant('intervention'); // kode acak RV-1xx..9xx, tidak bentrok dengan RV-0xx
        User::create(['username' => 'RV-050', 'role' => 'participant', 'cohort_id' => Cohort::first()->id, 'password' => 'x']);

        $this->actingAs($this->admin)->post('/admin/peserta/impor', [
            'rows' => "participant_code,group\nRV-051,intervensi\nRV-052,pembanding,rahasia99\nRV-050,intervensi\nbad code!,intervensi",
        ])->assertRedirect('/admin/peserta')->assertSessionHas('credentials', fn ($c) => count($c) === 2 && $c[1] === ['RV-052', 'rahasia99']);

        $this->assertSame('intervention', User::firstWhere('username', 'RV-051')->study_group);
        $this->assertSame('comparison', User::firstWhere('username', 'RV-052')->study_group);
        $this->assertTrue(\Hash::check('rahasia99', User::firstWhere('username', 'RV-052')->password));
        $this->assertDatabaseHas('audit_logs', ['action' => 'participants_imported']);
    }

    public function test_group_change_needs_reason_and_is_audited(): void
    {
        $user = $this->participant('comparison');
        $this->actingAs($this->admin)->patch("/admin/peserta/{$user->id}/kelompok", ['study_group' => 'intervention'])->assertSessionHasErrors('reason');
        $this->actingAs($this->admin)->patch("/admin/peserta/{$user->id}/kelompok", ['study_group' => 'intervention', 'reason' => 'Hasil randomisasi blok 2'])->assertRedirect();

        $this->assertSame('intervention', $user->fresh()->study_group);
        $log = AuditLog::firstWhere('action', 'group_assigned');
        $this->assertSame(['study_group' => 'comparison'], $log->old_values);
        $this->assertSame('Hasil randomisasi blok 2', $log->new_values['reason']);
    }

    public function test_password_reset_and_withdrawal(): void
    {
        $user = $this->participant('intervention');
        $this->actingAs($this->admin)->post("/admin/peserta/{$user->id}/kata-sandi")->assertSessionHas('credentials');
        $this->assertFalse(\Hash::check('password', $user->fresh()->password));

        $this->actingAs($this->admin)->patch("/admin/peserta/{$user->id}/status", ['status' => 'withdrawn'])->assertSessionHasErrors('reason');
        $this->actingAs($this->admin)->patch("/admin/peserta/{$user->id}/status", ['status' => 'withdrawn', 'reason' => 'Permintaan peserta'])->assertRedirect();
        $this->assertSame('withdrawn', $user->fresh()->status);
    }

    public function test_schedule_update_is_validated_and_audited(): void
    {
        $cohort = Cohort::first();
        $schedule = CohortSessionSchedule::where('cohort_id', $cohort->id)->orderBy('release_at')->first();
        $base = ['name' => $cohort->name, 'starts_on' => $cohort->starts_on->toDateString(), 'late_policy' => 'strict', 'status' => 'active'];

        $this->actingAs($this->admin)->put('/admin/jadwal', [...$base, 'schedules' => [$schedule->id => [
            'release_at' => '2026-12-07T19:00', 'close_at' => '2026-12-07T18:00', 'prerequisite_session_id' => null,
        ]]])->assertSessionHasErrors("schedules.{$schedule->id}.close_at");

        $this->actingAs($this->admin)->put('/admin/jadwal', [...$base, 'schedules' => [$schedule->id => [
            'release_at' => '2026-12-07T19:30', 'close_at' => '', 'prerequisite_session_id' => null,
        ]]])->assertRedirect();

        $schedule->refresh();
        $this->assertSame('2026-12-07 19:30', $schedule->release_at->setTimezone('Asia/Makassar')->format('Y-m-d H:i'));
        $this->assertNull($schedule->close_at);
        $this->assertSame('strict', $cohort->fresh()->late_policy);
        $this->assertTrue(AuditLog::where('action', 'updated')->where('auditable_type', $schedule->getMorphClass())->exists());
    }

    public function test_content_update_bumps_version_only_when_changed(): void
    {
        $session = LearningSession::firstWhere('number', 2);
        $payload = [
            'title' => $session->title, 'media_type' => 'video', 'media_url' => 'https://youtu.be/contoh',
            'quiz' => [['question' => 'Apa itu midpoint?', 'options' => "Titik tengah tidur\nJam bangun\n", 'correct' => 1]],
        ];
        $this->actingAs($this->researcher())->put("/admin/konten/{$session->id}", $payload)->assertRedirect();

        $session->refresh();
        $this->assertSame(2, $session->content_version);
        $this->assertSame(['Titik tengah tidur', 'Jam bangun'], $session->quizItems->first()->options);
        $this->assertSame(0, $session->quizItems->first()->correct_index);

        $this->actingAs($this->admin)->put("/admin/konten/{$session->id}", $payload)->assertSessionHas('status', 'Tidak ada perubahan.');
        $this->assertSame(2, $session->fresh()->content_version);

        $this->actingAs($this->admin)->put("/admin/konten/{$session->id}", [...$payload, 'quiz' => [['question' => 'X', 'options' => "A\nB", 'correct' => 3]]])
            ->assertSessionHasErrors('quiz.0.correct');
    }

    public function test_exports_keep_missing_values_empty_and_are_audited(): void
    {
        $user = $this->participant('intervention');
        SleepDiary::create(['user_id' => $user->id, 'diary_date' => '2026-11-10', 'day_type' => 'workday',
            'sleep_onset' => Carbon::parse('2026-11-10 23:15', 'Asia/Makassar'), 'status' => 'partial']);

        $csv = $this->actingAs($this->admin)->get('/admin/ekspor/sleep_diaries')->assertOk()->streamedContent();
        $lines = array_values(array_filter(explode("\n", $csv)));
        $this->assertStringStartsWith("\xEF\xBB\xBFparticipant_code,group_code,diary_date", $lines[0]);
        $this->assertSame($user->username.',intervention,2026-11-10,workday,,2026-11-10T23:15:00+08:00,,,,partial,0', implode(',', array_slice(str_getcsv($lines[1]), 0, 11)));

        foreach (array_keys(\App\Http\Controllers\Admin\ExportController::DATASETS) as $dataset) {
            $this->actingAs($this->admin)->get("/admin/ekspor/{$dataset}")->assertOk();
        }
        $this->actingAs($this->admin)->get('/admin/ekspor/kamus-data')->assertOk();
        $this->actingAs($this->admin)->get('/admin/ekspor/tidak-ada')->assertNotFound();
        $this->assertDatabaseHas('audit_logs', ['action' => 'export_dataset']);
    }

    public function test_admin_creates_staff_accounts(): void
    {
        $this->actingAs($this->admin)->post('/admin/akses', ['username' => 'dosen.pembimbing', 'name' => 'Dosen', 'role' => 'researcher'])
            ->assertSessionHas('credentials');
        $staff = User::firstWhere('username', 'dosen.pembimbing');
        $this->assertSame(User::ROLE_RESEARCHER, $staff->role);

        $this->actingAs($this->admin)->patch("/admin/akses/{$staff->id}/status")->assertRedirect();
        $this->assertSame('inactive', $staff->fresh()->status);
    }
}
