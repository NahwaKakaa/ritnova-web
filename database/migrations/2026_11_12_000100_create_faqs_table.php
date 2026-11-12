<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('faqs', function (Blueprint $table) {
            $table->id();
            $table->string('question', 255);
            $table->text('answer');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });

        // Isi awal = pertanyaan yang sebelumnya tertulis langsung di landing page.
        $rows = [
            ['Siapa yang bisa ikut?', 'Mahasiswa yang terdaftar dalam penelitian dan menerima kode peserta dari tim peneliti.'],
            ['Berapa lama setiap hari?', 'Mencatat tidur kurang dari satu menit. Satu sesi belajar sekitar 8 menit, tiga kali seminggu.'],
            ['Apakah RITNOVA mendiagnosis gangguan tidur?', 'Tidak. RITNOVA adalah program edukasi. Bila kamu memiliki keluhan tidur yang bermakna, konsultasikan dengan tenaga kesehatan.'],
            ['Bagaimana jika lupa kata sandi?', 'Hubungi tim peneliti untuk mengatur ulang kata sandi.'],
        ];
        foreach ($rows as $i => [$question, $answer]) {
            DB::table('faqs')->insert([
                'question' => $question, 'answer' => $answer, 'sort_order' => $i + 1,
                'is_published' => true, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('faqs');
    }
};
