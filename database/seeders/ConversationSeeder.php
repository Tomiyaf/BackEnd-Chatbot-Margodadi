<?php

namespace Database\Seeders;

use App\Enums\ChannelType;
use App\Enums\ConversationStatus;
use App\Enums\PriorityLevel;
use App\Enums\SenderType;
use App\Models\Conversation;
use App\Models\ConversationAssignment;
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
        if (!$admin) {
            return;
        }

        $catKependudukan = ServiceCategory::where('slug', 'administrasi-kependudukan')->first()
            ?? ServiceCategory::first();

        // Buat 1 contoh interaksi warga untuk demonstrasi live chat admin
        $userWarga = User::firstOrCreate(
            ['phone_number' => '082188990011'],
            ['anonymous_code' => 'WARGA-MGD-001']
        );

        $conv = Conversation::updateOrCreate(
            ['external_conversation_id' => 'CV-MGD-0001'],
            [
                'user_id' => $userWarga->user_id,
                'channel' => ChannelType::WHATSAPP,
                'status' => ConversationStatus::ASSIGNED,
                'priority' => PriorityLevel::MEDIUM,
                'category_id' => $catKependudukan?->category_id,
                'assigned_operator_id' => $admin->operator_id,
                'needs_human' => true,
                'citizen_name' => 'Warga Margodadi',
                'started_at' => Carbon::now()->subMinutes(15),
                'last_message_at' => Carbon::now()->subMinutes(2),
            ]
        );

        ConversationAssignment::updateOrCreate(
            ['conversation_id' => $conv->conversation_id, 'operator_id' => $admin->operator_id],
            [
                'assigned_at' => Carbon::now()->subMinutes(14),
                'unassigned_at' => null,
            ]
        );

        // Pesan contoh
        Message::firstOrCreate(
            ['conversation_id' => $conv->conversation_id, 'sender_type' => SenderType::USER],
            [
                'content' => 'Halo Admin Pekon Margodadi, saya mau menanyakan syarat pembuatan Surat Keterangan Usaha (SKU) untuk warung saya di Dusun Margodadi.',
                'created_at' => Carbon::now()->subMinutes(15),
            ]
        );

        Message::firstOrCreate(
            ['conversation_id' => $conv->conversation_id, 'sender_type' => SenderType::BOT],
            [
                'content' => 'Halo! Untuk pengurusan Surat Keterangan Usaha (SKU) di Pekon Margodadi persyaratannya adalah: 1. Surat pengantar dari RT/RW setempat, 2. Fotokopi KTP & KK pemohon, 3. Foto tempat usaha. Layanan ini GRATIS tanpa dipungut biaya apapun.',
                'created_at' => Carbon::now()->subMinutes(14),
            ]
        );

        Message::firstOrCreate(
            ['conversation_id' => $conv->conversation_id, 'sender_type' => SenderType::OPERATOR],
            [
                'operator_id' => $admin->operator_id,
                'content' => 'Betul bapak/ibu, silakan berkasnya dibawa ke Kantor Balai Pekon Margodadi pada jam kerja Senin-Jumat pukul 08.00 - 15.00 WIB menemui Kasi Pemerintahan.',
                'created_at' => Carbon::now()->subMinutes(2),
            ]
        );
    }
}
