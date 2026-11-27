<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Profil dasar peserta (data pribadi): hanya untuk peneliti/admin, tidak ikut ekspor kecuali kelas, jenis kelamin, usia.
        Schema::create('participant_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('birth_place', 100);
            $table->date('birth_date');
            $table->string('sex', 1);              // L | P
            $table->string('class_group', 1);      // A | B | C
            $table->string('residence', 20);       // rumah_ortu | kost | apartemen | lainnya
            $table->string('residence_other', 100)->nullable();
            $table->string('whatsapp', 15);        // 08xxxxxxxxx
            $table->timestamps();
        });

        // Skrining kelayakan (Lampiran 02). Satu kali per peserta; jawaban tidak diubah peserta.
        Schema::create('eligibility_screenings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->json('answers');               // {"1":"ya",...}
            $table->string('result', 20);          // eligible | clarify | ineligible
            $table->json('flags');                 // {ineligible:[..], clarify:[..], contact, minor}
            $table->unsignedTinyInteger('age')->nullable();
            $table->string('rules_version', 40);
            $table->timestamp('submitted_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eligibility_screenings');
        Schema::dropIfExists('participant_profiles');
    }
};
