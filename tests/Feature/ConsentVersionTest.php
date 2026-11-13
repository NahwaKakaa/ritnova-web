<?php

namespace Tests\Feature;

use App\Models\ConsentVersion;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesParticipants;
use Tests\TestCase;

class ConsentVersionTest extends TestCase
{
    use CreatesParticipants, RefreshDatabase;

    private User $admin;

    private User $researcher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        Storage::fake('local');
        $this->admin = User::firstWhere('role', User::ROLE_ADMIN);
        $this->researcher = User::create(['username' => 'peneliti', 'name' => 'P', 'role' => 'researcher', 'password' => 'password', 'status' => 'active']);
    }

    public function test_new_version_requires_reconsent_and_published_version_is_frozen(): void
    {
        $user = $this->participant('intervention');
        $old = ConsentVersion::current();

        $this->actingAs($this->researcher)->get('/admin/persetujuan')->assertOk()->assertSee($old->version)->assertDontSee('Buat versi baru');
        $this->actingAs($this->researcher)->post('/admin/persetujuan', ['version' => 'v9', 'title' => 'x', 'body' => 'y'])->assertForbidden();

        $this->actingAs($this->admin)->get('/admin/persetujuan/baru')->assertOk()->assertSee($old->title);
        $this->actingAs($this->admin)->post('/admin/persetujuan', [
            'version' => 'v1.0', 'title' => 'Persetujuan Penelitian', 'body' => "Naskah resmi.\nBaris kedua.",
            'document' => UploadedFile::fake()->create('info.pdf', 120, 'application/pdf'),
        ])->assertRedirect();
        $draft = ConsentVersion::firstWhere('version', 'v1.0');
        $this->assertFalse($draft->is_active);
        Storage::disk('local')->assertExists($draft->document_path);

        // Draf belum terlihat peserta.
        $this->actingAs($user)->get('/beranda')->assertOk();
        $this->actingAs($user)->get('/dokumen-persetujuan/'.$draft->id)->assertNotFound();
        $this->actingAs($this->admin)->get('/dokumen-persetujuan/'.$draft->id)->assertOk();

        $this->actingAs($this->admin)->post('/admin/persetujuan/'.$draft->id.'/terbitkan')->assertRedirect('/admin/persetujuan');
        $this->assertTrue($draft->fresh()->is_active);
        $this->assertFalse($old->fresh()->is_active);
        $this->assertDatabaseHas('audit_logs', ['action' => 'consent_published']);

        // Peserta wajib menyetujui ulang.
        $this->actingAs($user)->get('/beranda')->assertRedirect(route('consent.show'));
        $this->actingAs($user)->get('/persetujuan')->assertOk()->assertSee('diperbarui dari versi '.$old->version, false)->assertSee('Baris kedua.');
        $this->actingAs($user)->get('/dokumen-persetujuan/'.$draft->id)->assertOk();
        $this->actingAs($user)->post('/persetujuan', ['agree' => '1'])->assertRedirect(route('dashboard'));
        $this->actingAs($user)->get('/beranda')->assertOk();

        // Versi terbit beku.
        $this->actingAs($this->admin)->put('/admin/persetujuan/'.$draft->id, ['version' => 'v1.0', 'title' => 'Ubah', 'body' => 'Ubah'])->assertForbidden();
        $this->actingAs($this->admin)->delete('/admin/persetujuan/'.$draft->id)->assertSessionHasErrors('delete');
        $this->actingAs($this->admin)->get('/admin/persetujuan')->assertOk()->assertSee('Aktif')->assertSee('Arsip');
    }

    public function test_draft_can_be_edited_and_deleted(): void
    {
        $this->actingAs($this->admin)->post('/admin/persetujuan', ['version' => 'v2', 'title' => 'A', 'body' => 'B'])->assertRedirect();
        $draft = ConsentVersion::firstWhere('version', 'v2');
        $this->actingAs($this->admin)->put('/admin/persetujuan/'.$draft->id, ['version' => 'v2.1', 'title' => 'A2', 'body' => 'B2'])->assertSessionHas('status');
        $this->assertSame('v2.1', $draft->fresh()->version);
        $this->actingAs($this->admin)->post('/admin/persetujuan', ['version' => 'v2.1', 'title' => 'A', 'body' => 'B'])->assertSessionHasErrors('version');
        $this->actingAs($this->admin)->post('/admin/persetujuan', ['version' => 'v3', 'title' => 'A', 'body' => 'B', 'document' => UploadedFile::fake()->create('x.docx', 10)])->assertSessionHasErrors('document');
        $this->actingAs($this->admin)->delete('/admin/persetujuan/'.$draft->id)->assertRedirect('/admin/persetujuan');
        $this->assertNull(ConsentVersion::find($draft->id));
    }
}
