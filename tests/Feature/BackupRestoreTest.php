<?php

namespace Tests\Feature;

use App\Models\Faq;
use App\Models\SleepDiary;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesParticipants;
use Tests\TestCase;

class BackupRestoreTest extends TestCase
{
    use CreatesParticipants, RefreshDatabase;

    public function test_backup_then_restore_brings_data_back(): void
    {
        Storage::fake('local');
        $this->seed(DatabaseSeeder::class);
        $user = $this->participant('intervention');
        SleepDiary::create(['user_id' => $user->id, 'diary_date' => '2026-11-10', 'day_type' => 'workday', 'status' => 'partial', 'nap_minutes' => 20]);
        $faqs = Faq::count();

        $this->artisan('ritnova:backup')->assertSuccessful();
        $file = basename(Storage::disk('local')->files('backups')[0]);

        SleepDiary::query()->delete();
        Faq::query()->delete();
        $user->delete();

        $this->artisan('ritnova:restore', ['--list' => true])->expectsOutputToContain($file)->assertSuccessful();
        $this->artisan('ritnova:restore', ['file' => 'tidak-ada.jsonl.gz', '--force' => true])->assertFailed();
        $this->artisan('ritnova:restore', ['file' => $file])->expectsConfirmation('Isi tabel di atas akan DIGANTI dengan isi cadangan. Lanjutkan?', 'no')->assertFailed();
        $this->assertSame(0, SleepDiary::count());

        $this->artisan('ritnova:restore', ['file' => $file, '--force' => true])->assertSuccessful();
        $this->assertSame(20, SleepDiary::firstWhere('user_id', $user->id)->nap_minutes);
        $this->assertSame($faqs, Faq::count());
        $this->assertCount(2, Storage::disk('local')->files('backups'), 'keadaan sebelum restore ikut dicadangkan');
    }
}
