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

    public function test_rmeq_is_t0_only_and_scores_as_sum_of_keys(): void
    {
        $this->artisan('ritnova:instrument', ['file' => 'database/instruments/rmeq.php'])->assertSuccessful();

        $rmeq = Instrument::where('code', 'RMEQ')->firstOrFail();
        $this->assertSame(['T0'], $rmeq->phases);
        $this->assertCount(5, $rmeq->items);
        $this->assertSame(['6', '4', '2', '0'], $rmeq->items->last()->options['values']);

        $user = $this->participant('comparison');
        $this->actingAs($user)->get('/pengukuran/T0/RMEQ')->assertOk()
            ->assertSee('Pukul 22.15 – 00.30 (malam s.d. tengah malam)')->assertSee('Jelas tipe malam')->assertDontSee('rMEQ-01');

        // Pilihan pertama di setiap butir: 5 + 1 + 5 + 5 + 6 = 22.
        $answers = $rmeq->items->mapWithKeys(fn ($item) => [$item->id => $item->options['values'][0]])->all();
        $this->actingAs($user)->post('/pengukuran/T0/RMEQ', ['answers' => $answers])->assertRedirect(route('assessments.index'));
        $this->assertEquals(22, (float) AssessmentResponse::where('user_id', $user->id)->where('instrument_id', $rmeq->id)->value('score'));
    }
}
