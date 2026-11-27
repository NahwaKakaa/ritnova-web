<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\Instrument;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Pasang instrumen dari berkas data di repo (butir dari dokumen peneliti, disalin apa adanya).
 * Tidak pernah menimpa: bila kode sudah ada dan punya butir, perintah berhenti.
 * Perubahan versi berikutnya dilakukan lewat menu Instrumen di panel admin.
 */
class InstallInstrument extends Command
{
    protected $signature = 'ritnova:instrument {file : Berkas PHP di database/instruments}';

    protected $description = 'Pasang instrumen penelitian dari berkas data';

    public function handle(): int
    {
        $path = base_path($this->argument('file'));
        if (! is_file($path) || ! str_starts_with(realpath($path), base_path('database/instruments'))) {
            $this->error('Berkas tidak ditemukan di database/instruments.');

            return self::FAILURE;
        }
        $data = require $path;

        $existing = Instrument::where('code', $data['code'])->first();
        if ($existing && $existing->items()->exists()) {
            $this->error("Instrumen {$data['code']} sudah ada (versi {$existing->version}, {$existing->items()->count()} butir). Tidak ada yang diubah.");

            return self::FAILURE;
        }

        $instrument = DB::transaction(function () use ($data, $existing) {
            $instrument = $existing ?? new Instrument(['code' => $data['code'], 'version' => '1']);
            $instrument->fill(['name' => $data['name'], 'description' => $data['description'], 'phases' => $data['phases'], 'scoring' => $data['scoring']])->save();
            foreach (array_values($data['items']) as $i => [$text, $type, $options]) {
                $instrument->items()->create(['order' => $i + 1, 'text' => $text, 'type' => $type, 'options' => $options, 'required' => true, 'reverse_scored' => false]);
            }

            return $instrument;
        });
        AuditLog::record('instrument_installed', $instrument, null, [
            'code' => $instrument->code, 'version' => $instrument->version, 'items' => count($data['items']), 'source' => $this->argument('file'),
        ]);

        $this->info("Instrumen {$instrument->code} versi {$instrument->version} terpasang: ".count($data['items']).' butir, fase '.implode(', ', $instrument->phases).'.');

        return self::SUCCESS;
    }
}
