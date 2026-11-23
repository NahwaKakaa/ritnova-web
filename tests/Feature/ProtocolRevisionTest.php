<?php

namespace Tests\Feature;

use App\Models\AssessmentResponse;
use App\Models\AssessmentWindow;
use App\Models\Cohort;
use App\Models\Instrument;
use App\Models\LearningSession;
use App\Models\SleepDiary;
use App\Services\ParticipantStatus;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Concerns\CreatesParticipants;
use Tests\TestCase;

/**
 * Revisi alur peneliti (23 Nov 2026): T0 tujuh malam, SSE minggu kedua (M2),
 * T1 minggu ke-5, T2 minggu ke-8, menunggu pembagian kelompok, SJL disembunyikan saat T0.
 */
class ProtocolRevisionTest extends TestCase
{
    use CreatesParticipants, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function cohortStart(): Carbon
    {
        return Carbon::parse(Cohort::first()->starts_on->toDateString(), config('ritnova.timezone'));
    }

    private function diary(int $userId, string $date): void
    {
        SleepDiary::create([
            'user_id' => $userId, 'diary_date' => $date, 'day_type' => 'workday', 'status' => 'complete',
            'sleep_onset' => Carbon::parse("$date 23:30"), 'sleep_offset' => Carbon::parse("$date 23:30")->addHours(7),
        ]);
    }

    public function test_default_windows_follow_revised_protocol(): void
    {
        $start = $this->cohortStart();
        $w = AssessmentWindow::where('cohort_id', Cohort::first()->id)->get()->keyBy('phase');

        $this->assertSame(['M2', 'T0', 'T1', 'T2'], $w->keys()->sort()->values()->all());
        $this->assertSame($start->copy()->subDays(7)->toDateString(), $w['T0']->opens_at->toDateString());
        $this->assertSame($start->copy()->subDay()->toDateString(), $w['T0']->closes_at->toDateString());
        $this->assertSame($start->copy()->addDays(7)->toDateString(), $w['M2']->opens_at->toDateString());
        $this->assertSame($start->copy()->addDays(28)->toDateString(), $w['T1']->opens_at->toDateString());
        $this->assertSame($start->copy()->addDays(49)->toDateString(), $w['T2']->opens_at->toDateString());
    }

    public function test_t0_diary_progress_counts_seven_nights_inside_window(): void
    {
        $user = $this->participant('comparison');
        $t0 = $this->cohortStart()->subDays(7);
        Carbon::setTestNow($t0->copy()->addDays(4)->setTime(8, 0));

        foreach ([0, 1, 2] as $d) {
            $this->diary($user->id, $t0->copy()->addDays($d)->toDateString());
        }
        // Malam sebelum jendela T0 tidak ikut dihitung.
        $this->diary($user->id, $t0->copy()->subDay()->toDateString());

        $progress = ParticipantStatus::for($user)->diaryProgress('T0');
        $this->assertSame(7, $progress['target']);
        $this->assertSame(3, $progress['done']);
        $this->assertTrue($progress['days'][0]['filled']);
        $this->assertFalse($progress['days'][3]['filled']);
        $this->assertNull(ParticipantStatus::for($user)->diaryProgress('M2'));

        $this->actingAs($user)->get('/persiapan')->assertOk()->assertSee('Pencatatan tidur T0: 3 dari 7 malam selesai.');
        $this->actingAs($user)->get('/beranda')->assertOk()->assertSee('3 dari 7');
    }

    public function test_week_two_questionnaire_prompt_appears_on_dashboard(): void
    {
        $user = $this->participant('comparison');
        Instrument::create(['code' => 'SSE', 'name' => 'Sleep Self-Efficacy', 'phases' => ['T0', 'M2', 'T1', 'T2']]);
        Carbon::setTestNow($this->cohortStart()->addDays(8)->setTime(9, 0));

        $this->actingAs($user)->get('/beranda')->assertOk()->assertSee('Kuesioner minggu kedua sudah tersedia');
        $this->actingAs($user)->get('/pengukuran')->assertOk()->assertSee('Minggu kedua program');
    }

    public function test_waiting_participant_sees_baseline_done_message(): void
    {
        $user = $this->participant('comparison');
        $user->forceFill(['study_group' => null])->save();
        $t0 = $this->cohortStart()->subDays(7);
        Carbon::setTestNow($t0->copy()->addDays(8)->setTime(9, 0));

        foreach (range(0, 6) as $d) {
            $this->diary($user->id, $t0->copy()->addDays($d)->toDateString());
        }
        $instrument = Instrument::create(['code' => 'SSE', 'name' => 'Sleep Self-Efficacy', 'phases' => ['T0'], 'version' => '1']);
        AssessmentResponse::create(['user_id' => $user->id, 'instrument_id' => $instrument->id, 'phase' => 'T0',
            'instrument_version' => '1', 'started_at' => now(), 'submitted_at' => now()]);

        $this->actingAs($user)->get('/beranda')->assertOk()->assertSee('Pengukuran awal telah selesai')->assertSee('menunggu program dimulai');
    }

    public function test_sjl_check_hidden_before_program_starts(): void
    {
        $user = $this->participant('intervention');
        Carbon::setTestNow($this->cohortStart()->subDays(2)->setTime(9, 0));

        $this->actingAs($user)->get('/sjl-check')->assertRedirect(route('dashboard'));

        Carbon::setTestNow($this->cohortStart()->addDay()->setTime(9, 0));
        $this->actingAs($user)->get('/sjl-check')->assertOk();
    }

    public function test_quiz_stage_is_not_done_when_session_has_no_questions(): void
    {
        $user = $this->participant('intervention');
        $session = LearningSession::where('number', 1)->first();
        $session->quizItems()->delete();
        Carbon::setTestNow(Cohort::first()->schedules()->orderBy('release_at')->first()->release_at->addHour());

        $this->actingAs($user)->get('/belajar/1')->assertOk()->assertSee('sedang disiapkan tim peneliti');
        $this->actingAs($user)->post('/belajar/1/selesai')->assertSessionHasErrors('complete');
    }

    public function test_admin_can_fill_standard_windows(): void
    {
        $admin = \App\Models\User::where('role', \App\Models\User::ROLE_ADMIN)->first();
        AssessmentWindow::where('phase', 'M2')->delete();

        $this->actingAs($admin)->get('/admin/pengukuran')->assertOk()->assertSee('M2');
        $this->actingAs($admin)->post('/admin/pengukuran/jendela/standar')->assertRedirect();
        $this->assertNotNull(AssessmentWindow::firstWhere('phase', 'M2'));
    }
}
