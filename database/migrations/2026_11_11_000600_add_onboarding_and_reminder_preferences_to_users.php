<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('onboarded_at')->nullable()->after('status');
            // Preferensi pengingat (Layar 16): opt-in, kanal, jam wajar. Nomor WA dikelola klien.
            $table->boolean('reminder_opt_in')->default(true)->after('onboarded_at');
            $table->string('reminder_channel')->default('web')->after('reminder_opt_in'); // web | whatsapp | both
            $table->string('reminder_time', 5)->nullable()->after('reminder_channel');   // HH:MM
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['onboarded_at', 'reminder_opt_in', 'reminder_channel', 'reminder_time']);
        });
    }
};
