<?php

namespace App\Services;

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
}
