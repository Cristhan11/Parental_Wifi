<?php

use App\Http\Controllers\AccessAttemptController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminParentAccountController;
use App\Http\Controllers\Admin\AdminParentPasswordResetRequestController;
use App\Http\Controllers\BlockedWebsiteController;
use App\Http\Controllers\BrowsingLogController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\DeviceScheduleController;
use App\Http\Controllers\FlaggedWebsiteController;
use App\Http\Controllers\LogsController;
use App\Http\Controllers\PortalController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\VideoController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/device-request', [DeviceController::class, 'requestRegistrationForm'])->name('device-request.create');
Route::post('/device-request', [DeviceController::class, 'submitRegistrationRequest'])
    ->middleware('throttle:6,1')
    ->name('device-request.store');

Route::middleware(['auth', 'verified', 'audit.sensitive'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'verified', 'parent.dashboard', 'audit.sensitive'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/usage-chart', [DashboardController::class, 'usageChart'])
        ->name('dashboard.usage-chart');

    Route::prefix('quizzes')->name('quizzes.')->group(function () {
        Route::get('/', [QuizController::class, 'index'])->name('index');
        Route::get('/create', [QuizController::class, 'create'])->name('create');
        Route::post('/', [QuizController::class, 'store'])->name('store');
        Route::get('/{quiz}/edit', [QuizController::class, 'edit'])->name('edit');
        Route::put('/{quiz}', [QuizController::class, 'update'])->name('update');
        Route::delete('/{quiz}', [QuizController::class, 'destroy'])->name('destroy');
        Route::get('/import', [QuizController::class, 'import'])->name('import');
        Route::post('/import', [QuizController::class, 'processImport'])->name('import.process');
        Route::get('/template/download', [QuizController::class, 'downloadTemplate'])->name('template.download');
        Route::get('/question-bank/export', [QuizController::class, 'exportQuestionBank'])->name('question-bank.export');
        Route::post('/random-mode', [QuizController::class, 'updateRandomModeSettings'])->name('random-mode.update');
    });

    Route::prefix('videos')->name('videos.')->group(function () {
        Route::get('/', [VideoController::class, 'index'])->name('index');
        Route::get('/create', [VideoController::class, 'create'])->name('create');
        Route::post('/', [VideoController::class, 'store'])->name('store');
        Route::get('/{video}/edit', [VideoController::class, 'edit'])->name('edit');
        Route::put('/{video}', [VideoController::class, 'update'])->name('update');
        Route::delete('/{video}', [VideoController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('accounts')->name('accounts.')->group(function () {
        Route::get('/', [DeviceController::class, 'accounts'])->name('index');
        Route::get('/blocklist', [DeviceController::class, 'blocklist'])->name('blocklist');
        Route::get('/whitelist', [DeviceController::class, 'whitelist'])->name('whitelist');
        Route::get('/create', [DeviceController::class, 'create'])->name('create');
        Route::get('/create/advanced', [DeviceController::class, 'createAdvanced'])->name('create.advanced');
        Route::post('/', [DeviceController::class, 'store'])->name('store');
        Route::get('/{device}/edit', [DeviceController::class, 'edit'])->name('edit');
        Route::put('/{device}', [DeviceController::class, 'update'])->name('update');
        Route::delete('/{device}', [DeviceController::class, 'destroy'])->name('destroy');
        Route::post('/{device}/status', [DeviceController::class, 'updateStatus'])->name('status.update');
        Route::post('/{device}/time', [DeviceController::class, 'updateTimeAllocation'])->name('time.update');
        Route::post('/{device}/update-role', [DeviceController::class, 'updateRole'])->name('role.update');
        Route::post('/registration-requests/{registrationRequest}/approve', [DeviceController::class, 'approveRegistrationRequest'])
            ->name('registration-requests.approve');
        Route::post('/registration-requests/{registrationRequest}/reject', [DeviceController::class, 'rejectRegistrationRequest'])
            ->name('registration-requests.reject');
    });

    Route::prefix('child_devices')->name('child_devices.')->group(function () {
        Route::get('/', [DeviceController::class, 'index'])->name('index');
        Route::get('/api/connected', [DeviceController::class, 'getConnectedDevices'])->name('api.connected');
        Route::get('/{device}/usage-chart', [DeviceController::class, 'childDeviceUsageChart'])->name('usage-chart');
        Route::get('/{device}', [DeviceController::class, 'index'])->name('show');
    });

    Route::prefix('blocked-websites')->name('blocked-websites.')->group(function () {
        Route::get('/', [BlockedWebsiteController::class, 'index'])->name('index');
        Route::get('/create', [BlockedWebsiteController::class, 'create'])->name('create');
        Route::post('/', [BlockedWebsiteController::class, 'store'])->name('store');
        Route::get('/{blockedWebsite}/edit', [BlockedWebsiteController::class, 'edit'])->name('edit');
        Route::put('/{blockedWebsite}', [BlockedWebsiteController::class, 'update'])->name('update');
        Route::delete('/{blockedWebsite}', [BlockedWebsiteController::class, 'destroy'])->name('destroy');
        Route::post('/suggest-domains', [BlockedWebsiteController::class, 'suggestRelatedDomains'])->name('suggest-domains');
        Route::post('/bulk-import', [BlockedWebsiteController::class, 'bulkImport'])->name('bulk-import');
        Route::get('/export', [BlockedWebsiteController::class, 'bulkExport'])->name('export');
    });

    Route::prefix('flagged-websites')->name('flagged-websites.')->group(function () {
        Route::get('/', [FlaggedWebsiteController::class, 'index'])->name('index');
        Route::get('/create', [FlaggedWebsiteController::class, 'create'])->name('create');
        Route::post('/', [FlaggedWebsiteController::class, 'store'])->name('store');
        Route::get('/{flaggedWebsite}/edit', [FlaggedWebsiteController::class, 'edit'])->name('edit');
        Route::put('/{flaggedWebsite}', [FlaggedWebsiteController::class, 'update'])->name('update');
        Route::delete('/{flaggedWebsite}', [FlaggedWebsiteController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('schedules')->name('schedules.')->group(function () {
        Route::get('/', [DeviceScheduleController::class, 'index'])->name('index');
        Route::get('/create', [DeviceScheduleController::class, 'create'])->name('create');
        Route::post('/', [DeviceScheduleController::class, 'store'])->name('store');
        Route::get('/{schedule}/edit', [DeviceScheduleController::class, 'edit'])->name('edit');
        Route::put('/{schedule}', [DeviceScheduleController::class, 'update'])->name('update');
        Route::delete('/{schedule}', [DeviceScheduleController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('browsing-logs')->name('browsing-logs.')->group(function () {
        Route::get('/', [BrowsingLogController::class, 'index'])->name('index');
    });

    Route::prefix('access-attempts')->name('access-attempts.')->group(function () {
        Route::get('/', [AccessAttemptController::class, 'index'])->name('index');
    });

    Route::prefix('logs')->name('logs.')->group(function () {
        Route::get('/', [LogsController::class, 'index'])->name('index');
        Route::get('/export', [LogsController::class, 'export'])->name('export');
        Route::get('/export-excel', [LogsController::class, 'exportExcel'])->name('export.excel');
    });

    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [ReportsController::class, 'index'])->name('index');
        Route::put('/preferences', [ReportsController::class, 'updatePreferences'])->name('preferences.update');
        Route::post('/recipients/bulk-save', [ReportsController::class, 'bulkSaveRecipients'])->name('recipients.bulk-save');
        Route::post('/recipients', [ReportsController::class, 'storeRecipient'])->name('recipients.store');
        Route::put('/recipients/{recipient}', [ReportsController::class, 'updateRecipient'])->name('recipients.update');
        Route::delete('/recipients/{recipient}', [ReportsController::class, 'destroyRecipient'])->name('recipients.destroy');
        Route::post('/send-test-digest', [ReportsController::class, 'sendTestDigest'])->name('send-test-digest');
    });
});

Route::middleware(['auth', 'verified', 'role.admin', 'audit.sensitive'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');
    Route::get('/password-reset-requests', [AdminParentPasswordResetRequestController::class, 'index'])->name('password-reset-requests.index');
    Route::post('/password-reset-requests/{parent_password_reset_request}/fulfill', [AdminParentPasswordResetRequestController::class, 'fulfill'])->name('password-reset-requests.fulfill');
    Route::get('/parents/pending', [AdminParentAccountController::class, 'pending'])->name('parents.pending');
    Route::get('/parents', [AdminParentAccountController::class, 'index'])->name('parents.index');
    Route::get('/parents/{user}/edit', [AdminParentAccountController::class, 'edit'])->name('parents.edit');
    Route::patch('/parents/{user}', [AdminParentAccountController::class, 'update'])->name('parents.update');
    Route::delete('/parents/{user}', [AdminParentAccountController::class, 'destroy'])->name('parents.destroy');
    Route::post('/parents/{user}/approve', [AdminParentAccountController::class, 'approve'])->name('parents.approve');
    Route::post('/parents/{user}/reject', [AdminParentAccountController::class, 'reject'])->name('parents.reject');
    Route::post('/parents/{user}/promote', [AdminParentAccountController::class, 'promoteToHouseholdOperator'])->name('parents.promote');
    Route::post('/parents/{user}/demote', [AdminParentAccountController::class, 'demoteToParentRole'])->name('parents.demote');
    Route::post('/parents/{user}/reset-password-default', [AdminParentAccountController::class, 'resetPasswordToDefault'])->name('parents.reset-password-default');
});

Route::prefix('portal')->name('portal.')->group(function () {
    Route::get('/', [PortalController::class, 'landing'])->name('landing');
    Route::get('/quiz/{quiz}', [PortalController::class, 'showQuiz'])->name('quiz.show');
    Route::post('/quiz/submit', [PortalController::class, 'submitQuiz'])->name('quiz.submit');
    Route::get('/quiz/result/{attempt}', [PortalController::class, 'quizResult'])->name('quiz.result');
    Route::get('/video/{video}/stream', [PortalController::class, 'streamVideo'])->name('video.stream');
    Route::get('/video/{video}', [PortalController::class, 'showVideo'])->name('video.show');
    Route::post('/video/submit-words', [PortalController::class, 'submitVideoWords'])->name('video.submit');
    Route::get('/video/result/{completion}', [PortalController::class, 'videoResult'])->name('video.result');
});

require __DIR__.'/auth.php';
