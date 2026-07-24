<?php

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\AssessmentExportController;
use App\Http\Controllers\Admin\QuestionController;
use App\Http\Controllers\Admin\QuestionImportController;
use App\Http\Controllers\Admin\QuestionPackageController;
use App\Http\Controllers\Admin\SheReviewController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AssessmentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StorageFileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'assessment.guard'])
    ->name('dashboard');

Route::get('/files/{path}', [StorageFileController::class, 'show'])
    ->where('path', '.*')
    ->name('files.show');

Route::middleware(['auth', 'assessment.guard'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::post('/assessment/start', [AssessmentController::class, 'start'])->name('assessment.start');
    Route::post('/assessment/{assessment}/security-violation', [AssessmentController::class, 'securityViolation'])->name('assessment.security-violation');
    Route::get('/assessment/{assessment}', [AssessmentController::class, 'show'])->name('assessment.show');
    Route::post('/assessment/{assessment}', [AssessmentController::class, 'submit'])->name('assessment.submit');
    Route::get('/assessment/{assessment}/result', [AssessmentController::class, 'result'])->name('assessment.result');
    Route::get('/assessment/{assessment}/certificate', [AssessmentController::class, 'certificate'])->name('assessment.certificate');
});

Route::middleware(['auth', 'admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('assessments', [AssessmentController::class, 'adminIndex'])->name('assessments.index');
        Route::get('assessments/export', [AssessmentController::class, 'export'])->name('assessments.export');
        Route::get('assessments/{assessment}/questions', [AssessmentController::class, 'adminQuestions'])->name('assessments.questions');
        Route::get('assessments/{assessment}/pdf', [AssessmentExportController::class, 'pdf'])->name('assessments.pdf');
        Route::post('assessments/{assessment}/unblock', [AssessmentController::class, 'unblock'])->name('assessments.unblock');
        Route::post('assessments/{assessment}/set-duration', [AssessmentController::class, 'setDuration'])->name('assessments.set-duration');
        Route::resource('packages', QuestionPackageController::class)->except('show');
        Route::get('packages/{package}/questions', [QuestionPackageController::class, 'questions'])->name('packages.questions');
        Route::get('questions/import', [QuestionImportController::class, 'create'])->name('questions.import');
        Route::get('questions/import/template', [QuestionImportController::class, 'template'])->name('questions.import.template');
        Route::post('questions/import', [QuestionImportController::class, 'store'])->name('questions.import.store');
        Route::resource('questions', QuestionController::class);
        Route::get('invite', [UserController::class, 'inviteForm'])->name('invite');
        Route::post('invite', [UserController::class, 'invite'])->name('users.invite');
        Route::post('invite/bulk', [UserController::class, 'inviteBulk'])->name('users.invite-bulk');
        Route::resource('users', UserController::class);
        Route::get('users/{user}/answers', [UserController::class, 'answers'])->name('users.answers');
        Route::get('activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');

        Route::get('she-review', [SheReviewController::class, 'index'])->name('she-review.index');
        Route::get('she-review/{assessment}', [SheReviewController::class, 'show'])->name('she-review.show');
        Route::post('she-review/{assessment}/grade', [SheReviewController::class, 'grade'])->name('she-review.grade');
    });

require __DIR__.'/auth.php';
