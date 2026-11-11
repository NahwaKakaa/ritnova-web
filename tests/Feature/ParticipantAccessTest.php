<?php

namespace Tests\Feature;

use App\Models\Cohort;
use App\Models\Consent;
use App\Models\ConsentVersion;
use App\Models\SleepDiary;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ParticipantAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    private function participant(string $group, bool $consented = true): User
    {
        $user = User::create([
            'username' => 'RV-'.fake()->unique()->numberBetween(100, 999),
            'role' => User::ROLE_PARTICIPANT,
            'cohort_id' => Cohort::first()->id,
            'study_group' => $group,
            'password' => 'password',
        ]);

        if ($consented) {
            Consent::create(['user_id' => $user->id, 'consent_version_id' => ConsentVersion::current()->id, 'agreed_at' => now()]);
        }

        return $user;
    }

    public function test_seeder_creates_twelve_scheduled_sessions(): void
    {
        $this->assertSame(12, Cohort::first()->schedules()->count());
    }

    public function test_participant_login_with_code(): void
    {
        $user = $this->participant('intervention');

        $this->post('/masuk', ['username' => strtolower($user->username), 'password' => 'password'])
            ->assertRedirect(route('dashboard'));
    }

    public function test_u01_cannot_continue_before_consent(): void
    {
        $this->actingAs($this->participant('intervention', consented: false))
            ->get('/beranda')
            ->assertRedirect(route('consent.show'));
    }

    public function test_u02_comparison_group_cannot_open_intervention_content(): void
    {
        $user = $this->participant('comparison');

        $this->actingAs($user)->get('/belajar')->assertNotFound();
        $this->actingAs($user)->get('/sjl-check')->assertNotFound();
        $this->actingAs($user)->get('/catatan-tidur')->assertOk();
    }

    public function test_g04_direct_url_to_locked_session_is_rejected_by_server(): void
    {
        $user = $this->participant('intervention');

        // Sebelum rilis pertama (seeder: kohort mulai Senin depan).
        $this->actingAs($user)->get('/belajar/1')->assertOk()->assertSee('Belum Tersedia');
        $this->actingAs($user)->post('/belajar/1/selesai')->assertForbidden();
    }

    public function test_g05_session_cannot_complete_without_activities(): void
    {
        $user = $this->participant('intervention');
        Carbon::setTestNow(Cohort::first()->schedules()->orderBy('release_at')->first()->release_at->addHour());

        $this->actingAs($user)->get('/belajar/1')->assertOk()->assertSee('Selesaikan sesi');
        $this->actingAs($user)->post('/belajar/1/selesai')->assertSessionHasErrors('complete');

        Carbon::setTestNow();
    }

    public function test_u03_diary_cross_midnight_is_stored_as_seven_hours(): void
    {
        $user = $this->participant('comparison');

        $this->actingAs($user)->post('/catatan-tidur', [
            'diary_date' => now()->subDay()->toDateString(),
            'day_type' => 'workday',
            'sleep_onset_time' => '23:30',
            'sleep_offset_time' => '06:30',
        ])->assertRedirect(route('diary.index'));

        $diary = SleepDiary::firstWhere('user_id', $user->id);
        $this->assertSame('complete', $diary->status);
        $this->assertEquals(420, $diary->sleep_onset->diffInMinutes($diary->sleep_offset));
    }

    public function test_diary_edit_keeps_revision_history(): void
    {
        $user = $this->participant('comparison');
        $payload = ['diary_date' => now()->subDay()->toDateString(), 'day_type' => 'workday', 'sleep_onset_time' => '23:30', 'sleep_offset_time' => '06:30'];

        $this->actingAs($user)->post('/catatan-tidur', $payload);
        $diary = SleepDiary::firstWhere('user_id', $user->id);
        $this->actingAs($user)->put('/catatan-tidur/'.$diary->id, [...$payload, 'sleep_offset_time' => '07:00']);

        $this->assertSame(1, $diary->revisions()->count());
    }

    public function test_participants_cannot_open_admin_and_staff_can_export_reminders(): void
    {
        $this->actingAs($this->participant('intervention'))->get('/admin')->assertForbidden();

        $admin = User::where('role', User::ROLE_ADMIN)->first();
        $this->participant('comparison');

        $this->actingAs($admin)
            ->get('/admin/ekspor/pengingat?type=diary&group=comparison')
            ->assertOk()
            ->assertDownload()
            ->assertStreamedContent("participant_code,group,reminder\n".User::where('study_group', 'comparison')->value('username').",comparison,\"Lengkapi catatan tidur semalam\"\n");
    }
}
