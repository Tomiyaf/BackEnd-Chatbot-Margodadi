<?php

namespace Tests\Feature;

use App\Enums\OperatorRole;
use App\Enums\OperatorStatus;
use App\Enums\ServiceDomain;
use App\Models\Operator;
use App\Models\ServiceCategory;
use App\Models\Umkm;
use App\Models\UmkmProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminUmkmApiTest extends TestCase
{
    use RefreshDatabase;

    private Operator $admin;
    private Operator $operator;
    private ServiceCategory $category;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        $this->admin = Operator::factory()->create([
            'role' => OperatorRole::ADMIN,
            'status' => OperatorStatus::ONLINE,
            'is_active' => true,
        ]);

        $this->operator = Operator::factory()->create([
            'role' => OperatorRole::OPERATOR,
            'status' => OperatorStatus::ONLINE,
            'is_active' => true,
        ]);

        $this->category = ServiceCategory::factory()->create([
            'name' => 'Kuliner & Olahan Pangan',
            'slug' => 'kuliner-olahan',
            'domain' => ServiceDomain::UMKM,
            'is_active' => true,
        ]);
    }

    public function test_unauthenticated_cannot_access_admin_umkms(): void
    {
        $response = $this->getJson('/api/admin/umkms');
        $response->assertStatus(401);
    }

    public function test_admin_and_operator_can_list_umkms(): void
    {
        Umkm::factory()->count(5)->create([
            'category_id' => $this->category->category_id,
            'is_active' => true,
        ]);

        // Test with ADMIN
        Sanctum::actingAs($this->admin);
        $resAdmin = $this->getJson('/api/admin/umkms');
        $resAdmin->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonStructure(['data', 'meta']);

        $this->assertCount(5, $resAdmin->json('data'));

        // Test with OPERATOR
        Sanctum::actingAs($this->operator);
        $resOp = $this->getJson('/api/admin/umkms');
        $resOp->assertStatus(200);
        $this->assertCount(5, $resOp->json('data'));
    }

    public function test_can_view_umkm_stats(): void
    {
        Sanctum::actingAs($this->admin);

        Umkm::factory()->create([
            'category_id' => $this->category->category_id,
            'is_active' => true,
            'last_verified_at' => now(),
        ]);

        Umkm::factory()->create([
            'category_id' => $this->category->category_id,
            'is_active' => false,
            'last_verified_at' => null,
        ]);

        $response = $this->getJson('/api/admin/umkms/stats');
        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.total_umkm', 2)
            ->assertJsonPath('data.active_umkm', 1)
            ->assertJsonPath('data.inactive_umkm', 1)
            ->assertJsonPath('data.verified_umkm', 1);
    }

    public function test_can_create_umkm_with_physical_banner_image(): void
    {
        Sanctum::actingAs($this->operator);

        $fakeBanner = UploadedFile::fake()->image('banner_toko.png', 800, 600);
        $fakeGallery1 = UploadedFile::fake()->image('produk1.jpg', 600, 600);

        $payload = [
            'name' => 'Kopi Robusta Lereng Margodadi',
            'sub_title' => 'Kopi Asli Tanggamus Petik Merah',
            'category_id' => $this->category->category_id,
            'owner_name' => 'Pak Supardi',
            'phone' => '081278901234',
            'wa_number' => '6281278901234',
            'address' => 'Dusun 02 RT 04, Pekon Margodadi',
            'description' => 'Biji kopi pilihan diproses dengan standar mutu.',
            'history' => 'Dirintis sejak tahun 2017.',
            'is_active' => 1,
            'banner_image' => $fakeBanner,
            'gallery_images' => [$fakeGallery1],
            'products' => json_encode([
                ['name' => 'Kopi Bubuk 250gr', 'price' => '35000', 'description' => 'Aroma cokelat karamel'],
                ['name' => 'Roasted Beans 500gr', 'price' => '65000', 'description' => 'Medium dark roast'],
            ]),
        ];

        $response = $this->postJson('/api/admin/umkms', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.name', 'Kopi Robusta Lereng Margodadi')
            ->assertJsonPath('data.owner_name', 'Pak Supardi');

        $this->assertDatabaseHas('umkms', [
            'name' => 'Kopi Robusta Lereng Margodadi',
            'owner_name' => 'Pak Supardi',
        ]);

        $this->assertDatabaseHas('umkm_products', [
            'name' => 'Kopi Bubuk 250gr',
        ]);

        $this->assertDatabaseHas('umkm_products', [
            'name' => 'Roasted Beans 500gr',
        ]);
    }

    public function test_can_update_umkm_and_toggle_status(): void
    {
        Sanctum::actingAs($this->admin);

        $umkm = Umkm::factory()->create([
            'category_id' => $this->category->category_id,
            'name' => 'Nama Usaha Lama',
            'is_active' => true,
        ]);

        // Update name
        $updateRes = $this->postJson("/api/admin/umkms/{$umkm->umkm_id}", [
            'category_id' => $this->category->category_id,
            'name' => 'Nama Usaha Baru',
            'owner_name' => 'Pemilik Baru',
        ]);

        $updateRes->assertStatus(200)
            ->assertJsonPath('data.name', 'Nama Usaha Baru');

        $this->assertDatabaseHas('umkms', [
            'umkm_id' => $umkm->umkm_id,
            'name' => 'Nama Usaha Baru',
        ]);

        // Toggle status
        $statusRes = $this->patchJson("/api/admin/umkms/{$umkm->umkm_id}/status", [
            'is_active' => false,
        ]);

        $statusRes->assertStatus(200)
            ->assertJsonPath('data.is_active', false);

        $this->assertDatabaseHas('umkms', [
            'umkm_id' => $umkm->umkm_id,
            'is_active' => false,
        ]);
    }

    public function test_can_verify_umkm(): void
    {
        Sanctum::actingAs($this->admin);

        $umkm = Umkm::factory()->create([
            'category_id' => $this->category->category_id,
            'last_verified_at' => null,
        ]);

        $response = $this->patchJson("/api/admin/umkms/{$umkm->umkm_id}/verify");

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success');

        $this->assertNotNull(Umkm::find($umkm->umkm_id)->last_verified_at);
    }

    public function test_can_delete_umkm_and_cascade_products(): void
    {
        Sanctum::actingAs($this->admin);

        $umkm = Umkm::factory()->create([
            'category_id' => $this->category->category_id,
        ]);

        $product = UmkmProduct::factory()->create([
            'umkm_id' => $umkm->umkm_id,
            'name' => 'Produk Tes',
        ]);

        $response = $this->deleteJson("/api/admin/umkms/{$umkm->umkm_id}");

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success');

        $this->assertDatabaseMissing('umkms', ['umkm_id' => $umkm->umkm_id]);
        $this->assertDatabaseMissing('umkm_products', ['product_id' => $product->product_id]);
    }
}
