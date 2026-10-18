<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class WahaService
{
    protected string $baseUrl;
    protected string $defaultSession;
    protected ?string $apiKey;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('services.waha.base_url', 'http://127.0.0.1:3001'), '/');
        $this->defaultSession = (string) (env('WAHA_SESSION') ?: config('services.waha.session', 'default'));
        $this->apiKey = config('services.waha.api_key');
    }

    /**
     * Resolve the active session name.
     * Uses explicit session if provided, or env/config if not 'default',
     * or queries WAHA directly for the first WORKING session.
     */
    public function resolveSession(?string $session = null): string
    {
        if (! empty($session) && $session !== 'default') {
            return $session;
        }

        // Direct env read (survives process cache)
        $envSession = env('WAHA_SESSION');
        if (! empty($envSession) && $envSession !== 'default') {
            return $envSession;
        }

        $configSession = (string) config('services.waha.session');
        if (! empty($configSession) && $configSession !== 'default') {
            return $configSession;
        }

        // Auto-discover active session from WAHA
        try {
            $client = Http::timeout(3)->acceptJson();
            if (! empty($this->apiKey)) {
                $client = $client->withHeaders(['X-Api-Key' => $this->apiKey]);
            }
            $response = $client->get("{$this->baseUrl}/api/sessions");
            if ($response->successful()) {
                $sessions = $response->json();
                if (is_array($sessions)) {
                    foreach ($sessions as $s) {
                        if (($s['status'] ?? '') === 'WORKING') {
                            $this->defaultSession = $s['name'];
                            return $s['name'];
                        }
                    }
                    if (! empty($sessions[0]['name'])) {
                        $this->defaultSession = $sessions[0]['name'];
                        return $sessions[0]['name'];
                    }
                }
            }
        } catch (Throwable) {
            // ignore
        }

        return $this->defaultSession ?: 'default';
    }

    /**
     * Send a text message to WhatsApp via WAHA API.
     *
     * @param string $chatId e.g. "628123456789@c.us" or "170926947758297@lid"
     * @param string $text Message content
     * @param string|null $session Optional WAHA session name
     * @return array|null Response data or null on failure
     */
    public function sendText(string $chatId, string $text, ?string $session = null): ?array
    {
        $targetSession = $this->resolveSession($session);
        $url = "{$this->baseUrl}/api/sendText";

        $payload = [
            'chatId' => $chatId,
            'text' => $text,
            'session' => $targetSession,
        ];

        try {
            $client = Http::timeout(15)->acceptJson();

            if (! empty($this->apiKey)) {
                $client = $client->withHeaders(['X-Api-Key' => $this->apiKey]);
            }

            $response = $client->post($url, $payload);

            // If session failed with 422, try auto-resolving from WAHA once
            if (! $response->successful() && $response->status() === 422 && str_contains($response->body(), 'does not exist')) {
                Log::warning('WAHA sendText session not found, attempting auto-discovery...', [
                    'failedSession' => $targetSession,
                ]);

                $discoveredSession = $this->resolveSession('force_discovery');
                if (! empty($discoveredSession) && $discoveredSession !== $targetSession) {
                    $payload['session'] = $discoveredSession;
                    $response = $client->post($url, $payload);
                    $targetSession = $discoveredSession;
                }
            }

            if ($response->successful()) {
                Log::info('WAHA message sent successfully', [
                    'chatId' => $chatId,
                    'session' => $targetSession,
                ]);
                return $response->json();
            }

            Log::error('WAHA sendText returned error', [
                'status' => $response->status(),
                'body' => $response->body(),
                'chatId' => $chatId,
            ]);

            return null;
        } catch (Throwable $e) {
            Log::error('WAHA sendText request failed with exception', [
                'error' => $e->getMessage(),
                'chatId' => $chatId,
            ]);

            return null;
        }
    }

    /**
     * Start typing indicator in WhatsApp chat.
     */
    public function startTyping(string $chatId, ?string $session = null): bool
    {
        return $this->sendAction('startTyping', $chatId, $session);
    }

    /**
     * Stop typing indicator in WhatsApp chat.
     */
    public function stopTyping(string $chatId, ?string $session = null): bool
    {
        return $this->sendAction('stopTyping', $chatId, $session);
    }

    /**
     * Mark message as seen / read receipt.
     */
    public function sendSeen(string $chatId, ?string $messageId = null, ?string $session = null): bool
    {
        $targetSession = $this->resolveSession($session);
        $url = "{$this->baseUrl}/api/sendSeen";

        $payload = [
            'chatId' => $chatId,
            'session' => $targetSession,
        ];

        if ($messageId) {
            $payload['messageId'] = $messageId;
        }

        try {
            $client = Http::timeout(5)->acceptJson();
            if (! empty($this->apiKey)) {
                $client = $client->withHeaders(['X-Api-Key' => $this->apiKey]);
            }

            return $client->post($url, $payload)->successful();
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Check if WAHA service is accessible.
     */
    public function isOnline(): bool
    {
        try {
            $client = Http::timeout(3)->acceptJson();
            if (! empty($this->apiKey)) {
                $client = $client->withHeaders(['X-Api-Key' => $this->apiKey]);
            }

            $response = $client->get("{$this->baseUrl}/api/server/version");
            return $response->successful();
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Get details of a specific WhatsApp session.
     */
    public function getSessionStatus(?string $session = null): ?array
    {
        $targetSession = $this->resolveSession($session);
        $url = "{$this->baseUrl}/api/sessions/{$targetSession}";

        try {
            $client = Http::timeout(5)->acceptJson();
            if (! empty($this->apiKey)) {
                $client = $client->withHeaders(['X-Api-Key' => $this->apiKey]);
            }

            $response = $client->get($url);
            return $response->successful() ? $response->json() : null;
        } catch (Throwable $e) {
            Log::warning('Failed to get WAHA session status', [
                'session' => $targetSession,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Helper to send presence action.
     */
    protected function sendAction(string $endpoint, string $chatId, ?string $session = null): bool
    {
        $targetSession = $this->resolveSession($session);
        $url = "{$this->baseUrl}/api/{$endpoint}";

        try {
            $client = Http::timeout(5)->acceptJson();
            if (! empty($this->apiKey)) {
                $client = $client->withHeaders(['X-Api-Key' => $this->apiKey]);
            }

            return $client->post($url, [
                'chatId' => $chatId,
                'session' => $targetSession,
            ])->successful();
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Get active WhatsApp Center phone number and profile info.
     * Tries querying WAHA session /me or /api/sessions.
     * Falls back to PEKON_WHATSAPP_NUMBER when WAHA is unavailable or not logged in.
     */
    public function getWhatsAppCenterInfo(): array
    {
        $fallbackNumber = (string) config('services.waha.fallback_phone', env('PEKON_WHATSAPP_NUMBER', '6285185222303'));
        $cleanFallback = $this->sanitizePhoneNumber($fallbackNumber);

        // Cache for 2 minutes to minimize WAHA requests while remaining reactive
        return Cache::remember('waha_whatsapp_center_info', 120, function () use ($cleanFallback) {
            try {
                $session = $this->resolveSession();
                $client = Http::timeout(3)->acceptJson();
                if (! empty($this->apiKey)) {
                    $client = $client->withHeaders(['X-Api-Key' => $this->apiKey]);
                }

                // 1. Try GET /api/sessions/{session}/me
                $response = $client->get("{$this->baseUrl}/api/sessions/{$session}/me");
                if ($response->successful() && ! empty($response->json('id'))) {
                    $rawId = (string) $response->json('id');
                    $phone = $this->sanitizePhoneNumber($rawId);
                    if (! empty($phone)) {
                        return $this->buildWhatsAppCenterPayload(
                            phone: $phone,
                            pushName: (string) ($response->json('pushName') ?: 'Pekon Margodadi'),
                            session: $session,
                            isOnline: true,
                            source: 'waha'
                        );
                    }
                }

                // 2. Try GET /api/sessions and search for any WORKING session with me info
                $sessionsResponse = $client->get("{$this->baseUrl}/api/sessions");
                if ($sessionsResponse->successful() && is_array($sessionsResponse->json())) {
                    foreach ($sessionsResponse->json() as $s) {
                        if (($s['status'] ?? '') === 'WORKING' && ! empty($s['me']['id'])) {
                            $phone = $this->sanitizePhoneNumber((string) $s['me']['id']);
                            if (! empty($phone)) {
                                return $this->buildWhatsAppCenterPayload(
                                    phone: $phone,
                                    pushName: (string) ($s['me']['pushName'] ?? 'Pekon Margodadi'),
                                    session: (string) ($s['name'] ?? $session),
                                    isOnline: true,
                                    source: 'waha'
                                );
                            }
                        }
                    }
                }
            } catch (Throwable $e) {
                Log::debug('WAHA getWhatsAppCenterInfo fallback triggered', ['error' => $e->getMessage()]);
            }

            return $this->buildWhatsAppCenterPayload(
                phone: $cleanFallback ?: '6285185222303',
                pushName: 'Pekon Margodadi',
                session: null,
                isOnline: false,
                source: 'fallback'
            );
        });
    }

    /**
     * Sanitize phone number from WAHA JID/wid or input string into clean digits.
     */
    public function sanitizePhoneNumber(string $raw): string
    {
        // Extract portion before '@'
        $beforeAt = explode('@', $raw)[0] ?? $raw;
        // Extract portion before ':' (device identifier)
        $beforeColon = explode(':', $beforeAt)[0] ?? $beforeAt;
        // Keep only numbers
        $digits = preg_replace('/[^0-9]/', '', $beforeColon);

        // Normalize 08... to 628...
        if (str_starts_with($digits, '08')) {
            $digits = '628' . substr($digits, 2);
        }

        return $digits;
    }

    /**
     * Build standard payload for WhatsApp Center.
     */
    protected function buildWhatsAppCenterPayload(
        string $phone,
        string $pushName,
        ?string $session,
        bool $isOnline,
        string $source
    ): array {
        $display = $phone;
        if (str_starts_with($phone, '62') && strlen($phone) >= 11) {
            // e.g. +62 851-8522-2303
            $display = '+62 ' . substr($phone, 2, 3) . '-' . substr($phone, 5, 4) . '-' . substr($phone, 9);
        }

        $defaultGreeting = 'Halo Admin Pekon Margodadi, saya ingin bertanya terkait layanan pekon.';
        $waLink = "https://wa.me/{$phone}?text=" . rawurlencode($defaultGreeting);

        return [
            'phone_number' => $phone,
            'display_number' => $display,
            'wa_link' => $waLink,
            'push_name' => $pushName,
            'session' => $session,
            'is_online' => $isOnline,
            'source' => $source,
        ];
    }
}

