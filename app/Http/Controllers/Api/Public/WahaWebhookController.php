<?php

namespace App\Http\Controllers\Api\Public;

use App\Enums\ChannelType;
use App\Enums\ConversationStatus;
use App\Enums\HitlEventType;
use App\Enums\PriorityLevel;
use App\Enums\SenderType;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Conversation;
use App\Models\HitlEvent;
use App\Models\Message;
use App\Models\Operator;
use App\Models\User;
use App\Services\RagGatewayService;
use App\Services\WahaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class WahaWebhookController extends Controller
{
    public function __construct(
        protected WahaService $wahaService,
        protected RagGatewayService $ragGateway
    ) {}

    /**
     * Handle incoming webhook requests from WAHA.
     */
    public function handleWebhook(Request $request): JsonResponse
    {
        $event = (string) $request->input('event', '');
        $payload = $request->input('payload', []);

        // Log incoming webhook event for debugging
        Log::info('WAHA webhook received', [
            'event' => $event,
            'from' => $payload['from'] ?? null,
            'fromMe' => $payload['fromMe'] ?? null,
        ]);

        // Acknowledge non-message events immediately
        if (! str_starts_with($event, 'message') || empty($payload)) {
            return response()->json(['status' => 'ignored', 'reason' => 'Not a message event']);
        }

        // 1. Ignore outgoing messages sent by the bot itself
        if (! empty($payload['fromMe'])) {
            return response()->json(['status' => 'ignored', 'reason' => 'Outgoing message fromMe']);
        }

        // 2. Extract chat identifier & filter broadcast/groups
        $chatId = trim((string) ($payload['from'] ?? ''));
        if (empty($chatId) || str_contains($chatId, 'broadcast')) {
            return response()->json(['status' => 'ignored', 'reason' => 'Invalid or broadcast sender']);
        }

        // Ignore group messages (focus on 1-on-1 citizen services)
        if (str_ends_with($chatId, '@g.us')) {
            return response()->json(['status' => 'ignored', 'reason' => 'Group messages not supported']);
        }

        // 3. Extract text content
        $text = trim((string) ($payload['body'] ?? ''));
        if (empty($text)) {
            return response()->json(['status' => 'ignored', 'reason' => 'Empty text message']);
        }

        // 4. Mark message as seen / read receipt
        $wahaMessageId = $payload['id'] ?? null;
        $this->wahaService->sendSeen($chatId, $wahaMessageId);

        // 5. Extract contact details
        $rawNumber = explode('@', $chatId)[0];
        $phoneNumber = preg_replace('/[^0-9]/', '', $rawNumber);
        $citizenName = $payload['_data']['notifyName'] 
            ?? $payload['pushName'] 
            ?? ('Warga ' . substr($phoneNumber, -4));

        // 6. Find or create User
        $user = User::where('phone_number', $phoneNumber)->first();
        if (! $user) {
            $user = User::create([
                'phone_number' => $phoneNumber,
                'anonymous_code' => 'WA-' . substr($phoneNumber, -4),
            ]);
        }

        // 7. Find active conversation or create new one
        $conversation = Conversation::where('channel', ChannelType::WHATSAPP)
            ->where('external_conversation_id', $chatId)
            ->whereIn('status', [ConversationStatus::OPEN, ConversationStatus::ASSIGNED, ConversationStatus::PENDING])
            ->latest('started_at')
            ->first();

        $isNewSession = false;
        if (! $conversation) {
            $conversation = Conversation::create([
                'user_id' => $user->user_id,
                'external_conversation_id' => $chatId,
                'channel' => ChannelType::WHATSAPP,
                'status' => ConversationStatus::OPEN,
                'needs_human' => false,
                'priority' => PriorityLevel::MEDIUM,
                'citizen_name' => $citizenName,
                'started_at' => now(),
                'last_message_at' => now(),
            ]);
            $isNewSession = true;

            ActivityLog::create([
                'operator_id' => null,
                'actor_name' => $citizenName . ' (' . $phoneNumber . ')',
                'action' => 'CHAT_SESSION_START',
                'target' => '#' . $chatId,
                'description' => 'Warga memulai percakapan baru melalui WhatsApp',
                'channel' => ChannelType::WHATSAPP,
                'created_at' => now(),
            ]);
        } else {
            $conversation->update(['last_message_at' => now()]);
        }

        // 8. Save Citizen Message
        Message::create([
            'conversation_id' => $conversation->conversation_id,
            'sender_type' => SenderType::USER,
            'content' => $text,
            'metadata' => [
                'waha_message_id' => $wahaMessageId,
                'timestamp' => $payload['timestamp'] ?? time(),
            ],
        ]);

        // ============================================================
        // 9. CHECK INTERVENTION MODE (Operator Human vs AI)
        // ============================================================
        // If conversation is currently taken over by Human Operator:
        // DO NOT let AI answer. Keep in queue for operator to reply from web.
        if ($conversation->needs_human) {
            Log::info('WhatsApp message received in Operator Intervention mode', [
                'conversation_id' => $conversation->conversation_id,
                'chatId' => $chatId,
            ]);

            return response()->json([
                'status' => 'success',
                'mode' => 'OPERATOR',
                'message' => 'Message queued for operator intervention.',
            ]);
        }

        // ============================================================
        // 10. CHECK AUTO-HANDOFF HITL KEYWORDS
        // ============================================================
        $q = strtolower($text);
        $hitlKeywords = [
            'operator', 'manusia', 'petugas', 'kadus', 'lurah', 'kepala pekon',
            'bantuan langsung', 'tidak paham', 'salah', 'komplain', 'dinas luar',
            'mendesak', 'diskresi', 'hubungi admin', 'bicara dengan orang', 'tolong bantuan'
        ];

        $needsHuman = false;
        foreach ($hitlKeywords as $kw) {
            if (str_contains($q, $kw)) {
                $needsHuman = true;
                break;
            }
        }

        if ($needsHuman) {
            return $this->triggerAutoHandoff($conversation, $chatId, $citizenName, $text);
        }

        // ============================================================
        // 11. PROCESS WITH RAG AI & REPLY VIA WHATSAPP
        // ============================================================
        $this->wahaService->startTyping($chatId);

        try {
            $ragResponse = $this->ragGateway->chat($text, 3, 'whatsapp:'.$conversation->conversation_id);
            $aiAnswer = trim((string) ($ragResponse['answer'] ?? ''));

            if (empty($aiAnswer)) {
                $aiAnswer = 'Maaf, informasi tersebut tidak tersedia dalam basis data Pekon Margodadi.';
            }

            // Append helpful footer
            $replyWithFooter = $aiAnswer . "\n\n_Butuh bantuan petugas? Ketik *operator* untuk terhubung langsung._";

            // Send answer via WAHA
            $this->wahaService->sendText($chatId, $replyWithFooter);

            // Save AI reply in database
            Message::create([
                'conversation_id' => $conversation->conversation_id,
                'sender_type' => SenderType::BOT,
                'content' => $aiAnswer,
            ]);

            return response()->json([
                'status' => 'success',
                'mode' => 'AI',
                'answer' => $aiAnswer,
            ]);
        } catch (Throwable $e) {
            Log::error('WAHA RAG chat processing failed', [
                'conversation_id' => $conversation->conversation_id,
                'error' => $e->getMessage(),
            ]);

            // Auto-fallback escalation so citizen is not stranded
            return $this->triggerAutoHandoff(
                $conversation,
                $chatId,
                $citizenName,
                $text,
                'Sistem AI mengalami kendala, dialihkan otomatis ke operator.'
            );
        } finally {
            $this->wahaService->stopTyping($chatId);
        }
    }

    /**
     * Trigger auto-handoff escalation to human operator.
     */
    protected function triggerAutoHandoff(
        Conversation $conversation,
        string $chatId,
        string $citizenName,
        string $originalText,
        ?string $reason = null
    ): JsonResponse {
        $operator = Operator::where('is_active', true)->where('role', 'OPERATOR')->first()
            ?: Operator::where('is_active', true)->first();

        $conversation->update([
            'needs_human' => true,
            'status' => ConversationStatus::ASSIGNED,
            'priority' => PriorityLevel::HIGH,
            'assigned_operator_id' => $operator?->operator_id,
        ]);

        HitlEvent::create([
            'conversation_id' => $conversation->conversation_id,
            'operator_id' => $operator?->operator_id,
            'event_type' => HitlEventType::NEED_HUMAN,
            'notes' => $reason ?: ('Permintaan bantuan operator via WhatsApp: ' . Str::limit($originalText, 80)),
            'created_at' => now(),
        ]);

        ActivityLog::create([
            'operator_id' => $operator?->operator_id,
            'action' => 'HITL_ESCALATION',
            'actor_name' => 'Sistem RAG WhatsApp',
            'target' => $chatId,
            'channel' => ChannelType::WHATSAPP,
            'description' => 'Percakapan WA dialihkan ke operator: ' . $originalText,
        ]);

        $operatorName = $operator?->name ?? 'Dewi Lestari';
        $handoffReply = "Halo Bapak/Ibu {$citizenName},\n\nPesan Anda telah diteruskan ke *Petugas Pelayanan Pekon Margodadi* ({$operatorName}). Petugas kami akan segera membalas percakapan Anda langsung di nomor WhatsApp ini.\n\n_Balai Pekon melayani Senin–Kamis (08.00–16.00 WIB) & Jumat (08.00–16.30 WIB)._";

        $this->wahaService->sendText($chatId, $handoffReply);

        Message::create([
            'conversation_id' => $conversation->conversation_id,
            'sender_type' => SenderType::OPERATOR,
            'operator_id' => $operator?->operator_id,
            'content' => $handoffReply,
        ]);

        return response()->json([
            'status' => 'success',
            'mode' => 'OPERATOR',
            'escalated' => true,
            'assigned_operator' => $operatorName,
        ]);
    }
}
