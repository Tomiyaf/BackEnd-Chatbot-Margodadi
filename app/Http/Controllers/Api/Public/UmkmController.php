<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\Umkm;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UmkmController extends Controller
{
    /**
     * Get list of UMKM with categories, search, and sorting.
     */
    public function index(Request $request): JsonResponse
    {
        $search = $request->query('search');
        $category = $request->query('category');
        $sortBy = $request->query('sort', 'popular');

        $query = Umkm::with(['category', 'products'])->where('is_active', true);

        if ($search) {
            $searchLower = '%'.strtolower($search).'%';
            $query->where(function ($q) use ($searchLower) {
                $q->whereRaw('LOWER(name) LIKE ?', [$searchLower])
                    ->orWhereRaw('LOWER(owner_name) LIKE ?', [$searchLower])
                    ->orWhereRaw('LOWER(description) LIKE ?', [$searchLower])
                    ->orWhereRaw('LOWER(address) LIKE ?', [$searchLower])
                    ->orWhereHas('products', function ($pq) use ($searchLower) {
                        $pq->whereRaw('LOWER(name) LIKE ?', [$searchLower]);
                    });
            });
        }

        if ($category && $category !== 'all') {
            $categoryLower = '%'.strtolower($category).'%';
            $query->where(function ($q) use ($categoryLower) {
                $q->whereRaw('LOWER(description) LIKE ?', [$categoryLower])
                    ->orWhereRaw('LOWER(sub_title) LIKE ?', [$categoryLower])
                    ->orWhereRaw('LOWER(name) LIKE ?', [$categoryLower])
                    ->orWhereHas('category', function ($cq) use ($categoryLower) {
                        $cq->whereRaw('LOWER(name) LIKE ?', [$categoryLower]);
                    });
            });
        }

        if ($sortBy === 'az') {
            $query->orderBy('name', 'asc');
        } elseif ($sortBy === 'newest') {
            $query->orderBy('created_at', 'desc');
        } else {
            $query->orderBy('umkm_id', 'asc');
        }

        $umkms = $query->get()->map(function ($u) {
            return [
                'id' => (string) $u->umkm_id,
                'name' => $u->name,
                'subTitle' => $u->sub_title,
                'regNumber' => $u->reg_number,
                'category' => ['kuliner', 'kerajinan', 'pertanian', 'jasa'],
                'categoryBadge' => $u->category?->name ?: 'UMKM Desa',
                'owner' => $u->owner_name,
                'phone' => $u->phone,
                'waNumber' => $u->wa_number ?: preg_replace('/[^0-9]/', '', (string) $u->phone),
                'address' => $u->address,
                'description' => $u->description,
                'image' => $u->banner_image_url,
                'featuredProducts' => $u->products->map(fn ($p) => [
                    'name' => $p->name,
                    'price' => 'Rp '.number_format($p->price, 0, ',', '.'),
                    'description' => $p->description,
                ]),
                'history' => $u->history,
                'legalCertification' => $u->legal_certification,
                'legalNumber' => $u->legal_number,
                'capacity' => $u->production_capacity,
                'capacityNote' => $u->capacity_note,
                'group' => $u->group_name,
                'groupLocation' => $u->group_location,
                'gallery' => $u->gallery_urls ?: [],
                'mapTitle' => $u->map_title,
                'mapAddress' => $u->map_address,
                'mapUrl' => $u->map_url,
                'lastVerified' => $u->last_verified_at ? $u->last_verified_at->format('d F Y') : 'Terverifikasi',
            ];
        });

        $categories = [
            ['id' => 'all', 'name' => 'Semua Kategori', 'count' => Umkm::where('is_active', true)->count()],
            ['id' => 'kuliner', 'name' => 'Kuliner & Olahan', 'count' => Umkm::where('is_active', true)->where(fn ($q) => $q->whereRaw("LOWER(name) LIKE '%kopi%' OR LOWER(name) LIKE '%keripik%' OR LOWER(name) LIKE '%madu%'"))->count()],
            ['id' => 'kerajinan', 'name' => 'Kerajinan & Kriya', 'count' => Umkm::where('is_active', true)->where(fn ($q) => $q->whereRaw("LOWER(name) LIKE '%bambu%' OR LOWER(name) LIKE '%batik%'"))->count()],
            ['id' => 'pertanian', 'name' => 'Pertanian & Agribisnis', 'count' => Umkm::where('is_active', true)->where(fn ($q) => $q->whereRaw("LOWER(name) LIKE '%bibit%' OR LOWER(name) LIKE '%madu%' OR LOWER(name) LIKE '%kopi%'"))->count()],
            ['id' => 'jasa', 'name' => 'Jasa & Perdagangan', 'count' => Umkm::where('is_active', true)->where(fn ($q) => $q->whereRaw("LOWER(name) LIKE '%bengkel%'"))->count()],
        ];

        return response()->json([
            'status' => 'success',
            'data' => [
                'umkms' => $umkms,
                'categories' => $categories,
            ],
        ]);
    }

    /**
     * Get detail of a specific UMKM by ID.
     */
    public function show(int $id): JsonResponse
    {
        $u = Umkm::with(['category', 'products'])->where('is_active', true)->find($id);

        if (! $u) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data UMKM tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => (string) $u->umkm_id,
                'name' => $u->name,
                'subTitle' => $u->sub_title,
                'regNumber' => $u->reg_number,
                'category' => ['kuliner', 'kerajinan', 'pertanian', 'jasa'],
                'categoryBadge' => $u->category?->name ?: 'UMKM Desa',
                'owner' => $u->owner_name,
                'phone' => $u->phone,
                'waNumber' => $u->wa_number ?: preg_replace('/[^0-9]/', '', (string) $u->phone),
                'address' => $u->address,
                'description' => $u->description,
                'image' => $u->banner_image_url,
                'featuredProducts' => $u->products->map(fn ($p) => [
                    'name' => $p->name,
                    'price' => 'Rp '.number_format($p->price, 0, ',', '.'),
                    'description' => $p->description,
                ]),
                'history' => $u->history,
                'legalCertification' => $u->legal_certification,
                'legalNumber' => $u->legal_number,
                'capacity' => $u->production_capacity,
                'capacityNote' => $u->capacity_note,
                'group' => $u->group_name,
                'groupLocation' => $u->group_location,
                'gallery' => $u->gallery_urls ?: [],
                'mapTitle' => $u->map_title,
                'mapAddress' => $u->map_address,
                'mapUrl' => $u->map_url,
                'lastVerified' => $u->last_verified_at ? $u->last_verified_at->format('d F Y') : 'Terverifikasi',
            ],
        ]);
    }
}
