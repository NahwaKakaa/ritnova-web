<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Aturan skoring + versi kalkulator yang dipakai saat skor dihitung, agar skor dapat diaudit.
        Schema::table('assessment_responses', function (Blueprint $table) {
            $table->json('scoring_snapshot')->nullable()->after('score');
            $table->timestamp('scored_at')->nullable()->after('scoring_snapshot');
        });
    }

    public function down(): void
    {
        Schema::table('assessment_responses', function (Blueprint $table) {
            $table->dropColumn(['scoring_snapshot', 'scored_at']);
        });
    }
};
