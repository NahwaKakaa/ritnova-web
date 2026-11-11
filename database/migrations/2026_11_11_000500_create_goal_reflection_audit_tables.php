<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('week');
            $table->string('target');                  // contoh: mulai tidur sebelum 23:30 minimal 4 hari
            $table->json('action_plan')->nullable();   // daftar langkah
            $table->text('if_then_plan')->nullable();  // rencana jika–maka
            $table->string('status')->default('active'); // active | achieved | partial | not_achieved
            $table->timestamps();
        });

        Schema::create('weekly_reflections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('week');
            $table->unsignedTinyInteger('ease_rating')->nullable(); // 1..5
            $table->json('barriers')->nullable();
            $table->text('adjustment')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'week']);
        });

        // Audit trail: siapa mengubah apa, kapan.
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action');                  // created | updated | deleted | login | export | ...
            $table->string('auditable_type')->nullable();
            $table->unsignedBigInteger('auditable_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at');

            $table->index(['auditable_type', 'auditable_id']);
        });

        // Notifikasi di dalam website (database notifications bawaan Laravel).
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('weekly_reflections');
        Schema::dropIfExists('goals');
    }
};
