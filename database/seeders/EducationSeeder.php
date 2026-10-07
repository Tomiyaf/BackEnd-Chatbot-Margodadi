<?php

namespace Database\Seeders;

use App\Enums\ResearchSessionStatus;
use App\Models\Conversation;
use App\Models\EducationExposure;
use App\Models\EducationTopic;
use App\Models\ResearchSession;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class EducationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $topicsData = [
            [
                'name' => 'Pemilahan Sampah Organik & Anorganik',
                'slug' => 'pemilahan-sampah',
                'description' => 'Panduan identifikasi jenis sampah basah/organik (sisa makanan, daun) dan anorganik bernilai ekonomis (plastik, botol, kertas).',
                'knowledge_base_version' => 'v2.1-PKM2026',
                'sequence_order' => 1,
                'is_active' => true,
            ],
            [
                'name' => 'Prinsip 3R Terapan di Pekon',
                'slug' => 'prinsip-3r',
                'description' => 'Metode Reduce (Kurangi), Reuse (Gunakan Kembali), dan Recycle (Daur Ulang) untuk skala rumah tangga di pedesaan.',
                'knowledge_base_version' => 'v2.1-PKM2026',
                'sequence_order' => 2,
                'is_active' => true,
            ],
            [
                'name' => 'Bank Sampah Berkah Margodadi',
                'slug' => 'bank-sampah',
                'description' => 'Alur tabungan sampah, katalog harga jual limbah per kilogram, jadwal penimbangan, dan penukaran saldo kas.',
                'knowledge_base_version' => 'v2.1-PKM2026',
                'sequence_order' => 3,
                'is_active' => true,
            ],
            [
                'name' => 'Bahaya Bakar Sampah Terbuka',
                'slug' => 'bahaya-bakar-sampah',
                'description' => 'Dampak racun dioksin, polusi udara ISPA bagi balita/lansia, serta regulasi larangan pembakaran sampah di pekon.',
                'knowledge_base_version' => 'v2.1-PKM2026',
                'sequence_order' => 4,
                'is_active' => true,
            ],
            [
                'name' => 'Komposting Sederhana Rumah Tangga',
                'slug' => 'komposting-sederhana',
                'description' => 'Teknik pembuatan pupuk kompos cair & padat menggunakan ember bekas dan EM4 untuk perkebunan pekarangan.',
                'knowledge_base_version' => 'v2.1-PKM2026',
                'sequence_order' => 5,
                'is_active' => true,
            ],
        ];

        $topics = [];
        foreach ($topicsData as $data) {
            $topics[$data['slug']] = EducationTopic::updateOrCreate(
                ['slug' => $data['slug']],
                $data
            );
        }

        // Ensure we have sample users for research sessions
        $sampleUsers = [];
        for ($i = 1; $i <= 10; $i++) {
            $code = 'RESP-' . str_pad($i, 3, '0', STR_PAD_LEFT);
            $user = User::firstOrCreate(
                ['anonymous_code' => $code],
                [
                    'phone_number' => '0812' . str_pad($i, 8, '0', STR_PAD_LEFT),
                ]
            );
            $sampleUsers[] = $user;
        }

        // Fetch some conversations if any
        $conversations = Conversation::take(10)->get();

        $sessionSamples = [
            [
                'code' => 'RESP-001',
                'topic_slug' => 'pemilahan-sampah',
                'score' => 100.0,
                'status' => ResearchSessionStatus::COMPLETED,
                'interactions' => 8,
                'hours_ago' => 2,
            ],
            [
                'code' => 'RESP-002',
                'topic_slug' => 'bank-sampah',
                'score' => 85.0,
                'status' => ResearchSessionStatus::COMPLETED,
                'interactions' => 12,
                'hours_ago' => 5,
            ],
            [
                'code' => 'RESP-003',
                'topic_slug' => 'prinsip-3r',
                'score' => 90.0,
                'status' => ResearchSessionStatus::COMPLETED,
                'interactions' => 6,
                'hours_ago' => 9,
            ],
            [
                'code' => 'RESP-004',
                'topic_slug' => 'bahaya-bakar-sampah',
                'score' => null,
                'status' => ResearchSessionStatus::IN_PROGRESS,
                'interactions' => 4,
                'hours_ago' => 1,
            ],
            [
                'code' => 'RESP-005',
                'topic_slug' => 'komposting-sederhana',
                'score' => 95.0,
                'status' => ResearchSessionStatus::COMPLETED,
                'interactions' => 10,
                'hours_ago' => 24,
            ],
            [
                'code' => 'RESP-006',
                'topic_slug' => 'bank-sampah',
                'score' => 100.0,
                'status' => ResearchSessionStatus::COMPLETED,
                'interactions' => 14,
                'hours_ago' => 30,
            ],
            [
                'code' => 'RESP-007',
                'topic_slug' => 'pemilahan-sampah',
                'score' => 75.0,
                'status' => ResearchSessionStatus::COMPLETED,
                'interactions' => 7,
                'hours_ago' => 36,
            ],
            [
                'code' => 'RESP-008',
                'topic_slug' => 'prinsip-3r',
                'score' => null,
                'status' => ResearchSessionStatus::IN_PROGRESS,
                'interactions' => 3,
                'hours_ago' => 3,
            ],
            [
                'code' => 'RESP-009',
                'topic_slug' => 'bahaya-bakar-sampah',
                'score' => 80.0,
                'status' => ResearchSessionStatus::COMPLETED,
                'interactions' => 5,
                'hours_ago' => 48,
            ],
            [
                'code' => 'RESP-010',
                'topic_slug' => 'komposting-sederhana',
                'score' => 88.0,
                'status' => ResearchSessionStatus::COMPLETED,
                'interactions' => 9,
                'hours_ago' => 52,
            ],
        ];

        foreach ($sessionSamples as $index => $sample) {
            $user = $sampleUsers[$index % count($sampleUsers)];
            $conv = $conversations->get($index % max(1, $conversations->count()));
            $topic = $topics[$sample['topic_slug']] ?? reset($topics);
            $startedAt = Carbon::now()->subHours($sample['hours_ago']);
            $completedAt = $sample['status'] === ResearchSessionStatus::COMPLETED
                ? $startedAt->copy()->addMinutes(15 + ($sample['interactions'] * 2))
                : null;

            $session = ResearchSession::updateOrCreate(
                ['anonymous_code' => $sample['code']],
                [
                    'user_id' => $user->user_id,
                    'conversation_id' => $conv ? $conv->conversation_id : null,
                    'education_version' => 'v2.1-PKM2026',
                    'quiz_score' => $sample['score'],
                    'status' => $sample['status'],
                    'started_at' => $startedAt,
                    'completed_at' => $completedAt,
                    'created_at' => $startedAt,
                ]
            );

            EducationExposure::updateOrCreate(
                [
                    'research_session_id' => $session->research_session_id,
                    'topic_id' => $topic->topic_id,
                ],
                [
                    'interaction_count' => $sample['interactions'],
                    'quiz_score' => $sample['score'],
                    'started_at' => $startedAt,
                    'completed_at' => $completedAt,
                    'created_at' => $startedAt,
                ]
            );
        }
    }
}
