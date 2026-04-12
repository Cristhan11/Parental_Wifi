<?php

use App\Http\Controllers\AccessAttemptController;
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

Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');
Route::get('/dashboard/usage-chart', [DashboardController::class, 'usageChart'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard.usage-chart');

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

    // Video routes (parent dashboard) - requires authentication
    // All routes are prefixed with /videos and named with videos.*
    Route::prefix('videos')->name('videos.')->group(function () {
        Route::get('/', [VideoController::class, 'index'])->name('index'); // List all videos
        Route::get('/create', [VideoController::class, 'create'])->name('create'); // Show create form
        Route::post('/', [VideoController::class, 'store'])->name('store'); // Save new video
        Route::get('/{video}/edit', [VideoController::class, 'edit'])->name('edit'); // Show edit form
        Route::put('/{video}', [VideoController::class, 'update'])->name('update'); // Update video
        Route::delete('/{video}', [VideoController::class, 'destroy'])->name('destroy'); // Delete video
    });

    // Accounts routes - Device management (main CRUD interface - Image 4)
    // Route: /accounts (separate from child devices)
    Route::prefix('accounts')->name('accounts.')->group(function () {
        Route::get('/', [DeviceController::class, 'accounts'])->name('index'); // Main accounts page
        Route::get('/blocklist', [DeviceController::class, 'blocklist'])->name('blocklist'); // Blocklist page
        Route::get('/whitelist', [DeviceController::class, 'whitelist'])->name('whitelist'); // Whitelist page
        Route::get('/create', [DeviceController::class, 'create'])->name('create'); // Show create form
        Route::post('/', [DeviceController::class, 'store'])->name('store'); // Save new device
        Route::get('/{device}/edit', [DeviceController::class, 'edit'])->name('edit'); // Show edit form
        Route::put('/{device}', [DeviceController::class, 'update'])->name('update'); // Update device
        Route::delete('/{device}', [DeviceController::class, 'destroy'])->name('destroy'); // Delete device
        Route::post('/{device}/status', [DeviceController::class, 'updateStatus'])->name('status.update'); // Update device status
        Route::post('/{device}/time', [DeviceController::class, 'updateTimeAllocation'])->name('time.update'); // Update time allocation
        Route::post('/{device}/update-role', [DeviceController::class, 'updateRole'])->name('role.update'); // Update device role
    });

    // Child Devices routes - Statistics and monitoring (Image 3)
    // Route: /child_devices (separate from accounts)
    Route::prefix('child_devices')->name('child_devices.')->group(function () {
        Route::get('/', [DeviceController::class, 'index'])->name('index'); // Show stats for first device or selected device
        Route::get('/api/connected', [DeviceController::class, 'getConnectedDevices'])->name('api.connected'); // Get real-time connected devices (before {device})
        Route::get('/{device}/usage-chart', [DeviceController::class, 'childDeviceUsageChart'])->name('usage-chart'); // JSON chart for selected device
        Route::get('/{device}', [DeviceController::class, 'index'])->name('show'); // Show stats for specific device
    });

    // Blocked websites routes - Website blocking management
    // Route: /blocked-websites
    Route::prefix('blocked-websites')->name('blocked-websites.')->group(function () {
        Route::get('/', [BlockedWebsiteController::class, 'index'])->name('index'); // List all blocked websites
        Route::get('/create', [BlockedWebsiteController::class, 'create'])->name('create'); // Show create form
        Route::post('/', [BlockedWebsiteController::class, 'store'])->name('store'); // Save new blocked website
        Route::get('/{blockedWebsite}/edit', [BlockedWebsiteController::class, 'edit'])->name('edit'); // Show edit form
        Route::put('/{blockedWebsite}', [BlockedWebsiteController::class, 'update'])->name('update'); // Update blocked website
        Route::delete('/{blockedWebsite}', [BlockedWebsiteController::class, 'destroy'])->name('destroy'); // Delete blocked website
        Route::post('/suggest-domains', [BlockedWebsiteController::class, 'suggestRelatedDomains'])->name('suggest-domains'); // AJAX: Suggest related domains
        Route::post('/bulk-import', [BlockedWebsiteController::class, 'bulkImport'])->name('bulk-import'); // Bulk import from file
        Route::get('/export', [BlockedWebsiteController::class, 'bulkExport'])->name('export'); // Export to CSV/JSON
    });

    // Flagged websites routes - Website monitoring (not blocking)
    // Route: /flagged-websites
    Route::prefix('flagged-websites')->name('flagged-websites.')->group(function () {
        Route::get('/', [FlaggedWebsiteController::class, 'index'])->name('index'); // List all flagged websites
        Route::get('/create', [FlaggedWebsiteController::class, 'create'])->name('create'); // Show create form
        Route::post('/', [FlaggedWebsiteController::class, 'store'])->name('store'); // Save new flagged website
        Route::get('/{flaggedWebsite}/edit', [FlaggedWebsiteController::class, 'edit'])->name('edit'); // Show edit form
        Route::put('/{flaggedWebsite}', [FlaggedWebsiteController::class, 'update'])->name('update'); // Update flagged website
        Route::delete('/{flaggedWebsite}', [FlaggedWebsiteController::class, 'destroy'])->name('destroy'); // Delete flagged website
    });

    // Device schedules routes - Time-based access control
    // Route: /schedules
    Route::prefix('schedules')->name('schedules.')->group(function () {
        Route::get('/', [DeviceScheduleController::class, 'index'])->name('index'); // List all schedules
        Route::get('/create', [DeviceScheduleController::class, 'create'])->name('create'); // Show create form
        Route::post('/', [DeviceScheduleController::class, 'store'])->name('store'); // Save new schedule
        Route::get('/{schedule}/edit', [DeviceScheduleController::class, 'edit'])->name('edit'); // Show edit form
        Route::put('/{schedule}', [DeviceScheduleController::class, 'update'])->name('update'); // Update schedule
        Route::delete('/{schedule}', [DeviceScheduleController::class, 'destroy'])->name('destroy'); // Delete schedule
    });

    // Browsing logs routes - View browsing history for devices
    // Route: /browsing-logs
    // These routes allow parents to view browsing history (website visits) for their devices
    // Logs are automatically created by the ParseNetworkLogs background job
    Route::prefix('browsing-logs')->name('browsing-logs.')->group(function () {
        Route::get('/', [BrowsingLogController::class, 'index'])->name('index'); // List browsing logs with filtering
    });

    // Access attempts routes - View security events (blocked/flagged website attempts)
    // Route: /access-attempts
    // These routes allow parents to view security events when children interact with blocked/flagged websites
    // Attempts are automatically created when children try to access blocked sites or visit flagged sites
    Route::prefix('access-attempts')->name('access-attempts.')->group(function () {
        Route::get('/', [AccessAttemptController::class, 'index'])->name('index'); // List access attempts with filtering
    });

    // Unified logs module (finals scope - frontend logs/filtering).
    // Why grouped here:
    // - keeps logs concerns discoverable under one route prefix
    // - keeps auth/role middleware behavior aligned with the rest of dashboard pages
    // Relevance:
    // - index provides separated streams for UI investigation
    // - export reuses the same query/filter state for report handoff (CSV)
    Route::prefix('logs')->name('logs.')->group(function () {
        Route::get('/', [LogsController::class, 'index'])->name('index');
        Route::get('/export', [LogsController::class, 'export'])->name('export');
        Route::get('/export-excel', [LogsController::class, 'exportExcel'])->name('export.excel');
    });

    // Reporting configuration and delivery controls.
    // Locked finals scope:
    // - immediate alerts: blocked + flagged events
    // - digests: daily, weekly, monthly
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [ReportsController::class, 'index'])->name('index');
        Route::put('/preferences', [ReportsController::class, 'updatePreferences'])->name('preferences.update');
        Route::post('/recipients', [ReportsController::class, 'storeRecipient'])->name('recipients.store');
        Route::put('/recipients/{recipient}', [ReportsController::class, 'updateRecipient'])->name('recipients.update');
        Route::delete('/recipients/{recipient}', [ReportsController::class, 'destroyRecipient'])->name('recipients.destroy');
        Route::post('/send-test-digest', [ReportsController::class, 'sendTestDigest'])->name('send-test-digest');
    });
});

// Portal routes (no auth, device-based) - accessible without login
// Device identification via MAC address in query parameter
// These routes are for children accessing the captive portal to earn internet time
Route::prefix('portal')->name('portal.')->group(function () {
    // Landing page - shows available quizzes and videos for device
    // Route: GET /portal?mac=AA:BB:CC:DD:EE:FF
    // This is the main entry point where children see all available activities
    // Displays device info, remaining time, and lists of quizzes/videos they can complete
    Route::get('/', [PortalController::class, 'landing'])->name('landing');
    
    // Quiz routes
    Route::get('/quiz/{quiz}', [PortalController::class, 'showQuiz'])->name('quiz.show'); // Display quiz for child
    Route::post('/quiz/submit', [PortalController::class, 'submitQuiz'])->name('quiz.submit'); // Process quiz submission
    Route::get('/quiz/result/{attempt}', [PortalController::class, 'quizResult'])->name('quiz.result'); // Show quiz results
    
    // Video routes
    Route::get('/video/{video}', [PortalController::class, 'showVideo'])->name('video.show'); // Display video for child
    Route::post('/video/submit-words', [PortalController::class, 'submitVideoWords'])->name('video.submit'); // Process word submission
    Route::get('/video/result/{completion}', [PortalController::class, 'videoResult'])->name('video.result'); // Show video results
});

require __DIR__.'/auth.php';
