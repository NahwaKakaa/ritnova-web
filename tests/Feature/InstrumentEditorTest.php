<?php

namespace Tests\Feature;

use App\Models\AssessmentResponse;
use App\Models\Instrument;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesParticipants;
use Tests\TestCase;

class InstrumentEditorTest extends TestCase
{
    use CreatesParticipants, RefreshDatabase;

    private User $researcher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->researcher = User::create(['username' => 'peneliti', 'name' => 'P', 'role' => 'researcher', 'password' => 'password', 'status' => 'active']);
    }

    private function payload(array $override = []): array
    {
        return array_replace([
            'code' => 'sse', 'name' => 'Sleep Self-Efficacy', 'description' => 'Pilih yang paling sesuai.',
            'phases' => ['T0', 'T2'], 'scoring_method' => 'sum', 'scoring_items' => '',
            'items' => [
                ['text' => 'Saya bisa tidur tepat waktu.', 'type' => 'likert', 'choices' => "1 = Sangat tidak yakin\n2\n3\n4\n5 = Sangat yakin", 'required' => '1', 'reverse' => '0'],
                ['text' => 'Saya sulit tidur di hari bebas.', 'type' => 'likert', 'choices' => "1\n2\n3\n4\n5", 'required' => '1', 'reverse' => '1'],
                ['text' => 'Jam tidur siang (menit).', 'type' => 'number', 'min' => '0', 'max' => '240', 'required' => '0', 'reverse' => '0'],
                ['text' => 'Catatan', 'type' => 'text', 'required' => '0'],
            ],
        ], $override);
    }

    public function test_researcher_builds_instrument_and_participant_gets_audited_score(): void
    {
        $this->actingAs($this->researcher)->get('/admin/instrumen')->assertOk()->assertSee('Belum ada instrumen');
        $this->actingAs($this->researcher)->post('/admin/instrumen', $this->payload())->assertRedirect();

        $instrument = Instrument::firstWhere('code', 'SSE');
        $this->assertSame(['T0', 'T2'], $instrument->phases);
        $this->assertSame(4, $instrument->items()->count());
        $first = $instrument->items->first();
        $this->assertSame(['1', '2', '3', '4', '5'], $first->options['values']);
        $this->assertSame('Sangat tidak yakin', $first->options['labels'][0]);
        $this->assertEquals(['min' => 0, 'max' => 240], $instrument->items[2]->options);
        $this->actingAs($this->researcher)->get('/admin/instrumen/'.$instrument->id)->assertOk()->assertSee('Saya sulit tidur di hari bebas.');

        // Peserta mengisi T0: skor = 4 + (1+5-2=4) + 30 = 38 (butir teks tidak dihitung).
        $user = $this->participant('comparison');
        $items = $instrument->items;
        $this->actingAs($user)->post('/pengukuran/T0/SSE', ['answers' => [
            $items[0]->id => '4', $items[1]->id => '2', $items[2]->id => '30', $items[3]->id => 'ok',
        ]])->assertRedirect(route('assessments.index'));
        $response = AssessmentResponse::firstWhere('user_id', $user->id);
        $this->assertSame(38.0, $response->score);
        $this->assertSame('sum', $response->scoring_snapshot['rule']['method']);
        $this->assertSame('score-v1', $response->scoring_snapshot['calculator']);

        // Sudah ada respons: butir terkunci, aturan skoring berubah → versi naik, lalu hitung ulang.
        $this->actingAs($this->researcher)->get('/admin/instrumen/'.$instrument->id)->assertSee('Butir dikunci');
        $this->actingAs($this->researcher)->put('/admin/instrumen/'.$instrument->id, [...$this->payload(['scoring_method' => 'mean', 'scoring_items' => '1, 2']), 'items' => []])
            ->assertSessionHas('status');
        $instrument->refresh();
        $this->assertSame('2', $instrument->version);
        $this->assertSame(4, $instrument->items()->count(), 'butir tidak boleh berubah setelah ada respons');
        $this->actingAs($this->researcher)->post('/admin/instrumen/'.$instrument->id.'/hitung-ulang')->assertSessionHas('status');
        $this->assertSame(4.0, $response->fresh()->score);
        $this->assertDatabaseHas('audit_logs', ['action' => 'scores_recomputed']);

        // Tidak bisa dihapus; bisa disalin.
        $this->actingAs($this->researcher)->delete('/admin/instrumen/'.$instrument->id)->assertSessionHasErrors('delete');
        $this->actingAs($this->researcher)->post('/admin/instrumen/'.$instrument->id.'/duplikat')->assertRedirect();
        $copy = Instrument::firstWhere('code', 'SSE-B');
        $this->assertSame(4, $copy->items()->count());
        $this->actingAs($this->researcher)->delete('/admin/instrumen/'.$copy->id)->assertRedirect('/admin/instrumen');
        $this->assertNull(Instrument::find($copy->id));
    }

    public function test_missing_counted_answer_gives_empty_score(): void
    {
        $this->actingAs($this->researcher)->post('/admin/instrumen', $this->payload(['scoring_items' => '1, 3']))->assertRedirect();
        $instrument = Instrument::firstWhere('code', 'SSE');
        $user = $this->participant('intervention');
        $items = $instrument->items;
        $this->actingAs($user)->post('/pengukuran/T0/SSE', ['answers' => [$items[0]->id => '4', $items[1]->id => '2']])->assertRedirect();
        $this->assertNull(AssessmentResponse::firstWhere('user_id', $user->id)->score);
    }

    public function test_editor_validation(): void
    {
        $bad = $this->payload(['items' => [
            ['text' => 'A', 'type' => 'likert', 'choices' => '1'],
            ['text' => 'B', 'type' => 'text', 'reverse' => '1'],
            ['text' => 'C', 'type' => 'number', 'min' => '5', 'max' => '1'],
        ], 'scoring_items' => '9']);
        $this->actingAs($this->researcher)->post('/admin/instrumen', $bad)
            ->assertSessionHasErrors(['items.0.choices', 'items.1.reverse', 'items.2.max', 'scoring_items']);
        $this->actingAs($this->researcher)->post('/admin/instrumen', $this->payload(['code' => 'x y']))->assertSessionHasErrors('code');
        $this->assertSame(0, Instrument::count());
    }
}
