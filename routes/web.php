<?php

use App\Http\Controllers\PortalController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QuizController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Quiz routes (parent dashboard) - requires authentication
    // All routes are prefixed with /quizzes and named with quizzes.*
    Route::prefix('quizzes')->name('quizzes.')->group(function () {
        Route::get('/', [QuizController::class, 'index'])->name('index'); // List all quizzes
        Route::get('/create', [QuizController::class, 'create'])->name('create'); // Show create form
        Route::post('/', [QuizController::class, 'store'])->name('store'); // Save new quiz
        Route::get('/{quiz}/edit', [QuizController::class, 'edit'])->name('edit'); // Show edit form
        Route::put('/{quiz}', [QuizController::class, 'update'])->name('update'); // Update quiz
        Route::delete('/{quiz}', [QuizController::class, 'destroy'])->name('destroy'); // Delete quiz
        Route::get('/import', [QuizController::class, 'import'])->name('import'); // Show import form
        Route::post('/import', [QuizController::class, 'processImport'])->name('import.process'); // Process Excel import
        Route::get('/template/download', [QuizController::class, 'downloadTemplate'])->name('template.download'); // Download Excel template
    });
});

// Portal routes (no auth, device-based) - accessible without login
// Device identification via MAC address in query parameter
Route::prefix('portal')->name('portal.')->group(function () {
    Route::get('/quiz/{quiz}', [PortalController::class, 'showQuiz'])->name('quiz.show'); // Display quiz for child
    Route::post('/quiz/submit', [PortalController::class, 'submitQuiz'])->name('quiz.submit'); // Process quiz submission
    Route::get('/quiz/result/{attempt}', [PortalController::class, 'quizResult'])->name('quiz.result'); // Show quiz results
});

require __DIR__.'/auth.php';
