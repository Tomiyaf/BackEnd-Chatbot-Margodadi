<?php

namespace Tests\Feature;

use App\Enums\OperatorRole;
use App\Enums\ServiceDomain;
use App\Models\Operator;
use App\Models\PublicService;
use App\Models\ServiceCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminPublicServiceManagementTest extends TestCase
{
    use RefreshDatabase;

    protected Operator $admin;
    protected ServiceCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Operator::factory()->create([
            'role' => OperatorRole::ADMIN,
            'is_active' => true,
        ]);

        $this->category = ServiceCategory::firstOrCreate(
            ['slug' => 'administrasi-kependudukan'],
            [
                'name' => 'Administrasi & Kependudukan',
                'domain' => ServiceDomain::PUBLIC_SERVICE,
                'icon' => 'badge',
                'is_active' => true,
            ]
        );
    }

    public function test_admin_can_list_services_with_pagination_and_stats(): void
    {
        Sanctum::actingAs($this->admin);

        PublicService::create([
            'title' => 'Layanan Uji Coba Kependudukan',
            'slug' => 'layanan-uji-coba-kependudukan',
            'category_id' => $this->category->category_id,
            'description' => 'Deskripsi layanan pengujian fitur admin.',
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/admin/services');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data',
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
                'stats' => ['total', 'active', 'inactive'],
            ])
            ->assertJson(['status' => 'success']);
    }

    public function test_admin_can_create_new_public_service(): void
    {
        Sanctum::actingAs($this->admin);

        $payload = [
            'title' => 'Surat Pengantar Nikah (N1-N4)',
            'category_id' => $this->category->category_id,
            'category_badge' => 'SURAT_PENGANTAR',
            'sub_category_badge' => 'Kependudukan',
            'description' => 'Layanan permohonan surat pengantar pernikahan warga Margodadi.',
            'legal_basis' => 'PMA RI No. 20 Tahun 2019 tentang Pencatatan Pernikahan.',
            'requirements' => [
                'Fotokopi KTP dan KK calon pengantin',
                'Fotokopi Akta Kelahiran dan Ijazah terakhir',
                'Surat Pengantar RT/RW setempat',
            ],
            'steps' => [
                ['step' => 1, 'name' => 'Pendaftaran', 'desc' => 'Daftar di loket pekon'],
            ],
            'sla_duration' => '1 Hari Kerja',
            'cost_info' => 'Gratis (Rp 0)',
            'officer_in_charge' => 'Kasi Pelayanan',
            'download_url' => 'https://margodadi.desa.id/n1.pdf',
            'tags' => ['nikah', 'surat pengantar', 'kua'],
            'is_active' => true,
        ];

        $response = $this->postJson('/api/admin/services', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success',
                'message' => 'Layanan publik berhasil ditambahkan.',
            ]);

        $this->assertDatabaseHas('public_services', [
            'title' => 'Surat Pengantar Nikah (N1-N4)',
            'category_id' => $this->category->category_id,
        ]);
    }

    public function test_admin_can_update_existing_public_service(): void
    {
        Sanctum::actingAs($this->admin);

        $service = PublicService::create([
            'title' => 'Layanan Lama Sebelum Diubah',
            'slug' => 'layanan-lama-sebelum-diubah',
            'category_id' => $this->category->category_id,
            'description' => 'Deskripsi lama.',
            'is_active' => true,
        ]);

        $payload = [
            'title' => 'Layanan Baru Setelah Diubah',
            'category_id' => $this->category->category_id,
            'description' => 'Deskripsi baru yang telah diperbarui.',
            'sla_duration' => '2 Hari Kerja',
        ];

        $response = $this->putJson("/api/admin/services/{$service->service_id}", $payload);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Layanan publik berhasil diperbarui.',
            ]);

        $this->assertDatabaseHas('public_services', [
            'service_id' => $service->service_id,
            'title' => 'Layanan Baru Setelah Diubah',
        ]);
    }

    public function test_admin_can_toggle_service_active_status(): void
    {
        Sanctum::actingAs($this->admin);

        $service = PublicService::create([
            'title' => 'Layanan Toggle Status',
            'slug' => 'layanan-toggle-status',
            'category_id' => $this->category->category_id,
            'description' => 'Deskripsi test toggle.',
            'is_active' => true,
        ]);

        $response = $this->patchJson("/api/admin/services/{$service->service_id}/status", [
            'is_active' => false,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'service_id' => $service->service_id,
                    'is_active' => false,
                ],
            ]);

        $this->assertDatabaseHas('public_services', [
            'service_id' => $service->service_id,
            'is_active' => false,
        ]);
    }

    public function test_admin_can_delete_public_service(): void
    {
        Sanctum::actingAs($this->admin);

        $service = PublicService::create([
            'title' => 'Layanan Yang Akan Dihapus',
            'slug' => 'layanan-yang-akan-dihapus',
            'category_id' => $this->category->category_id,
            'description' => 'Deskripsi akan dihapus.',
            'is_active' => true,
        ]);

        $response = $this->deleteJson("/api/admin/services/{$service->service_id}");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
            ]);

        $this->assertDatabaseMissing('public_services', [
            'service_id' => $service->service_id,
        ]);
    }

    public function test_admin_can_manage_service_categories(): void
    {
        Sanctum::actingAs($this->admin);

        // 1. Create category
        $createRes = $this->postJson('/api/admin/services/categories', [
            'name' => 'Kategori Baru Percobaan',
            'icon' => 'gavel',
            'description' => 'Kategori hukum dan regulasi.',
            'is_active' => true,
        ]);

        $createRes->assertStatus(201)
            ->assertJson([
                'status' => 'success',
                'message' => 'Kategori layanan berhasil ditambahkan.',
            ]);

        $categoryId = $createRes->json('data.category_id');

        // 2. List categories
        $listRes = $this->getJson('/api/admin/services/categories');
        $listRes->assertStatus(200)
            ->assertJsonStructure(['status', 'data']);

        // 3. Update category
        $updateRes = $this->putJson("/api/admin/services/categories/{$categoryId}", [
            'name' => 'Kategori Baru Diperbarui',
            'icon' => 'balance',
            'description' => 'Deskripsi terupdate.',
        ]);

        $updateRes->assertStatus(200)
            ->assertJson(['status' => 'success']);

        // 4. Delete category
        $deleteRes = $this->deleteJson("/api/admin/services/categories/{$categoryId}");
        $deleteRes->assertStatus(200)
            ->assertJson(['status' => 'success']);

        $this->assertDatabaseMissing('service_categories', [
            'category_id' => $categoryId,
        ]);
    }
}
