<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\DeviceSession;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\Video;
use App\Services\TimeTrackingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    protected $timeTrackingService;

    public function __construct(TimeTrackingService $timeTrackingService)
    {
        $this->timeTrackingService = $timeTrackingService;
    }

    /**
     * Display the dashboard.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $user = auth()->user();

        // Get user's devices with relationships
        $devices = Device::where('user_id', $user->id)
            ->with(['sessions', 'quizAttempts.quiz'])
            ->get();

        // Calculate time usage per device (sum of all session durations)
        $timeUsageData = [];
        foreach ($devices as $device) {
            // Sum all session durations in seconds, convert to hours:minutes
            $totalSeconds = DeviceSession::where('device_id', $device->id)
                ->whereNotNull('duration_seconds')
                ->sum('duration_seconds');

            // Also include active session duration if exists
            $activeSession = DeviceSession::where('device_id', $device->id)
                ->whereNull('ended_at')
                ->first();
            if ($activeSession) {
                $activeSeconds = $activeSession->started_at->diffInSeconds(now());
                $totalSeconds += $activeSeconds;
            }

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
            ];
        }

        // Get monthly usage data for graph (last 12 months)
        $monthlyUsage = $this->getMonthlyUsage($user->id);

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
            'monthlyUsage' => $monthlyUsage,
            'quizResults' => array_values($quizResults),
            'totalQuizzes' => $totalQuizzes,
            'totalVideos' => $totalVideos,
            'quizRemainingPercent' => $quizRemainingPercent,
            'videoRemainingPercent' => $videoRemainingPercent,
            'currentDate' => Carbon::now()->format('F j, Y'),
        ]);
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

