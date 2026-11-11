<?php

namespace Tests\Feature;

use App\Models\AssessmentResponse;
use App\Models\Cohort;
use App\Models\Goal;
use App\Models\Instrument;
use App\Models\User;
use App\Models\WeeklyReflection;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesParticipants;
use Tests\TestCase;

class ParticipantPagesTest extends TestCase
{
    use CreatesParticipants, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_landing_has_program_features_and_faq(): void
    {
        $this->get('/')->assertOk()->assertSee('Alur program 4 minggu')->assertSee('Pertanyaan umum')->assertSee('Masuk sebagai Peserta');
    }

    public function test_every_intervention_page_renders(): void
    {
        $user = $this->participant('intervention');

        foreach (['/beranda', '/menu', '/persiapan', '/catatan-tidur', '/catatan-tidur/baru', '/pengukuran', '/notifikasi',
            '/pengingat', '/bantuan', '/profil', '/belajar', '/belajar/1', '/progres', '/sjl-check', '/target', '/refleksi'] as $url) {
            $this->actingAs($user)->get($url)->assertOk();
        }
    }

    public function test_comparison_group_sees_only_its_pages(): void
    {
        $user = $this->participant('comparison');

        foreach (['/beranda', '/menu', '/catatan-tidur', '/pengukuran', '/pengingat', '/bantuan', '/profil'] as $url) {
            $this->actingAs($user)->get($url)->assertOk()->assertDontSee('SJL Check');
        }

        foreach (['/belajar', '/progres', '/sjl-check', '/target', '/refleksi'] as $url) {
            $this->actingAs($user)->get($url)->assertNotFound();
        }
    }

    public function test_consent_leads_to_onboarding_and_profile_step_completes_it(): void
    {
        $user = $this->participant('intervention', consented: false, onboarded: false);

        $this->actingAs($user)->post('/persetujuan', ['agree' => '1'])->assertRedirect(route('onboarding'));
        $this->actingAs($user)->get('/persiapan')->assertOk()->assertSee('Langkah 2 dari 4');

        $this->actingAs($user)->post('/persiapan/profil', [
            'code_confirmed' => '1', 'reminder_opt_in' => '1', 'reminder_channel' => 'both', 'reminder_time' => '20:00',
        ])->assertRedirect(route('onboarding'));

        $this->actingAs($user)->post('/persiapan/selesai')->assertRedirect(route('dashboard'));
        $this->assertNotNull($user->fresh()->onboarded_at);
        $this->assertSame('both', $user->fresh()->reminder_channel);
    }

    public function test_reminder_time_must_be_reasonable(): void
    {
        $user = $this->participant('comparison');

        $this->actingAs($user)->put('/pengingat', ['reminder_channel' => 'web', 'reminder_time' => '23:30'])
            ->assertSessionHasErrors('reminder_time');
    }

    public function test_u04_manual_sjl_calculator_runs_on_server(): void
    {
        $this->actingAs($this->participant('intervention'))->post('/sjl-check', [
            'work_onset' => '23:30', 'work_offset' => '06:30', 'free_onset' => '01:30', 'free_offset' => '09:30',
        ])->assertOk()->assertSee('2 jam 30 menit')->assertSee('03:00')->assertSee('05:30');
    }

    public function test_goal_is_saved_and_reviewed(): void
    {
        $user = $this->participant('intervention');

        $this->actingAs($user)->post('/target', [
            'target' => 'Mulai tidur sebelum 23.30 minimal 4 hari',
            'action_plan' => ['Matikan layar 30 menit lebih awal', '', ''],
            'if_then_plan' => 'Jika tugas menumpuk, maka buat batas waktu',
        ])->assertRedirect();

        $goal = Goal::firstWhere('user_id', $user->id);
        $this->assertSame(['Matikan layar 30 menit lebih awal'], $goal->action_plan);

        $this->actingAs($user)->patch('/target/'.$goal->id, ['status' => 'partial'])->assertRedirect();
        $this->assertSame('partial', $goal->fresh()->status);
    }

    public function test_weekly_reflection_is_saved_once_per_week(): void
    {
        $user = $this->participant('intervention');

        foreach ([2, 4] as $rating) {
            $this->actingAs($user)->post('/refleksi', ['ease_rating' => $rating, 'barriers' => ['perangkat']])->assertRedirect();
        }

        $this->assertSame(1, WeeklyReflection::where('user_id', $user->id)->count());
        $this->assertSame(4, WeeklyReflection::firstWhere('user_id', $user->id)->ease_rating);
    }

    public function test_assessment_form_renders_researcher_items_and_logs_answers(): void
    {
        $user = $this->participant('comparison');
        $instrument = Instrument::create(['code' => 'UJI', 'name' => 'Instrumen Uji', 'phases' => ['T0']]);
        $item = $instrument->items()->create(['order' => 1, 'text' => 'Butir uji', 'type' => 'likert', 'options' => ['values' => [1, 2, 3, 4, 5]]]);

        // Seeder: jendela T0 terbuka sampai kohort dimulai (Senin depan).
        $this->actingAs($user)->get('/pengukuran/T0/UJI')->assertOk()->assertSee('Butir uji');
        $this->actingAs($user)->post('/pengukuran/T0/UJI', ['answers' => [$item->id => 9]])->assertSessionHasErrors('answers.'.$item->id);
        $this->actingAs($user)->post('/pengukuran/T0/UJI', ['answers' => [$item->id => 4]])->assertRedirect(route('assessments.index'));

        $response = AssessmentResponse::firstWhere('user_id', $user->id);
        $this->assertNotNull($response->submitted_at);
        $this->assertSame('4', $response->items()->first()->value);

        // T1 belum dibuka.
        $instrument->update(['phases' => ['T0', 'T1']]);
        $this->actingAs($user)->get('/pengukuran/T1/UJI')->assertForbidden();
    }

    public function test_password_change_and_consent_withdrawal(): void
    {
        $user = $this->participant('intervention');

        $this->actingAs($user)->put('/profil/kata-sandi', [
            'current_password' => 'password', 'password' => 'sandi-baru-123', 'password_confirmation' => 'sandi-baru-123',
        ])->assertSessionHasNoErrors();

        $this->actingAs($user)->post('/persetujuan/tarik', ['confirm' => '1'])->assertRedirect(route('login'));
        $this->assertSame('withdrawn', $user->fresh()->status);
        $this->assertGuest();

        $this->post('/masuk', ['username' => $user->username, 'password' => 'sandi-baru-123'])->assertSessionHasErrors('username');
    }

    public function test_notification_inbox_marks_all_read(): void
    {
        $user = $this->participant('intervention');
        $user->notify(new \App\Notifications\SessionReleased(\App\Models\LearningSession::first()));

        $this->actingAs($user)->get('/notifikasi')->assertOk()->assertSee('Sesi 01 sudah tersedia');
        $this->actingAs($user)->post('/notifikasi/baca-semua')->assertRedirect();
        $this->assertSame(0, $user->unreadNotifications()->count());
    }
}
