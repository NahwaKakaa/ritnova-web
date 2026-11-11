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
    // Layar 03: consent wajib sebelum fitur lain.
    Route::get('/persetujuan', [Participant\ConsentController::class, 'show'])->name('consent.show');
    Route::post('/persetujuan', [Participant\ConsentController::class, 'store'])->name('consent.store');

    Route::middleware('consented')->group(function () {
        // Layar 04: onboarding & baseline T0.
        Route::get('/persiapan', [Participant\OnboardingController::class, 'show'])->name('onboarding');
        Route::post('/persiapan/profil', [Participant\OnboardingController::class, 'saveProfile'])->name('onboarding.profile');
        Route::post('/persiapan/selesai', [Participant\OnboardingController::class, 'complete'])->name('onboarding.complete');

        // Layar 05: dashboard.
        Route::get('/beranda', Participant\DashboardController::class)->name('dashboard');
        Route::get('/menu', [Participant\AccountController::class, 'menu'])->name('menu');

        // Layar 08–09: Sleep Tracker (kedua kelompok, sesuai protokol).
        Route::get('/catatan-tidur', [Participant\DiaryController::class, 'index'])->name('diary.index');
        Route::get('/catatan-tidur/baru', [Participant\DiaryController::class, 'create'])->name('diary.create');
        Route::post('/catatan-tidur', [Participant\DiaryController::class, 'store'])->name('diary.store');
        Route::get('/catatan-tidur/{diary}/ubah', [Participant\DiaryController::class, 'edit'])->name('diary.edit');
        Route::put('/catatan-tidur/{diary}', [Participant\DiaryController::class, 'update'])->name('diary.update');

        // Layar 15: pengukuran T0/T1/T2 (kedua kelompok).
        Route::get('/pengukuran', [Participant\AssessmentController::class, 'index'])->name('assessments.index');
        Route::get('/pengukuran/{phase}/{instrument:code}', [Participant\AssessmentController::class, 'show'])->name('assessments.show');
        Route::post('/pengukuran/{phase}/{instrument:code}', [Participant\AssessmentController::class, 'store'])->name('assessments.store');

        // Layar 16: pengingat & notifikasi web.
        Route::get('/notifikasi', [Participant\NotificationController::class, 'index'])->name('notifications.index');
        Route::post('/notifikasi/baca-semua', [Participant\NotificationController::class, 'markAllRead'])->name('notifications.read');
        Route::get('/pengingat', [Participant\AccountController::class, 'reminders'])->name('reminders.edit');
        Route::put('/pengingat', [Participant\AccountController::class, 'updateReminders'])->name('reminders.update');

        // Layar 17: bantuan, privasi, profil.
        Route::get('/bantuan', [Participant\AccountController::class, 'help'])->name('help');
        Route::get('/profil', [Participant\AccountController::class, 'profile'])->name('profile');
        Route::put('/profil/kata-sandi', [Participant\AccountController::class, 'updatePassword'])->name('profile.password');
        Route::post('/persetujuan/tarik', [Participant\ConsentController::class, 'withdraw'])->name('consent.withdraw');

        // Konten intervensi: hanya kelompok intervensi.
        Route::middleware('group:intervention')->group(function () {
            // Layar 06, 07, 19, 20.
            Route::get('/belajar', [Participant\LearningController::class, 'index'])->name('learning.index');
            Route::get('/belajar/{session:number}', [Participant\LearningController::class, 'show'])->name('learning.show');
            Route::post('/belajar/{session:number}/media', [Participant\LearningController::class, 'trackMedia'])->name('learning.media');
            Route::post('/belajar/{session:number}/kuis', [Participant\LearningController::class, 'submitQuiz'])->name('learning.quiz');
            Route::post('/belajar/{session:number}/aksi', [Participant\LearningController::class, 'submitAction'])->name('learning.action');
            Route::post('/belajar/{session:number}/selesai', [Participant\LearningController::class, 'complete'])->name('learning.complete');

            // Layar 10–11: SJL Check.
            Route::get('/sjl-check', [Participant\SjlController::class, 'show'])->name('sjl');
            Route::post('/sjl-check', [Participant\SjlController::class, 'calculate'])->name('sjl.calculate');

            // Layar 18 + 14: progres sesi & insights.
            Route::get('/progres', Participant\ProgressController::class)->name('progress');

            // Layar 12–13: goals & refleksi mingguan.
            Route::get('/target', [Participant\GoalController::class, 'index'])->name('goals.index');
            Route::post('/target', [Participant\GoalController::class, 'store'])->name('goals.store');
            Route::patch('/target/{goal}', [Participant\GoalController::class, 'review'])->name('goals.review');
            Route::get('/refleksi', [Participant\ReflectionController::class, 'index'])->name('reflection.index');
            Route::post('/refleksi', [Participant\ReflectionController::class, 'store'])->name('reflection.store');
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
