<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cohorts', function (Blueprint $table) {
            $table->id();
            $table->string('name');                       // contoh: Kohort FKM 2026
            $table->string('timezone')->default('Asia/Makassar');
            $table->date('starts_on')->nullable();         // Senin minggu pertama
            $table->string('late_policy')->default('catch_up'); // strict | catch_up
            $table->string('status')->default('draft');    // draft | active | finished
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            // Kode peserta (RV-001) atau username admin/peneliti.
            $table->string('username')->unique();
            $table->string('name')->nullable();            // hanya untuk admin/peneliti
            $table->string('email')->nullable()->unique(); // opsional, untuk notifikasi email
            $table->string('role')->default('participant'); // participant | researcher | admin
            $table->foreignId('cohort_id')->nullable()->constrained()->nullOnDelete();
            $table->string('study_group')->nullable();     // intervention | comparison
            $table->string('status')->default('active');   // active | withdrawn
            $table->string('password');
            $table->timestamp('last_login_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
        Schema::dropIfExists('cohorts');
    }
};
