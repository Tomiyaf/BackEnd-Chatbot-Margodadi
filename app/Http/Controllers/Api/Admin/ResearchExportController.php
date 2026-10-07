<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\ChannelType;
use App\Enums\ResearchSessionStatus;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\ResearchSession;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ResearchExportController extends Controller
{
    /**
     * Preview anonymized research dataset.
     */
    public function preview(Request $request): JsonResponse
    {
        $period = strtoupper($request->query('period', 'MONTH'));

        $query = ResearchSession::with(['exposures.topic'])
            ->orderBy('started_at', 'desc');

        if ($period === 'MONTH') {
            $query->where('started_at', '>=', Carbon::now()->subDays(30));
        }

        $sessions = $query->take(20)->get()->map(function (ResearchSession $session) {
            $firstExposure = $session->exposures->first();
            $topicName = $firstExposure && $firstExposure->topic
                ? $firstExposure->topic->name
                : 'Edukasi Pengelolaan Sampah';

            $interactionCount = $session->exposures->sum('interaction_count');
            if ($interactionCount === 0 && $session->conversation) {
                $interactionCount = $session->conversation->messages()->count();
            }

            return [
                'id' => $session->research_session_id,
                'respondentCode' => $session->anonymous_code,
                'sessionId' => 'RS-' . str_pad($session->research_session_id, 5, '0', STR_PAD_LEFT),
                'topic' => $topicName,
                'interactionCount' => $interactionCount,
                'status' => $session->status instanceof ResearchSessionStatus ? $session->status->value : (string) $session->status,
                'quizScore' => $session->quiz_score !== null ? round($session->quiz_score) . '%' : '-',
                'materialVersion' => $session->education_version ?? 'v2.1-PKM2026',
                'startedAt' => $session->started_at ? Carbon::parse($session->started_at)->format('Y-m-d H:i:s') : '-',
                'completedAt' => $session->completed_at ? Carbon::parse($session->completed_at)->format('Y-m-d H:i:s') : '-',
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => $sessions,
        ]);
    }

    /**
     * Export anonymized research dataset as CSV stream.
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        $period = strtoupper($request->query('period', 'ALL'));
        $fieldsParam = $request->query('fields');

        $selectedFields = $fieldsParam
            ? array_filter(explode(',', $fieldsParam))
            : ['respondentCode', 'sessionId', 'topic', 'interactionCount', 'status', 'quizScore', 'materialVersion', 'startedAt'];

        $fieldHeaderMap = [
            'respondentCode' => 'Kode Responden (Anonim)',
            'sessionId' => 'ID Sesi Penelitian',
            'topic' => 'Topik Modul Edukasi',
            'interactionCount' => 'Jumlah Interaksi (Turn)',
            'status' => 'Status Sesi',
            'quizScore' => 'Skor Evaluasi (%)',
            'materialVersion' => 'Versi Materi Edukasi',
            'startedAt' => 'Waktu Mulai',
            'completedAt' => 'Waktu Selesai',
        ];

        $headers = [];
        foreach ($selectedFields as $f) {
            $headers[] = $fieldHeaderMap[$f] ?? $f;
        }

        $query = ResearchSession::with(['exposures.topic'])->orderBy('started_at', 'desc');
        if ($period === 'MONTH') {
            $query->where('started_at', '>=', Carbon::now()->subDays(30));
        }

        $operator = $request->user();
        ActivityLog::create([
            'operator_id' => $operator?->operator_id,
            'actor_name' => $operator?->name ?? 'Aparatur Pekon',
            'action' => 'RESEARCH_EXPORT',
            'target' => '#DATASET-PKM',
            'description' => 'Mengunduh dataset penelitian PKM/Skripsi format CSV (Periode: ' . $period . ')',
            'channel' => ChannelType::WEB,
            'created_at' => now(),
        ]);

        $filename = 'research_dataset_margodadi_' . Carbon::now()->format('Ymd_His') . '.csv';

        return response()->stream(function () use ($query, $selectedFields, $headers) {
            $handle = fopen('php://output', 'w');
            
            // Add UTF-8 BOM for Excel Indonesian compatibility
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, $headers);

            $query->chunk(100, function ($sessions) use ($handle, $selectedFields) {
                foreach ($sessions as $session) {
                    $firstExposure = $session->exposures->first();
                    $topicName = $firstExposure && $firstExposure->topic
                        ? $firstExposure->topic->name
                        : 'Edukasi Pengelolaan Sampah';

                    $interactionCount = $session->exposures->sum('interaction_count');
                    if ($interactionCount === 0 && $session->conversation) {
                        $interactionCount = $session->conversation->messages()->count();
                    }

                    $dataMap = [
                        'respondentCode' => $session->anonymous_code,
                        'sessionId' => 'RS-' . str_pad($session->research_session_id, 5, '0', STR_PAD_LEFT),
                        'topic' => $topicName,
                        'interactionCount' => $interactionCount,
                        'status' => $session->status instanceof ResearchSessionStatus ? $session->status->value : (string) $session->status,
                        'quizScore' => $session->quiz_score !== null ? round($session->quiz_score) . '%' : '-',
                        'materialVersion' => $session->education_version ?? 'v2.1-PKM2026',
                        'startedAt' => $session->started_at ? Carbon::parse($session->started_at)->format('Y-m-d H:i:s') : '-',
                        'completedAt' => $session->completed_at ? Carbon::parse($session->completed_at)->format('Y-m-d H:i:s') : '-',
                    ];

                    $row = [];
                    foreach ($selectedFields as $fieldKey) {
                        $row[] = $dataMap[$fieldKey] ?? '';
                    }

                    fputcsv($handle, $row);
                }
            });

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control' => 'no-store, no-cache',
        ]);
    }
}
