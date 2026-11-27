<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesParticipants;
use Tests\TestCase;

/** Profil dasar tambahan dan tahap Skrining Kelayakan (permintaan peneliti, 27 Nov 2026). */
class ScreeningTest extends TestCase
{
    use CreatesParticipants, RefreshDatabase;

    /** Peserta 17 tahun (perlu prosedur persetujuan khusus). */
    private function profile(array $override = []): array
    {
        return $override + [
            'code_confirmed' => '1', 'reminder_opt_in' => '1', 'reminder_channel' => 'web', 'reminder_time' => '20:00',
            'birth_place' => 'Makassar', 'birth_date' => now()->subYears(17)->subMonths(2)->toDateString(), 'sex' => 'L', 'class_group' => 'C',
            'residence' => 'kost', 'whatsapp' => '085212345678',
        ];
    }

    private const ANSWERS = [1 => 'tidak', 2 => 'ya', 3 => 'belum_pasti', 4 => 'ya', 5 => 'ya', 6 => 'tidak', 7 => 'tidak', 8 => 'ya_hubungi'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_profile_validates_whatsapp_and_other_residence(): void
    {
        $user = $this->participant('intervention', onboarded: false);

        $this->actingAs($user)->post('/persiapan/profil', $this->profile(['whatsapp' => '+6285212345678', 'residence' => 'lainnya']))
            ->assertSessionHasErrors(['whatsapp', 'residence_other']);
        $this->assertNull($user->fresh()->profile);
    }

    public function test_screening_needs_profile_and_is_stored_once_with_flags(): void
    {
        $user = $this->participant('intervention', onboarded: false);

        $this->actingAs($user)->post('/persiapan/skrining', ['q' => self::ANSWERS])->assertSessionHasErrors('onboarding');

        $this->actingAs($user)->post('/persiapan/profil', $this->profile())->assertRedirect(route('onboarding'));
        $this->actingAs($user)->get('/persiapan')->assertOk()->assertSee('Langkah 3 dari 5')->assertSee('FKM UNHAS angkatan 2026');

        $this->actingAs($user)->post('/persiapan/skrining', ['q' => [1 => 'ya']])->assertSessionHasErrors('q.2');
        $this->actingAs($user)->post('/persiapan/skrining', ['q' => self::ANSWERS])->assertRedirect(route('onboarding'));

        $s = $user->fresh()->screening;
        $this->assertSame('ineligible', $s->result);
        $this->assertSame([1], $s->flags['ineligible']);
        $this->assertSame([3], $s->flags['clarify']);
        $this->assertTrue($s->flags['contact']);
        $this->assertSame(17, $s->age);
        $this->assertTrue($s->flags['minor']);

        // Tidak bisa diubah peserta; hasil tidak ditampilkan ke peserta; tetap boleh lanjut.
        $this->actingAs($user)->post('/persiapan/skrining', ['q' => array_fill_keys(range(1, 8), 'ya')]);
        $this->assertSame('ineligible', $user->fresh()->screening->result);
        $this->actingAs($user)->get('/persiapan')->assertOk()->assertDontSee('Tidak memenuhi kriteria');
        $this->actingAs($user)->post('/persiapan/selesai')->assertRedirect(route('dashboard'));
    }

    public function test_admin_sees_recap_and_details_and_export_excludes_contact_data(): void
    {
        $user = $this->participant('comparison', onboarded: false);
        $this->actingAs($user)->post('/persiapan/profil', $this->profile());
        $this->actingAs($user)->post('/persiapan/skrining', ['q' => self::ANSWERS]);
        $admin = User::where('role', User::ROLE_ADMIN)->first();

        $this->actingAs($admin)->get('/admin/skrining')->assertOk()
            ->assertSee('Rekap alur rekrutmen')->assertSee('Tidak memenuhi kriteria')
            ->assertSee('Tidak: no. 1 · Klarifikasi: no. 3 · Minta dihubungi · Di bawah 18 tahun');
        $this->actingAs($admin)->get('/admin/peserta/'.$user->id)->assertOk()->assertSee('085212345678')->assertSee('Makassar');

        $csv = $this->actingAs($admin)->get('/admin/ekspor/screening')->assertOk()->streamedContent();
        $this->assertStringContainsString($user->username.',C,tidak,ya,belum_pasti', $csv);
        $participants = $this->actingAs($admin)->get('/admin/ekspor/participants')->streamedContent();
        $this->assertStringContainsString('class_group,sex,age_at_enrollment,residence', $participants);
        foreach ([$csv, $participants] as $out) {
            $this->assertStringNotContainsString('085212345678', $out);
            $this->assertStringNotContainsString($this->profile()['birth_date'], $out);
        }
    }

    public function test_onboarded_participant_without_screening_gets_dashboard_task(): void
    {
        $user = $this->participant('intervention');
        $user->screening()->delete();

        $this->actingAs($user)->get('/beranda')->assertOk()->assertSee('Selesaikan persiapan awal');
    }
}
