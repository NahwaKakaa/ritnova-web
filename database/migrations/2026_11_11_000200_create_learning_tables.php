<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 12 sesi edukasi. Nama tabel "learning_sessions" agar tidak bentrok
        // dengan tabel "sessions" milik Laravel.
        Schema::create('learning_sessions', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('number')->unique(); // 1..12
            $table->unsignedTinyInteger('week');             // 1..4
            $table->string('title');
            $table->text('objective')->nullable();
            $table->string('media_type');                    // video | infographic | podcast
            $table->string('media_url')->nullable();         // contoh: YouTube unlisted
            $table->string('media_alt_text')->nullable();    // untuk infografis
            $table->longText('transcript')->nullable();      // subtitle/transkrip
            $table->text('key_message')->nullable();
            $table->text('action_prompt')->nullable();       // tugas praktik/aksi
            $table->unsignedSmallInteger('estimated_minutes')->nullable();
            $table->unsignedInteger('content_version')->default(1);
            $table->timestamps();
        });

        // Cek pemahaman (2–3 butir singkat per sesi).
        Schema::create('session_quiz_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('learning_session_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('order')->default(1);
            $table->text('question');
            $table->json('options');
            $table->unsignedTinyInteger('correct_index')->nullable();
            $table->timestamps();
        });

        // Jadwal rilis per kohort. Prasyarat & jendela diatur admin tanpa ubah kode.
        Schema::create('cohort_session_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cohort_id')->constrained()->cascadeOnDelete();
            $table->foreignId('learning_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('prerequisite_session_id')->nullable()
                ->constrained('learning_sessions')->nullOnDelete();
            $table->timestamp('release_at');
            $table->timestamp('close_at')->nullable();
            $table->timestamps();

            $table->unique(['cohort_id', 'learning_session_id']);
        });

        // Status paparan per peserta per sesi (satu baris per sesi).
        Schema::create('session_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('learning_session_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('content_version')->default(1);
            $table->timestamp('first_open_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->decimal('media_completion_pct', 5, 4)->default(0); // 0..1
            $table->timestamp('media_completed_at')->nullable();
            $table->json('quiz_answers')->nullable();
            $table->timestamp('quiz_completed_at')->nullable();
            $table->text('action_response')->nullable();
            $table->timestamp('action_completed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedInteger('active_seconds')->default(0);
            $table->boolean('is_late')->default(false);
            $table->boolean('protocol_deviation')->default(false);
            $table->timestamps();

            $table->unique(['user_id', 'learning_session_id']);
        });

        // Log kejadian mentah untuk analisis paparan (append-only).
        Schema::create('exposure_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('learning_session_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event'); // open | start | media_progress | media_complete | quiz_submit | action_submit | complete | heartbeat
            $table->unsignedInteger('content_version')->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('occurred_at');

            $table->index(['user_id', 'learning_session_id', 'event']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exposure_events');
        Schema::dropIfExists('session_progress');
        Schema::dropIfExists('cohort_session_schedules');
        Schema::dropIfExists('session_quiz_items');
        Schema::dropIfExists('learning_sessions');
    }
};
