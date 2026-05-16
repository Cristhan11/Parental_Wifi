<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\DeviceSession;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\Video;
use App\Services\BandwidthUsageService;
use App\Services\DashboardTimeUsageService;
use App\Services\UsageChartService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    protected $usageChartService;

    protected $bandwidthUsageService;

    protected $dashboardTimeUsageService;

    public function __construct(
        UsageChartService $usageChartService,
        BandwidthUsageService $bandwidthUsageService,
        DashboardTimeUsageService $dashboardTimeUsageService,
    ) {
        $this->usageChartService = $usageChartService;
        $this->bandwidthUsageService = $bandwidthUsageService;
        $this->dashboardTimeUsageService = $dashboardTimeUsageService;
    }

    /**
     * Display the dashboard.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $user = Auth::user();

        // Keep base device query lean; session data is loaded in two batched queries below.
        $devices = Device::where('user_id', $user->id)
            ->forDashboardTimeUsage()
            ->get();

        $now = now();
        $usageSecondsByDevice = $this->dashboardTimeUsageService->sumTodayUsageSecondsByDevice($user);

        $deviceIds = $devices->pluck('id')->values();
        $activeSessionByDevice = [];
        if ($deviceIds->isNotEmpty()) {
            $activeSessionByDevice = DeviceSession::query()
                ->whereIn('device_id', $deviceIds)
                ->whereNull('ended_at')
                ->orderByDesc('started_at')
                ->get(['id', 'device_id', 'started_at', 'last_incremental_bill_at'])
                ->unique('device_id')
                ->keyBy('device_id')
                ->all();
        }

        // Per-device usage: today's connected time only (app timezone; resets at midnight).
        $timeUsageData = [];
        foreach ($devices as $device) {
            $totalSeconds = (int) ($usageSecondsByDevice[(int) $device->id] ?? 0);
            $activeSession = $activeSessionByDevice[$device->id] ?? null;
            $remainingMinutes = $this->calculateRemainingMinutes($device, $activeSession);
            $includeActiveUsage = $activeSession
                && ($device->isWhitelisted() || $remainingMinutes > 0);

            // Convert to hours and minutes
            $hours = floor($totalSeconds / 3600);
            $minutes = floor(($totalSeconds % 3600) / 60);

            $timeUsageData[] = [
                'device' => $device,
                'hours' => $hours,
                'minutes' => $minutes,
                'total_seconds' => $totalSeconds,
                'remaining_minutes' => $remainingMinutes,
                'is_connected' => ! empty($device->ip_address),
                // Real-time UI (dashboard JS): DB pool + active session start for client-side countdown
                'db_remaining_minutes' => (int) ($device->remaining_time_minutes ?? 0),
                'active_session_started_at' => $includeActiveUsage
                    ? $activeSession?->started_at?->toIso8601String()
                    : null,
                'active_session_billing_anchor_at' => $includeActiveUsage && $activeSession
                    ? $activeSession->billingAnchor()->toIso8601String()
                    : null,
                'is_whitelisted' => $device->isWhitelisted(),
            ];
        }

        $timeUsageNow = now();
        $timeUsageRenderedAt = $timeUsageNow->toIso8601String();
        $timeUsageAnchorMs = (int) ($timeUsageNow->getTimestamp() * 1000);

        // Get recent quiz attempts with scores
        $quizAttempts = QuizAttempt::whereHas('device', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })
            ->with(['device', 'quiz'])
            ->orderBy('completed_at', 'desc')
            ->take(10)
            ->get();

        // Group quiz attempts by quiz
        $quizResults = [];
        foreach ($quizAttempts as $attempt) {
            $quizId = $attempt->quiz_id;
            if (! isset($quizResults[$quizId])) {
                $quizResults[$quizId] = [
                    'quiz' => $attempt->quiz,
                    'attempts' => [],
                ];
            }
            $questionItems = $attempt->quiz->questions['questions'] ?? [];
            if (! is_array($questionItems)) {
                $questionItems = [];
            }
            $totalQuestions = count($questionItems);
            // Stored score is 0–100%; derive correct count for display (same approach as DeviceController::getQuizScores).
            $correctCount = $totalQuestions > 0
                ? (int) max(0, min($totalQuestions, round($attempt->score / 100 * $totalQuestions)))
                : 0;

            $quizResults[$quizId]['attempts'][] = [
                'device' => $attempt->device,
                'correct_count' => $correctCount,
                'total_questions' => $totalQuestions,
                'completed_at' => $attempt->completed_at,
            ];
        }

        // Count available quizzes and videos
        $totalQuizzes = Quiz::where('user_id', $user->id)
            ->where('is_active', true)
            ->count();

        $totalVideos = Video::where('user_id', $user->id)
            ->where('is_active', true)
            ->count();

        // Calculate remaining percentages
        // For display purposes, we'll show percentage based on a reasonable maximum (10)
        // This represents how "full" the quiz/video library is
        $maxDisplay = 10;
        $quizRemainingPercent = min(100, ($totalQuizzes / $maxDisplay) * 100);
        $videoRemainingPercent = min(100, ($totalVideos / $maxDisplay) * 100);

        return view('dashboard.index', [
            'timeUsageData' => $timeUsageData,
            'timeUsageRenderedAt' => $timeUsageRenderedAt,
            'timeUsageAnchorMs' => $timeUsageAnchorMs,
            'quizResults' => array_values($quizResults),
            'totalQuizzes' => $totalQuizzes,
            'totalVideos' => $totalVideos,
            'quizRemainingPercent' => $quizRemainingPercent,
            'videoRemainingPercent' => $videoRemainingPercent,
            'currentDate' => Carbon::now()->format('F j, Y'),
        ]);
    }

    /**
     * Return JSON data for the dashboard time-usage chart.
     *
     * This powers the graph filters (daily/weekly/monthly/yearly) and is also
     * called by the frontend when realtime events are received.
     *
     * @param  Request  $request  HTTP request (query param: `range`)
     * @return JsonResponse JSON payload compatible with the Chart.js builder in the dashboard view
     */
    public function usageChart(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'range' => ['nullable', 'in:daily,weekly,monthly,yearly'],
        ]);

        $range = $validated['range'] ?? 'yearly';

        $parent = $request->user();

        return response()->json(
            $this->usageChartService->buildChartPayload($parent, $range)
        );
    }

    /**
     * Return JSON data for the dashboard bandwidth chart.
     */
    public function bandwidthChart(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'range' => ['nullable', 'in:daily,weekly,monthly,yearly'],
            'display_unit' => ['nullable', 'in:gb,mb'],
        ]);

        $range = $validated['range'] ?? 'yearly';
        $displayUnit = $validated['display_unit'] ?? 'gb';

        return response()->json(
            $this->bandwidthUsageService->buildChartPayload($request->user(), $range, null, $displayUnit)
        );
    }

    private function calculateRemainingMinutes(Device $device, ?DeviceSession $activeSession): int
    {
        if ($device->isWhitelisted()) {
            return 999999;
        }

        $baseRemaining = (int) ($device->remaining_time_minutes ?? 0);
        if (! $activeSession) {
            return max(0, $baseRemaining);
        }

        $poolSeconds = $baseRemaining * 60;
        $anchor = $activeSession->billingAnchor();
        $elapsedSeconds = $anchor->diffInSeconds(now());
        $remainingSeconds = max(0, $poolSeconds - $elapsedSeconds);

        return max(0, (int) floor($remainingSeconds / 60));
    }

    /**
     * Get monthly usage data for the last 12 months.
     *
     * @param  int  $userId
     * @return array
     */
    private function getMonthlyUsage($userId)
    {
        $months = [];
        $now = Carbon::now();

        // Get last 12 months
        for ($i = 11; $i >= 0; $i--) {
            $monthStart = $now->copy()->subMonths($i)->startOfMonth();
            $monthEnd = $monthStart->copy()->endOfMonth();

            // Sum all session durations for devices belonging to this user in this month
            $totalSeconds = DeviceSession::whereHas('device', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
                ->whereBetween('started_at', [$monthStart, $monthEnd])
                ->whereNotNull('duration_seconds')
                ->sum('duration_seconds');

            // Convert to hours (for graph display)
            $hours = round($totalSeconds / 3600, 1);

            $months[] = [
                'month' => $monthStart->format('M'),
                'hours' => $hours,
            ];
        }

        return $months;
    }
}
