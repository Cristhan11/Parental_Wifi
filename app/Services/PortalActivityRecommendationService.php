<?php

namespace App\Services;

use App\Models\Device;
use App\Models\QuestionBankItem;
use App\Models\Quiz;
use App\Models\Video;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Portal recommendations: optional parent pin (Phase A), then Phase B deterministic sort —
 * Option 1 "Newest assignment first" (Simplify_project_child_portal.md §3).
 * Pivot `created_at` preferred; if missing on a row we use the quiz/video `updated_at` as assignment stand-in.
 *
 * Eligible quizzes are all active quizzes assigned to the device (school level is informational only).
 */
class PortalActivityRecommendationService
{
    public function eligibleQuizzes(Device $device): Collection
    {
        return $device->quizzes()
            ->where('is_active', true)
            ->where('title', '!=', Quiz::RANDOM_MODE_SETTINGS_TITLE)
            ->get()
            ->values();
    }

    public function eligibleVideos(Device $device): Collection
    {
        return $device->videos()
            ->where('is_active', true)
            ->get()
            ->values();
    }

    public function recommendQuiz(Device $device): ?Quiz
    {
        $candidates = $this->eligibleQuizzes($device);
        if ($candidates->isEmpty()) {
            return null;
        }

        if ($device->preferred_quiz_id) {
            $pinned = $candidates->firstWhere('id', (int) $device->preferred_quiz_id);
            if ($pinned instanceof Quiz) {
                return $pinned;
            }
        }

        return $this->sortQuizzesPhaseB($candidates)->first();
    }

    public function recommendVideo(Device $device): ?Video
    {
        $candidates = $this->eligibleVideos($device);
        if ($candidates->isEmpty()) {
            return null;
        }

        if ($device->preferred_video_id) {
            $pinned = $candidates->firstWhere('id', (int) $device->preferred_video_id);
            if ($pinned instanceof Video) {
                return $pinned;
            }
        }

        return $this->sortVideosPhaseB($candidates)->first();
    }

    /**
     * Random global-bank mix is shown only when the device is on the parent's Random Quiz Mode list,
     * that quiz is active, and at least one active bank row exists for the configured school levels.
     */
    public function randomMixEligible(Device $device): bool
    {
        $quiz = Quiz::query()
            ->where('user_id', $device->user_id)
            ->where('title', Quiz::RANDOM_MODE_SETTINGS_TITLE)
            ->where('is_active', true)
            ->first();

        if (! $quiz || ! $quiz->devices()->where('devices.id', $device->id)->exists()) {
            return false;
        }

        $levels = $quiz->effectiveRandomBankLevelsForDevice($device);

        return QuestionBankItem::queryForRandomBankMix($quiz, $levels)->exists();
    }

    public function randomModeQuiz(Device $device): ?Quiz
    {
        return Quiz::query()
            ->where('user_id', $device->user_id)
            ->where('title', Quiz::RANDOM_MODE_SETTINGS_TITLE)
            ->where('is_active', true)
            ->first();
    }

    /** @return Collection<string, Collection<int, Quiz>> */
    public function quizzesGroupedBySubject(Collection $eligibleQuizzes): Collection
    {
        $groups = collect([
            'Math' => collect(),
            'English' => collect(),
            'Science' => collect(),
            'Other' => collect(),
        ]);

        foreach ($eligibleQuizzes as $quiz) {
            $key = $this->subjectGroupKey($quiz->subject);
            $groups[$key]->push($quiz);
        }

        return $groups;
    }

    private function subjectGroupKey(?string $subject): string
    {
        $s = strtolower(trim((string) $subject));

        return match ($s) {
            'math' => 'Math',
            'english' => 'English',
            'science' => 'Science',
            default => 'Other',
        };
    }

    /**
     * Phase B Option 1: newest assignment first, then shortest question_count, then updated_at desc, title, id.
     *
     * @param  Collection<int, Quiz>  $quizzes
     * @return Collection<int, Quiz>
     */
    private function sortQuizzesPhaseB(Collection $quizzes): Collection
    {
        return $quizzes->sort(function (Quiz $a, Quiz $b): int {
            $tA = $this->quizAssignmentTime($a);
            $tB = $this->quizAssignmentTime($b);
            if (($c = $tB <=> $tA) !== 0) {
                return $c;
            }

            $effA = $this->quizEffortSortKey($a);
            $effB = $this->quizEffortSortKey($b);
            if (($c = $effA <=> $effB) !== 0) {
                return $c;
            }

            if (($c = $b->updated_at <=> $a->updated_at) !== 0) {
                return $c;
            }

            if (($c = strcmp((string) $a->title, (string) $b->title)) !== 0) {
                return $c;
            }

            return $a->id <=> $b->id;
        })->values();
    }

    /**
     * @param  Collection<int, Video>  $videos
     * @return Collection<int, Video>
     */
    private function sortVideosPhaseB(Collection $videos): Collection
    {
        return $videos->sort(function (Video $a, Video $b): int {
            $tA = $this->videoAssignmentTime($a);
            $tB = $this->videoAssignmentTime($b);
            if (($c = $tB <=> $tA) !== 0) {
                return $c;
            }

            $effA = $this->videoEffortSortKey($a);
            $effB = $this->videoEffortSortKey($b);
            if (($c = $effA <=> $effB) !== 0) {
                return $c;
            }

            if (($c = $b->updated_at <=> $a->updated_at) !== 0) {
                return $c;
            }

            if (($c = strcmp((string) $a->title, (string) $b->title)) !== 0) {
                return $c;
            }

            return $a->id <=> $b->id;
        })->values();
    }

    private function quizAssignmentTime(Quiz $quiz): Carbon
    {
        $pivotCreated = $quiz->pivot?->created_at ?? null;
        if ($pivotCreated instanceof Carbon) {
            return $pivotCreated;
        }

        return $quiz->updated_at ?? Carbon::now();
    }

    private function videoAssignmentTime(Video $video): Carbon
    {
        $pivotCreated = $video->pivot?->created_at ?? null;
        if ($pivotCreated instanceof Carbon) {
            return $pivotCreated;
        }

        return $video->updated_at ?? Carbon::now();
    }

    /** Null/zero question_count sorts last (largest key) for ascending effort order. */
    private function quizEffortSortKey(Quiz $quiz): int
    {
        $n = (int) ($quiz->question_count ?? 0);

        return $n < 1 ? PHP_INT_MAX : $n;
    }

    private function videoEffortSortKey(Video $video): int
    {
        $n = (int) ($video->duration_seconds ?? 0);

        return $n < 1 ? PHP_INT_MAX : $n;
    }
}
