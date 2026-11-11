<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\Participant;
use Illuminate\Support\Facades\Route;

Route::get('/', LandingController::class)->name('landing');

// --- Autentikasi ---
Route::middleware('guest')->group(function () {
    Route::get('/masuk', [LoginController::class, 'show'])->name('login');
    Route::post('/masuk', [LoginController::class, 'store'])->middleware('throttle:10,1');
});
Route::post('/keluar', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

// --- Peserta ---
Route::middleware(['auth', 'role:participant'])->group(function () {
    Route::get('/persetujuan', [Participant\ConsentController::class, 'show'])->name('consent.show');
    Route::post('/persetujuan', [Participant\ConsentController::class, 'store'])->name('consent.store');

    Route::middleware('consented')->group(function () {
        Route::get('/beranda', Participant\DashboardController::class)->name('dashboard');

        // Diary tidur: kedua kelompok (sesuai protokol).
        Route::get('/catatan-tidur', [Participant\DiaryController::class, 'index'])->name('diary.index');
        Route::get('/catatan-tidur/baru', [Participant\DiaryController::class, 'create'])->name('diary.create');
        Route::post('/catatan-tidur', [Participant\DiaryController::class, 'store'])->name('diary.store');
        Route::get('/catatan-tidur/{diary}/ubah', [Participant\DiaryController::class, 'edit'])->name('diary.edit');
        Route::put('/catatan-tidur/{diary}', [Participant\DiaryController::class, 'update'])->name('diary.update');

        // Pengukuran T0/T1/T2: kedua kelompok.
        Route::get('/pengukuran', [Participant\AssessmentController::class, 'index'])->name('assessments.index');

        Route::get('/bantuan', [Participant\PageController::class, 'help'])->name('help');

        // Konten intervensi: hanya kelompok intervensi.
        Route::middleware('group:intervention')->group(function () {
            Route::get('/belajar', [Participant\LearningController::class, 'index'])->name('learning.index');
            Route::get('/belajar/{session:number}', [Participant\LearningController::class, 'show'])->name('learning.show');
            Route::post('/belajar/{session:number}/media', [Participant\LearningController::class, 'trackMedia'])->name('learning.media');
            Route::post('/belajar/{session:number}/kuis', [Participant\LearningController::class, 'submitQuiz'])->name('learning.quiz');
            Route::post('/belajar/{session:number}/aksi', [Participant\LearningController::class, 'submitAction'])->name('learning.action');
            Route::post('/belajar/{session:number}/selesai', [Participant\LearningController::class, 'complete'])->name('learning.complete');

            Route::get('/sjl-check', Participant\SjlController::class)->name('sjl');
            Route::get('/target', [Participant\PageController::class, 'goals'])->name('goals');
            Route::get('/refleksi', [Participant\PageController::class, 'reflection'])->name('reflection');
            Route::get('/progres', [Participant\PageController::class, 'progress'])->name('progress');
        });
    });
});

// --- Peneliti / Admin ---
Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:researcher,admin'])->group(function () {
    Route::get('/', Admin\DashboardController::class)->name('dashboard');
    Route::get('/peserta', [Admin\ParticipantController::class, 'index'])->name('participants.index');
    Route::get('/jadwal', [Admin\ScheduleController::class, 'index'])->name('schedule.index');
    Route::get('/paparan', [Admin\ExposureController::class, 'index'])->name('exposure.index');
    Route::get('/ekspor', [Admin\ExportController::class, 'index'])->name('export.index');
    Route::get('/ekspor/pengingat', [Admin\ExportController::class, 'reminders'])->name('export.reminders');
    Route::get('/ekspor/diary', [Admin\ExportController::class, 'diaries'])->name('export.diaries');
    Route::get('/audit', [Admin\AuditLogController::class, 'index'])->name('audit.index');
});
