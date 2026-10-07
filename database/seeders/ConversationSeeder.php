<?php

namespace Database\Seeders;

use App\Enums\ChannelType;
use App\Enums\ConversationStatus;
use App\Enums\HitlEventType;
use App\Enums\PriorityLevel;
use App\Enums\SenderType;
use App\Models\ActivityLog;
use App\Models\Conversation;
use App\Models\ConversationAssignment;
use App\Models\Feedback;
use App\Models\HitlEvent;
use App\Models\Message;
use App\Models\Operator;
use App\Models\ServiceCategory;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class ConversationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = Operator::where('email', 'admin@margodadi.desa.id')->first();
        $operator = Operator::where('email', 'operator@margodadi.desa.id')->first();
        $siti = Operator::where('email', 'siti.aminah@margodadi.desa.id')->first();
        $ahmad = Operator::where('email', 'ahmad.fauzi@margodadi.desa.id')->first();
        $budi = Operator::where('email', 'budi.santoso@margodadi.desa.id')->first();
        $nurul = Operator::where('email', 'nurul.hidayah@margodadi.desa.id')->first();

        $catAdmin = ServiceCategory::where('slug', 'layanan-kependudukan')->first();
        $catUmkm = ServiceCategory::where('slug', 'potensi-umkm')->first();
        $catSampah = ServiceCategory::where('slug', 'edukasi-sampah')->first();
        $catInfo = ServiceCategory::where('slug', 'informasi-publik')->first();

        $categories = [$catAdmin, $catUmkm, $catSampah, $catInfo];
        $operators = [$admin, $operator, $siti, $ahmad, $budi, $nurul];

        // ----------------------------------------------------
        // Detailed Active & Resolved Conversations for Today
        // ----------------------------------------------------

        // 1. SITI AMINAH - ASSIGNED (Bpk. Joko / Kependudukan)
        $userJoko = User::firstOrCreate(['phone_number' => '081234567801'], ['anonymous_code' => 'ANON-WA-001']);
        $conv1 = Conversation::updateOrCreate(
            ['external_conversation_id' => 'CV-00123'],
            [
                'user_id' => $userJoko->user_id,
                'category_id' => $catAdmin?->category_id,
                'assigned_operator_id' => $siti?->operator_id,
                'channel' => ChannelType::WHATSAPP,
                'status' => ConversationStatus::ASSIGNED,
                'priority' => PriorityLevel::HIGH,
                'needs_human' => true,
                'citizen_name' => 'Warga #A001 (Bpk. Joko)',
                'started_at' => now()->subMinutes(25),
                'last_message_at' => now()->subMinutes(5),
                'created_at' => now()->subMinutes(25),
            ]
        );
        Message::firstOrCreate(
            ['conversation_id' => $conv1->conversation_id, 'content' => 'Halo selamat pagi, mau tanya syarat bikin Surat Keterangan Usaha (SKU) untuk pengajuan KUR BRI apa saja ya?'],
            ['sender_type' => SenderType::USER, 'created_at' => now()->subMinutes(25)]
        );
        Message::firstOrCreate(
            ['conversation_id' => $conv1->conversation_id, 'content' => 'Selamat pagi! Berdasarkan SOP Pelayanan Administrasi Pekon Margodadi, syarat pengurusan SKU meliputi: 1. Pengantar dari RT/RW setempat, 2. Fotokopi KTP & KK Pemohon, 3. Foto lokasi usaha / jenis usaha. Jam pelayanan kantor pekon adalah Senin-Jumat pukul 08.00 - 15.00 WIB.'],
            ['sender_type' => SenderType::BOT, 'created_at' => now()->subMinutes(24)]
        );
        Message::firstOrCreate(
            ['conversation_id' => $conv1->conversation_id, 'content' => 'Kalau KTP saya masih alamat pekon sebelah tapi usaha sudah jalan 3 tahun di Dusun 2 Margodadi apakah tetap bisa diterbitkan suratnya?'],
            ['sender_type' => SenderType::USER, 'created_at' => now()->subMinutes(20)]
        );
        Message::firstOrCreate(
            ['conversation_id' => $conv1->conversation_id, 'content' => 'Pertanyaan ini memerlukan verifikasi kebijakan domisili usaha khusus. Mengalihkan percakapan ke operator aparatur pekon...'],
            ['sender_type' => SenderType::SYSTEM, 'metadata' => ['is_alert' => true], 'created_at' => now()->subMinutes(19)]
        );
        Message::firstOrCreate(
            ['conversation_id' => $conv1->conversation_id, 'content' => 'Selamat pagi Pak Joko, saya Siti dari Seksi Pelayanan Pekon Margodadi. Untuk kasus KTP luar domisili, Bapak perlu melampirkan Surat Keterangan Domisili Tempat Tinggal sementara dan Surat Perjanjian Sewa/Keterangan Tempat Usaha dari pemilik tanah dusun 2.'],
            ['sender_type' => SenderType::OPERATOR, 'operator_id' => $siti?->operator_id, 'created_at' => now()->subMinutes(12)]
        );
        Message::firstOrCreate(
            ['conversation_id' => $conv1->conversation_id, 'content' => 'Baik bu, berkas surat pengantar RT sudah saya scan.'],
            ['sender_type' => SenderType::USER, 'created_at' => now()->subMinutes(5)]
        );
        HitlEvent::firstOrCreate(
            ['conversation_id' => $conv1->conversation_id, 'event_type' => HitlEventType::NEED_HUMAN],
            ['notes' => 'AI mendeteksi pertanyaan domisili khusus non-katalog', 'created_at' => now()->subMinutes(19)]
        );
        ConversationAssignment::firstOrCreate(
            ['conversation_id' => $conv1->conversation_id, 'operator_id' => $siti?->operator_id],
            ['assigned_at' => now()->subMinutes(15), 'created_at' => now()->subMinutes(15)]
        );

        // 2. OPEN UNASSIGNED - URGENT (Ibu Ratna / Sampah)
        $userRatna = User::firstOrCreate(['phone_number' => '081234567802'], ['anonymous_code' => 'ANON-WEB-092']);
        $conv2 = Conversation::updateOrCreate(
            ['external_conversation_id' => 'CV-00124'],
            [
                'user_id' => $userRatna->user_id,
                'category_id' => $catSampah?->category_id,
                'assigned_operator_id' => null,
                'channel' => ChannelType::WEB,
                'status' => ConversationStatus::OPEN,
                'priority' => PriorityLevel::URGENT,
                'needs_human' => true,
                'citizen_name' => 'Warga #W092 (Ibu Ratna)',
                'started_at' => now()->subMinutes(15),
                'last_message_at' => now()->subMinutes(2),
                'created_at' => now()->subMinutes(15),
            ]
        );
        Message::firstOrCreate(
            ['conversation_id' => $conv2->conversation_id, 'content' => 'Halo, saya mau ikut program Bank Sampah Berkah Margodadi.'],
            ['sender_type' => SenderType::USER, 'created_at' => now()->subMinutes(15)]
        );
        Message::firstOrCreate(
            ['conversation_id' => $conv2->conversation_id, 'content' => 'Halo Ibu Ratna! Program Bank Sampah Berkah melayani penimbangan setiap hari Minggu ke-2 dan ke-4 di Balai Dusun 1. Kategori yang diterima meliputi kardus, botol plastik PET, kaleng, dan buku bekas.'],
            ['sender_type' => SenderType::BOT, 'created_at' => now()->subMinutes(14)]
        );
        Message::firstOrCreate(
            ['conversation_id' => $conv2->conversation_id, 'content' => 'Apakah minyak jelantah bekas gorengan bisa disetor ke Bank Sampah Berkah? Saya ada 5 jerigen.'],
            ['sender_type' => SenderType::USER, 'created_at' => now()->subMinutes(2)]
        );
        HitlEvent::firstOrCreate(
            ['conversation_id' => $conv2->conversation_id, 'event_type' => HitlEventType::NEED_HUMAN],
            ['notes' => 'User menanyakan komoditas jelantah non-katalog', 'created_at' => now()->subMinutes(2)]
        );

        // 3. AHMAD FAUZI - ASSIGNED (Pak Hendra / UMKM)
        $userHendra = User::firstOrCreate(['phone_number' => '081234567803'], ['anonymous_code' => 'ANON-WA-015']);
        $conv3 = Conversation::updateOrCreate(
            ['external_conversation_id' => 'CV-00125'],
            [
                'user_id' => $userHendra->user_id,
                'category_id' => $catUmkm?->category_id,
                'assigned_operator_id' => $ahmad?->operator_id,
                'channel' => ChannelType::WHATSAPP,
                'status' => ConversationStatus::ASSIGNED,
                'priority' => PriorityLevel::MEDIUM,
                'needs_human' => false,
                'citizen_name' => 'Warga #A015 (Pak Hendra)',
                'started_at' => now()->subMinutes(60),
                'last_message_at' => now()->subMinutes(30),
                'created_at' => now()->subMinutes(60),
            ]
        );
        Message::firstOrCreate(
            ['conversation_id' => $conv3->conversation_id, 'content' => 'Bagaimana cara mendaftarkan produk keripik pisang saya ke etalase website UMKM Desa Margodadi?'],
            ['sender_type' => SenderType::USER, 'created_at' => now()->subMinutes(60)]
        );
        Message::firstOrCreate(
            ['conversation_id' => $conv3->conversation_id, 'content' => 'Selamat pagi Pak Hendra. Pendaftaran bisa langsung mengisi formulir online di menu Potensi UMKM atau membawa sampel produk & foto ke kantor pekon setiap jam kerja.'],
            ['sender_type' => SenderType::OPERATOR, 'operator_id' => $ahmad?->operator_id, 'created_at' => now()->subMinutes(45)]
        );
        ConversationAssignment::firstOrCreate(
            ['conversation_id' => $conv3->conversation_id, 'operator_id' => $ahmad?->operator_id],
            ['assigned_at' => now()->subMinutes(50), 'created_at' => now()->subMinutes(50)]
        );

        // 4. SITI AMINAH - RESOLVED (Warga Anonim / Info Publik)
        $userAnon = User::firstOrCreate(['anonymous_code' => 'ANON-WEB-088']);
        $conv4 = Conversation::updateOrCreate(
            ['external_conversation_id' => 'CV-00122'],
            [
                'user_id' => $userAnon->user_id,
                'category_id' => $catInfo?->category_id,
                'assigned_operator_id' => $siti?->operator_id,
                'channel' => ChannelType::WEB,
                'status' => ConversationStatus::RESOLVED,
                'priority' => PriorityLevel::LOW,
                'needs_human' => false,
                'citizen_name' => 'Warga #W088 (Anonim)',
                'started_at' => now()->subHours(3),
                'last_message_at' => now()->subHours(2),
                'resolved_at' => now()->subHours(2),
                'created_at' => now()->subHours(3),
            ]
        );
        Message::firstOrCreate(
            ['conversation_id' => $conv4->conversation_id, 'content' => 'Jadwal pelayanan posyandu balita dusun 3 tanggal berapa ya?'],
            ['sender_type' => SenderType::USER, 'created_at' => now()->subHours(3)]
        );
        Message::firstOrCreate(
            ['conversation_id' => $conv4->conversation_id, 'content' => 'Jadwal Posyandu Melati Dusun 3 diadakan rutin setiap tanggal 18 pukul 08.30 WIB di Balai Posyandu Dusun 3.'],
            ['sender_type' => SenderType::BOT, 'created_at' => now()->subHours(3)]
        );
        ConversationAssignment::firstOrCreate(
            ['conversation_id' => $conv4->conversation_id, 'operator_id' => $siti?->operator_id],
            ['assigned_at' => now()->subHours(3), 'unassigned_at' => now()->subHours(2), 'created_at' => now()->subHours(3)]
        );
        Feedback::firstOrCreate(
            ['conversation_id' => $conv4->conversation_id],
            ['user_id' => $userAnon->user_id, 'rating' => 5, 'comment' => 'Pelayanan cepat dan informatif', 'created_at' => now()->subHours(2)]
        );

        // 5. OPERATOR MARGODADI - ASSIGNED (Pak Bambang / Kependudukan)
        $userBambang = User::firstOrCreate(['phone_number' => '081234567804'], ['anonymous_code' => 'ANON-WA-004']);
        $conv5 = Conversation::updateOrCreate(
            ['external_conversation_id' => 'CV-00121'],
            [
                'user_id' => $userBambang->user_id,
                'category_id' => $catAdmin?->category_id,
                'assigned_operator_id' => $operator?->operator_id,
                'channel' => ChannelType::WHATSAPP,
                'status' => ConversationStatus::ASSIGNED,
                'priority' => PriorityLevel::HIGH,
                'needs_human' => true,
                'citizen_name' => 'Warga #A004 (Pak Bambang)',
                'started_at' => now()->subMinutes(40),
                'last_message_at' => now()->subMinutes(8),
                'created_at' => now()->subMinutes(40),
            ]
        );
        Message::firstOrCreate(
            ['conversation_id' => $conv5->conversation_id, 'content' => 'Selamat siang, saya mau perbarui Kartu Keluarga karena ada penambahan anggota keluarga baru.'],
            ['sender_type' => SenderType::USER, 'created_at' => now()->subMinutes(40)]
        );
        Message::firstOrCreate(
            ['conversation_id' => $conv5->conversation_id, 'content' => 'Selamat siang Pak Bambang. Untuk penambahan anak baru, silakan siapkan Surat Keterangan Kelahiran dari Bidan/RS, Buku Nikah Orang Tua, dan KK Asli.'],
            ['sender_type' => SenderType::OPERATOR, 'operator_id' => $operator?->operator_id, 'created_at' => now()->subMinutes(25)]
        );
        ConversationAssignment::firstOrCreate(
            ['conversation_id' => $conv5->conversation_id, 'operator_id' => $operator?->operator_id],
            ['assigned_at' => now()->subMinutes(35), 'created_at' => now()->subMinutes(35)]
        );

        // 6. OPERATOR MARGODADI - RESOLVED (Ibu Dewi / Edukasi Sampah)
        $userDewi = User::firstOrCreate(['phone_number' => '081234567805'], ['anonymous_code' => 'ANON-WA-005']);
        $conv6 = Conversation::updateOrCreate(
            ['external_conversation_id' => 'CV-00128'],
            [
                'user_id' => $userDewi->user_id,
                'category_id' => $catSampah?->category_id,
                'assigned_operator_id' => $operator?->operator_id,
                'channel' => ChannelType::WHATSAPP,
                'status' => ConversationStatus::RESOLVED,
                'priority' => PriorityLevel::MEDIUM,
                'needs_human' => false,
                'citizen_name' => 'Warga #A005 (Ibu Dewi)',
                'started_at' => now()->subHours(4),
                'last_message_at' => now()->subHours(3),
                'resolved_at' => now()->subHours(3),
                'created_at' => now()->subHours(4),
            ]
        );
        Message::firstOrCreate(
            ['conversation_id' => $conv6->conversation_id, 'content' => 'Apakah botol kaca kecap dan sirup diterima di bank sampah pekon?'],
            ['sender_type' => SenderType::USER, 'created_at' => now()->subHours(4)]
        );
        Message::firstOrCreate(
            ['conversation_id' => $conv6->conversation_id, 'content' => 'Betul Bu Dewi, botol kaca sirup dan kecap diterima dengan tarif Rp 500/botol utuh tidak pecah.'],
            ['sender_type' => SenderType::OPERATOR, 'operator_id' => $operator?->operator_id, 'created_at' => now()->subHours(3)->subMinutes(30)]
        );
        Feedback::firstOrCreate(
            ['conversation_id' => $conv6->conversation_id],
            ['user_id' => $userDewi->user_id, 'rating' => 5, 'comment' => 'Respons sangat ramah', 'created_at' => now()->subHours(3)]
        );

        // 7. ADMIN MARGODADI - ASSIGNED (Ibu Sumarni / Administrasi)
        $userSumarni = User::firstOrCreate(['phone_number' => '081234567806'], ['anonymous_code' => 'ANON-WA-006']);
        $conv7 = Conversation::updateOrCreate(
            ['external_conversation_id' => 'CV-00126'],
            [
                'user_id' => $userSumarni->user_id,
                'category_id' => $catAdmin?->category_id,
                'assigned_operator_id' => $admin?->operator_id,
                'channel' => ChannelType::WHATSAPP,
                'status' => ConversationStatus::ASSIGNED,
                'priority' => PriorityLevel::URGENT,
                'needs_human' => true,
                'citizen_name' => 'Warga #A006 (Ibu Sumarni)',
                'started_at' => now()->subMinutes(50),
                'last_message_at' => now()->subMinutes(10),
                'created_at' => now()->subMinutes(50),
            ]
        );
        Message::firstOrCreate(
            ['conversation_id' => $conv7->conversation_id, 'content' => 'Mohon bantuan disposisi validasi tanah waris keluarga di Dusun 4 Margodadi.'],
            ['sender_type' => SenderType::USER, 'created_at' => now()->subMinutes(50)]
        );
        Message::firstOrCreate(
            ['conversation_id' => $conv7->conversation_id, 'content' => 'Selamat siang Bu Sumarni, permohonan sedang ditinjau langsung oleh Admin Kasi Pemerintahan Pekon Margodadi.'],
            ['sender_type' => SenderType::OPERATOR, 'operator_id' => $admin?->operator_id, 'created_at' => now()->subMinutes(20)]
        );

        // 8. ADMIN MARGODADI - RESOLVED (Pak Teguh / Info Publik)
        $userTeguh = User::firstOrCreate(['anonymous_code' => 'ANON-WEB-033']);
        Conversation::updateOrCreate(
            ['external_conversation_id' => 'CV-00127'],
            [
                'user_id' => $userTeguh->user_id,
                'category_id' => $catInfo?->category_id,
                'assigned_operator_id' => $admin?->operator_id,
                'channel' => ChannelType::WEB,
                'status' => ConversationStatus::RESOLVED,
                'priority' => PriorityLevel::LOW,
                'needs_human' => false,
                'citizen_name' => 'Warga #W033 (Pak Teguh)',
                'started_at' => now()->subHours(6),
                'last_message_at' => now()->subHours(5),
                'resolved_at' => now()->subHours(5),
                'created_at' => now()->subHours(6),
            ]
        );

        // 9. BUDI SANTOSO - ASSIGNED (Pak Yanto / UMKM)
        $userYanto = User::firstOrCreate(['phone_number' => '081234567807'], ['anonymous_code' => 'ANON-WA-007']);
        Conversation::updateOrCreate(
            ['external_conversation_id' => 'CV-00129'],
            [
                'user_id' => $userYanto->user_id,
                'category_id' => $catUmkm?->category_id,
                'assigned_operator_id' => $budi?->operator_id,
                'channel' => ChannelType::WHATSAPP,
                'status' => ConversationStatus::ASSIGNED,
                'priority' => PriorityLevel::MEDIUM,
                'needs_human' => false,
                'citizen_name' => 'Warga #A007 (Pak Yanto)',
                'started_at' => now()->subMinutes(35),
                'last_message_at' => now()->subMinutes(15),
                'created_at' => now()->subMinutes(35),
            ]
        );

        // 10. BUDI SANTOSO - RESOLVED (Ibu Linda / Kependudukan)
        $userLinda = User::firstOrCreate(['anonymous_code' => 'ANON-WEB-055']);
        Conversation::updateOrCreate(
            ['external_conversation_id' => 'CV-00130'],
            [
                'user_id' => $userLinda->user_id,
                'category_id' => $catAdmin?->category_id,
                'assigned_operator_id' => $budi?->operator_id,
                'channel' => ChannelType::WEB,
                'status' => ConversationStatus::RESOLVED,
                'priority' => PriorityLevel::LOW,
                'needs_human' => false,
                'citizen_name' => 'Warga #W055 (Ibu Linda)',
                'started_at' => now()->subHours(5),
                'last_message_at' => now()->subHours(4),
                'resolved_at' => now()->subHours(4),
                'created_at' => now()->subHours(5),
            ]
        );

        // 11. NURUL HIDAYAH - ASSIGNED (Mas Rizky / Sampah)
        $userRizky = User::firstOrCreate(['phone_number' => '081234567808'], ['anonymous_code' => 'ANON-WA-008']);
        Conversation::updateOrCreate(
            ['external_conversation_id' => 'CV-00131'],
            [
                'user_id' => $userRizky->user_id,
                'category_id' => $catSampah?->category_id,
                'assigned_operator_id' => $nurul?->operator_id,
                'channel' => ChannelType::WHATSAPP,
                'status' => ConversationStatus::ASSIGNED,
                'priority' => PriorityLevel::MEDIUM,
                'needs_human' => false,
                'citizen_name' => 'Warga #A008 (Mas Rizky)',
                'started_at' => now()->subMinutes(18),
                'last_message_at' => now()->subMinutes(4),
                'created_at' => now()->subMinutes(18),
            ]
        );

        // 12. NURUL HIDAYAH - RESOLVED (Ibu Maya / UMKM)
        $userMaya = User::firstOrCreate(['anonymous_code' => 'ANON-WEB-077']);
        Conversation::updateOrCreate(
            ['external_conversation_id' => 'CV-00132'],
            [
                'user_id' => $userMaya->user_id,
                'category_id' => $catUmkm?->category_id,
                'assigned_operator_id' => $nurul?->operator_id,
                'channel' => ChannelType::WEB,
                'status' => ConversationStatus::RESOLVED,
                'priority' => PriorityLevel::LOW,
                'needs_human' => false,
                'citizen_name' => 'Warga #W077 (Ibu Maya)',
                'started_at' => now()->subHours(2),
                'last_message_at' => now()->subHour(),
                'resolved_at' => now()->subHour(),
                'created_at' => now()->subHours(2),
            ]
        );

        // ----------------------------------------------------
        // Historical Conversations Across the Past 6 Days (Real Daily Trend)
        // ----------------------------------------------------
        $historicalRecords = [
            // Day -1 (Kemarin)
            ['days_ago' => 1, 'count' => 6, 'human_count' => 2, 'channel' => ChannelType::WHATSAPP, 'topic' => 'Kependudukan & UMKM'],
            // Day -2 (2 hari lalu)
            ['days_ago' => 2, 'count' => 5, 'human_count' => 1, 'channel' => ChannelType::WEB, 'topic' => 'Edukasi Sampah'],
            // Day -3 (3 hari lalu)
            ['days_ago' => 3, 'count' => 7, 'human_count' => 2, 'channel' => ChannelType::WHATSAPP, 'topic' => 'Layanan KTP/KK'],
            // Day -4 (4 hari lalu)
            ['days_ago' => 4, 'count' => 8, 'human_count' => 3, 'channel' => ChannelType::WEB, 'topic' => 'Informasi APBDes'],
            // Day -5 (5 hari lalu)
            ['days_ago' => 5, 'count' => 6, 'human_count' => 2, 'channel' => ChannelType::WHATSAPP, 'topic' => 'Katalog UMKM'],
            // Day -6 (6 hari lalu)
            ['days_ago' => 6, 'count' => 5, 'human_count' => 1, 'channel' => ChannelType::WEB, 'topic' => 'Jadwal Posyandu'],
        ];

        foreach ($historicalRecords as $record) {
            $daysAgo = $record['days_ago'];
            $timestamp = Carbon::now()->subDays($daysAgo)->setHour(10)->setMinute(15);

            for ($k = 0; $k < $record['count']; $k++) {
                $isHuman = $k < $record['human_count'];
                $cat = $categories[$k % count($categories)];
                $op = $isHuman ? $operators[$k % count($operators)] : null;
                $extId = 'CV-HIST-D'.$daysAgo.'-0'.($k + 1);

                $user = User::firstOrCreate(
                    ['anonymous_code' => 'ANON-HIST-'.$daysAgo.'-'.$k],
                    ['phone_number' => $record['channel'] === ChannelType::WHATSAPP ? ('08139988'.str_pad($daysAgo.$k, 4, '0', STR_PAD_LEFT)) : null]
                );

                $conv = Conversation::updateOrCreate(
                    ['external_conversation_id' => $extId],
                    [
                        'user_id' => $user->user_id,
                        'category_id' => $cat?->category_id,
                        'assigned_operator_id' => $op?->operator_id,
                        'channel' => $record['channel'],
                        'status' => $isHuman ? ConversationStatus::RESOLVED : ConversationStatus::RESOLVED,
                        'priority' => $isHuman ? PriorityLevel::HIGH : PriorityLevel::LOW,
                        'needs_human' => $isHuman,
                        'citizen_name' => 'Warga Pekon #H'.$daysAgo.str_pad($k, 2, '0', STR_PAD_LEFT),
                        'started_at' => $timestamp->copy()->addMinutes($k * 20),
                        'last_message_at' => $timestamp->copy()->addMinutes($k * 20 + 15),
                        'resolved_at' => $timestamp->copy()->addMinutes($k * 20 + 20),
                        'created_at' => $timestamp->copy()->addMinutes($k * 20),
                    ]
                );

                Message::firstOrCreate(
                    ['conversation_id' => $conv->conversation_id, 'content' => 'Tanya info '.$record['topic'].' Pekon Margodadi.'],
                    ['sender_type' => SenderType::USER, 'created_at' => $timestamp->copy()->addMinutes($k * 20)]
                );

                if ($isHuman && $op) {
                    Message::firstOrCreate(
                        ['conversation_id' => $conv->conversation_id, 'content' => 'Halo, saya '.$op->name.' dari aparatur pekon. Berikut penjelasannya sesuai regulasi.'],
                        ['sender_type' => SenderType::OPERATOR, 'operator_id' => $op->operator_id, 'created_at' => $timestamp->copy()->addMinutes($k * 20 + 3)]
                    );

                    ConversationAssignment::firstOrCreate(
                        ['conversation_id' => $conv->conversation_id, 'operator_id' => $op->operator_id],
                        ['assigned_at' => $timestamp->copy()->addMinutes($k * 20 + 1), 'unassigned_at' => $timestamp->copy()->addMinutes($k * 20 + 20), 'created_at' => $timestamp->copy()->addMinutes($k * 20 + 1)]
                    );
                } else {
                    Message::firstOrCreate(
                        ['conversation_id' => $conv->conversation_id, 'content' => 'Informasi '.$record['topic'].' telah tercatat dalam basis data resmi Pekon Margodadi.'],
                        ['sender_type' => SenderType::BOT, 'created_at' => $timestamp->copy()->addMinutes($k * 20 + 1)]
                    );
                }
            }
        }

        // ----------------------------------------------------
        // Activity Logs
        // ----------------------------------------------------
        ActivityLog::firstOrCreate(
            ['target' => '#CV-'.$conv1->conversation_id, 'action' => 'OPERATOR_RESPONSE'],
            [
                'operator_id' => $siti?->operator_id,
                'actor_name' => $siti?->name ?? 'Siti Aminah',
                'description' => 'Operator membalas panduan domisili usaha SKU warga',
                'channel' => ChannelType::WHATSAPP,
                'created_at' => now()->subMinutes(12),
            ]
        );
        ActivityLog::firstOrCreate(
            ['target' => '#CV-'.$conv3->conversation_id, 'action' => 'OPERATOR_RESPONSE'],
            [
                'operator_id' => $ahmad?->operator_id,
                'actor_name' => $ahmad?->name ?? 'Ahmad Fauzi',
                'description' => 'Operator memberikan panduan kurasi produk UMKM',
                'channel' => ChannelType::WHATSAPP,
                'created_at' => now()->subMinutes(45),
            ]
        );
        ActivityLog::firstOrCreate(
            ['target' => '#CV-'.$conv5->conversation_id, 'action' => 'OPERATOR_RESPONSE'],
            [
                'operator_id' => $operator?->operator_id,
                'actor_name' => $operator?->name ?? 'Operator Margodadi',
                'description' => 'Operator memandu syarat penambahan anggota baru Kartu Keluarga',
                'channel' => ChannelType::WHATSAPP,
                'created_at' => now()->subMinutes(25),
            ]
        );
        ActivityLog::firstOrCreate(
            ['target' => '#CV-'.$conv7->conversation_id, 'action' => 'OPERATOR_RESPONSE'],
            [
                'operator_id' => $admin?->operator_id,
                'actor_name' => $admin?->name ?? 'Admin Margodadi',
                'description' => 'Admin meninjau disposisi administrasi tanah warga',
                'channel' => ChannelType::WHATSAPP,
                'created_at' => now()->subMinutes(20),
            ]
        );
    }
}
