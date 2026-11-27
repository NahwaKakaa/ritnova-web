<?php

namespace Tests\Feature;

use App\Models\AssessmentResponse;
use App\Models\Instrument;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesParticipants;
use Tests\TestCase;

/** SSE-SJL versi 1.0 (Lampiran 06): 16 butir skala 0–10, skor rerata, fase T0/M2/T1/T2. */
class InstallInstrumentTest extends TestCase
{
    use CreatesParticipants, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_sse_sjl_installs_once_and_scores_as_mean(): void
    {
        $this->artisan('ritnova:instrument', ['file' => 'database/instruments/sse-sjl.php'])->assertSuccessful();
        $this->artisan('ritnova:instrument', ['file' => 'database/instruments/sse-sjl.php'])->assertFailed();
        $this->artisan('ritnova:instrument', ['file' => '.env'])->assertFailed();

        $sse = Instrument::where('code', 'SSE-SJL')->firstOrFail();
        $this->assertSame('1', $sse->version);
        $this->assertSame(['T0', 'M2', 'T1', 'T2'], $sse->phases);
        $this->assertCount(16, $sse->items);
        $this->assertSame(range(0, 10), array_map('intval', $sse->items->first()->options['values']));
        $this->assertStringStartsWith('Menetapkan jam tidur yang realistis', $sse->items->first()->text);
        $this->assertStringStartsWith('Kembali ke jadwal tidur–bangun', $sse->items->last()->text);

        // Seeder: jendela T0 terbuka. Kode butir tidak tampil ke peserta.
        $user = $this->participant('intervention');
        $this->actingAs($user)->get('/pengukuran/T0/SSE-SJL')->assertOk()
            ->assertSee('Sangat yakin mampu')->assertSee('Menghentikan penggunaan gawai')->assertDontSee('SSE-04');

        $answers = $sse->items->mapWithKeys(fn ($item) => [$item->id => $item->order <= 8 ? 10 : 5])->all();
        $this->actingAs($user)->post('/pengukuran/T0/SSE-SJL', ['answers' => $answers])->assertRedirect(route('assessments.index'));

        $this->assertEquals(7.5, (float) AssessmentResponse::where('user_id', $user->id)->where('instrument_id', $sse->id)->value('score'));
    }
}
