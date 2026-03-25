<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\DeviceSession;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\Video;
use App\Services\TimeTrackingService;
use App\Services\UsageChartService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    protected $timeTrackingService;
    protected $usageChartService;

    public function __construct(TimeTrackingService $timeTrackingService, UsageChartService $usageChartService)
    {
        $this->timeTrackingService = $timeTrackingService;
        $this->usageChartService = $usageChartService;
    }

    /**
     * Display the dashboard.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $user = Auth::user();

        // Get user's devices with relationships
        $devices = Device::where('user_id', $user->id)
            ->with(['sessions', 'quizAttempts.quiz'])
            ->get();

        // Per-device usage: today's connected time only (app timezone; resets at midnight).
        $timeUsageData = [];
        foreach ($devices as $device) {
            $todayUsage = $this->deviceUsageSecondsToday($device);
            $totalSeconds = $todayUsage['total_seconds'];
            $activeSession = $todayUsage['active_session'];

            // Convert to hours and minutes
            $hours = floor($totalSeconds / 3600);
            $minutes = floor(($totalSeconds % 3600) / 60);

            // Calculate remaining time
            $remainingMinutes = $this->timeTrackingService->calculateRemainingTime($device);

            $timeUsageData[] = [
                'device' => $device,
                'hours' => $hours,
                'minutes' => $minutes,
                'total_seconds' => $totalSeconds,
                'remaining_minutes' => $remainingMinutes,
                'is_connected' => !empty($device->ip_address),
                // Real-time UI (dashboard JS): DB pool + active session start for client-side countdown
                'db_remaining_minutes' => (int) ($device->remaining_time_minutes ?? 0),
                'active_session_started_at' => $activeSession?->started_at?->toIso8601String(),
                'is_whitelisted' => $device->isWhitelisted(),
            ];
        }

        $timeUsageRenderedAt = now()->toIso8601String();

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
            if (!isset($quizResults[$quizId])) {
                $quizResults[$quizId] = [
                    'quiz' => $attempt->quiz,
                    'attempts' => [],
                ];
            }
            $quizResults[$quizId]['attempts'][] = [
                'device' => $attempt->device,
                'score' => $attempt->score,
                'total_questions' => count($attempt->quiz->questions ?? []),
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
     * @param Request $request HTTP request (query param: `range`)
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
     * Seconds of internet usage for this device that fall on the current calendar day (app timezone).
     *
     * Counts overlap of each ended session with [start of today, now], plus the active session’s
     * overlap with the same window (handles sessions that span midnight).
     *
     * @return array{total_seconds: int, active_session: DeviceSession|null}
     */
    private function deviceUsageSecondsToday(Device $device): array
    {
        $startOfDay = now()->startOfDay();
        $now = now();
        $seconds = 0;

        $endedSessions = DeviceSession::query()
            ->where('device_id', $device->id)
            ->whereNotNull('ended_at')
            ->get();

        foreach ($endedSessions as $session) {
            $segStart = $session->started_at->copy()->max($startOfDay);
            $segEnd = $session->ended_at->copy()->min($now);
            if ($segStart->lt($segEnd)) {
                $seconds += $segStart->diffInSeconds($segEnd);
            }
        }

        $activeSession = DeviceSession::query()
            ->where('device_id', $device->id)
            ->whereNull('ended_at')
            ->first();

        // Count usage only while internet time is still granted — not idle WiFi after quota
        // is exhausted (session should be closed by jobs; this covers races before they run).
        $deviceForExpiry = $device->fresh() ?? $device;
        $includeActiveUsage = $activeSession
            && ($deviceForExpiry->isWhitelisted()
                || ! $this->timeTrackingService->hasTimeExpired($deviceForExpiry));

        if ($includeActiveUsage) {
            $segStart = $activeSession->started_at->copy()->max($startOfDay);
            if ($segStart->lt($now)) {
                $seconds += $segStart->diffInSeconds($now);
            }
        }

        return [
            'total_seconds' => $seconds,
            'active_session' => $includeActiveUsage ? $activeSession : null,
        ];
    }

    /**
     * Get monthly usage data for the last 12 months.
     *
     * @param int $userId
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

