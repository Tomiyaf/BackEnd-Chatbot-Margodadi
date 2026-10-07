<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\ResearchSessionStatus;
use App\Http\Controllers\Controller;
use App\Models\EducationTopic;
use App\Models\ResearchSession;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EducationController extends Controller
{
    /**
     * Display a listing of education and research sessions.
     */
    public function index(Request $request): JsonResponse
    {
        $status = $request->query('status');
        $search = $request->query('search');

        $query = ResearchSession::with(['conversation', 'exposures.topic', 'user'])
            ->orderBy('started_at', 'desc');

        if ($status && strtoupper($status) !== 'ALL') {
            $query->where('status', strtoupper($status));
        }

        if ($search) {
            $searchTerm = '%' . strtolower($search) . '%';
            $query->where(function ($q) use ($searchTerm) {
                $q->whereRaw('LOWER(anonymous_code) LIKE ?', [$searchTerm])
                  ->orWhereHas('exposures.topic', function ($tq) use ($searchTerm) {
                      $tq->whereRaw('LOWER(name) LIKE ?', [$searchTerm]);
                  });
            });
        }

        $sessions = $query->get()->map(function (ResearchSession $session) {
            $firstExposure = $session->exposures->first();
            $topicName = $firstExposure && $firstExposure->topic
                ? $firstExposure->topic->name
                : 'Edukasi Pengelolaan Sampah';

            $interactionCount = $session->exposures->sum('interaction_count');
            if ($interactionCount === 0 && $session->conversation) {
                $interactionCount = $session->conversation->messages()->count();
            }

            $quizScoreDisplay = '-';
            if ($session->quiz_score !== null) {
                $quizScoreDisplay = round($session->quiz_score) . '%';
            }

            $startedAtFormatted = $session->started_at
                ? Carbon::parse($session->started_at)->translatedFormat('d M Y, H:i') . ' WIB'
                : '-';

            $completedAtFormatted = $session->completed_at
                ? Carbon::parse($session->completed_at)->translatedFormat('d M Y, H:i') . ' WIB'
                : null;

            return [
                'id' => $session->research_session_id,
                'sessionId' => 'RS-' . str_pad($session->research_session_id, 5, '0', STR_PAD_LEFT),
                'respondentCode' => $session->anonymous_code,
                'topic' => $topicName,
                'channel' => $session->conversation?->channel?->value ?? 'WEB',
                'interactionCount' => $interactionCount,
                'quizScore' => $quizScoreDisplay,
                'rawQuizScore' => $session->quiz_score,
                'status' => $session->status instanceof ResearchSessionStatus ? $session->status->value : (string) $session->status,
                'materialVersion' => $session->education_version ?? 'v2.1-PKM2026',
                'startedAt' => $startedAtFormatted,
                'completedAt' => $completedAtFormatted,
                'rawStartedAt' => $session->started_at?->toIso8601String(),
            ];
        });

        // Compute global statistics
        $allSessions = ResearchSession::with('exposures.topic')->get();
        $totalSessions = $allSessions->count();
        $completedSessions = $allSessions->where('status', ResearchSessionStatus::COMPLETED)->count();
        $inProgressSessions = $allSessions->where('status', ResearchSessionStatus::IN_PROGRESS)->count();
        $completionRate = $totalSessions > 0 ? round(($completedSessions / $totalSessions) * 100, 1) : 0;

        // Dynamic Growth Trend (This 7 days vs previous 7 days)
        $now = Carbon::now();
        $thisWeekCount = ResearchSession::where('started_at', '>=', $now->copy()->subDays(7))->count();
        $lastWeekCount = ResearchSession::whereBetween('started_at', [$now->copy()->subDays(14), $now->copy()->subDays(7)])->count();

        if ($lastWeekCount > 0) {
            $growthPct = round((($thisWeekCount - $lastWeekCount) / $lastWeekCount) * 100, 1);
            $growthTrend = ($growthPct >= 0 ? '+' : '') . $growthPct . '% minggu ini';
            $growthType = $growthPct >= 0 ? 'positive' : 'negative';
        } else {
            $growthTrend = ($thisWeekCount > 0 ? "+{$thisWeekCount}" : '0') . ' sesi minggu ini';
            $growthType = $thisWeekCount > 0 ? 'positive' : 'neutral';
        }

        // Average Quiz Score
        $completedWithScore = $allSessions->whereNotNull('quiz_score');
        $avgScore = $completedWithScore->count() > 0 ? round($completedWithScore->avg('quiz_score'), 1) : null;
        $avgScoreDisplay = $avgScore !== null ? $avgScore . '%' : '-';
        $completionTrend = $avgScore !== null ? "Rata-rata skor {$avgScoreDisplay}" : 'Tingkat kelulusan modul';

        // Topics breakdown
        $topics = EducationTopic::withCount('exposures')->orderBy('sequence_order')->get();
        $totalExposures = $topics->sum('exposures_count');

        $colorPresets = [
            'border-emerald-200 bg-emerald-50/50',
            'border-indigo-200 bg-indigo-50/50',
            'border-amber-200 bg-amber-50/50',
            'border-rose-200 bg-rose-50/50',
            'border-teal-200 bg-teal-50/50',
        ];

        $topicBreakdown = $topics->map(function ($topic, $idx) use ($totalExposures, $colorPresets) {
            $count = $topic->exposures_count;
            $percentage = $totalExposures > 0 ? round(($count / $totalExposures) * 100, 1) : 0;

            return [
                'id' => $topic->topic_id,
                'name' => $topic->name,
                'slug' => $topic->slug,
                'count' => $count,
                'percentage' => $percentage,
                'pct' => $percentage . '%',
                'color' => $colorPresets[$idx % count($colorPresets)],
            ];
        });

        $mostPopular = $topicBreakdown->sortByDesc('count')->first();
        $popularTopic = $mostPopular && $mostPopular['count'] > 0 ? $mostPopular['name'] : ($topicBreakdown->first()['name'] ?? 'Belum ada data');
        $popularTopicCount = $mostPopular ? $mostPopular['count'] : 0;
        $popularTopicSubtitle = $popularTopicCount > 0 ? "{$popularTopicCount} interaksi modul" : "Modul edukasi 3R";

        return response()->json([
            'status' => 'success',
            'data' => [
                'sessions' => $sessions,
                'stats' => [
                    'totalSessions' => $totalSessions,
                    'completedSessions' => $completedSessions,
                    'inProgressSessions' => $inProgressSessions,
                    'completionRate' => $completionRate,
                    'growthTrend' => $growthTrend,
                    'growthType' => $growthType,
                    'avgQuizScore' => $avgScoreDisplay,
                    'completionTrend' => $completionTrend,
                    'popularTopic' => $popularTopic,
                    'popularTopicSubtitle' => $popularTopicSubtitle,
                    'topicBreakdown' => $topicBreakdown,
                ],
            ],
        ]);
    }

    /**
     * Display a listing of education topics.
     */
    public function topics(): JsonResponse
    {
        $topics = EducationTopic::withCount('exposures')->orderBy('sequence_order')->get();

        return response()->json([
            'status' => 'success',
            'data' => $topics,
        ]);
    }
}
