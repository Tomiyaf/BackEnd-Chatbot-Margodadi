<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\ServiceCategory;
use App\Models\Umkm;
use App\Services\RagGatewayService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ChatbotUmkmConversationTest extends TestCase
{
    use RefreshDatabase;

    public function test_web_messages_forward_stable_distinct_session_ids_and_only_active_catalogue(): void
    {
        $category = ServiceCategory::factory()->create(['domain' => 'UMKM']);
        $published = Umkm::factory()->create(['category_id' => $category->category_id, 'name' => 'Usaha Terdaftar', 'is_active' => true]);
        Umkm::factory()->create(['category_id' => $category->category_id, 'name' => 'Usaha Tidak Aktif', 'is_active' => false]);
        Http::preventStrayRequests();
        Http::fake(['*/chat' => Http::response(['answer' => 'Daftar UMKM', 'sources' => []])]);

        $firstSession = $this->postJson('/api/public/chatbot/init')->assertOk()->json('data');
        $secondSession = $this->postJson('/api/public/chatbot/init')->assertOk()->json('data');
        foreach ([$firstSession, $firstSession, $secondSession] as $index => $session) {
            $this->postJson('/api/public/chatbot/send', [
                'session_id' => $session['session_id'],
                'message' => $index === 1 ? 'Apa saja itu?' : 'Apa saja UMKM di Pekon Margodadi?',
            ])->assertOk()->assertJsonPath('data.message.text', 'Daftar UMKM');
        }

        $sent = Http::recorded()->map(fn ($pair) => $pair[0]->data());
        $this->assertCount(3, $sent);
        $this->assertSame('web:'.$firstSession['conversation_id'], $sent[0]['session_id']);
        $this->assertSame($sent[0]['session_id'], $sent[1]['session_id']);
        $this->assertNotSame($sent[0]['session_id'], $sent[2]['session_id']);
        $this->assertSame('Apa saja itu?', $sent[1]['question']);
        foreach ($sent as $payload) {
            $this->assertSame([['id' => $published->umkm_id, 'name' => 'Usaha Terdaftar']], $payload['umkm_catalogue']);
            $this->assertSame(3, $payload['top_k']);
        }
        $this->assertSame(2, Conversation::count());
    }

    public function test_gateway_refreshes_the_catalogue_and_remains_compatible_without_a_session(): void
    {
        Http::preventStrayRequests();
        Http::fake(['*/chat' => Http::response(['answer' => 'Daftar UMKM', 'sources' => []])]);
        $gateway = app(RagGatewayService::class);
        $gateway->chat('Daftar UMKM');
        $category = ServiceCategory::factory()->create(['domain' => 'UMKM']);
        $new = Umkm::factory()->create(['category_id' => $category->category_id, 'is_active' => true]);
        $gateway->chat('Daftar UMKM');
        $sent = Http::recorded()->map(fn ($pair) => $pair[0]->data());
        $this->assertNull($sent[0]['session_id']);
        $this->assertSame([], $sent[0]['umkm_catalogue']);
        $this->assertSame($new->umkm_id, $sent[1]['umkm_catalogue'][0]['id']);
        $new->update(['is_active' => false]);
        $gateway->chat('Daftar UMKM');
        Http::assertSent(fn (Request $request) => $request['umkm_catalogue'] === []);
        $this->assertSame([], Http::recorded()->last()[0]->data()['umkm_catalogue']);
    }
}
