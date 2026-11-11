<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Satu baris per malam. diary_date = tanggal malam MULAI tidur.
        Schema::create('sleep_diaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('diary_date');
            $table->string('day_type');                    // workday | free_day (hari setelah bangun)
            $table->timestamp('bed_at')->nullable();       // masuk tempat tidur (opsional)
            $table->timestamp('sleep_onset')->nullable();  // mulai benar-benar tidur
            $table->timestamp('sleep_offset')->nullable(); // bangun terakhir
            $table->boolean('used_alarm')->nullable();
            $table->unsignedSmallInteger('nap_minutes')->nullable();
            $table->text('notes')->nullable();
            $table->string('status')->default('partial');  // complete | partial
            $table->timestamps();

            $table->unique(['user_id', 'diary_date']);
        });

        // Riwayat edit diary (koreksi tetap tercatat).
        Schema::create('sleep_diary_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sleep_diary_id')->constrained()->cascadeOnDelete();
            $table->foreignId('edited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('before')->nullable();
            $table->json('after');
            $table->timestamp('created_at');
        });

        // Hasil kalkulasi SJL per periode (data turunan, bisa dihitung ulang).
        Schema::create('sjl_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('phase')->nullable();           // T0 | T1 | T2 | null (berjalan)
            $table->date('period_start');
            $table->date('period_end');
            $table->unsignedSmallInteger('valid_workdays');
            $table->unsignedSmallInteger('valid_free_days');
            $table->decimal('msw_hours', 5, 2)->nullable(); // midpoint hari kuliah (jam sejak 00:00)
            $table->decimal('msf_hours', 5, 2)->nullable(); // midpoint hari bebas
            $table->unsignedSmallInteger('sjl_minutes')->nullable(); // null = data belum cukup
            $table->string('algorithm_version');
            $table->timestamp('computed_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sjl_results');
        Schema::dropIfExists('sleep_diary_revisions');
        Schema::dropIfExists('sleep_diaries');
    }
};
