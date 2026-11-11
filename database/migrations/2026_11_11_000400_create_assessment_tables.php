<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Jendela pengukuran T0/T1/T2 per kohort (tidak terkunci oleh progres modul).
        Schema::create('assessment_windows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cohort_id')->constrained()->cascadeOnDelete();
            $table->string('phase');                 // T0 | T1 | T2
            $table->timestamp('opens_at');
            $table->timestamp('closes_at')->nullable();
            $table->timestamps();

            $table->unique(['cohort_id', 'phase']);
        });

        // Instrumen tervalidasi. Butir & skoring diisi peneliti, JANGAN dikarang.
        Schema::create('instruments', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();        // contoh: SSE (sleep self-efficacy)
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('version')->default('1');
            $table->json('phases');                  // ["T0","T1","T2"]
            $table->json('scoring')->nullable();     // aturan skoring
            $table->timestamps();
        });

        Schema::create('instrument_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instrument_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('order');
            $table->text('text');
            $table->string('type');                  // likert | choice | number | time | text
            $table->json('options')->nullable();
            $table->boolean('reverse_scored')->default(false);
            $table->boolean('required')->default(true);
            $table->timestamps();
        });

        Schema::create('assessment_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('instrument_id')->constrained();
            $table->string('phase');
            $table->string('instrument_version');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->decimal('score', 8, 2)->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'instrument_id', 'phase']);
        });

        Schema::create('assessment_item_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_response_id')->constrained()->cascadeOnDelete();
            $table->foreignId('instrument_item_id')->constrained();
            $table->text('value')->nullable();
            $table->timestamp('answered_at');
            $table->timestamps();

            $table->unique(['assessment_response_id', 'instrument_item_id'], 'item_response_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_item_responses');
        Schema::dropIfExists('assessment_responses');
        Schema::dropIfExists('instrument_items');
        Schema::dropIfExists('instruments');
        Schema::dropIfExists('assessment_windows');
    }
};
