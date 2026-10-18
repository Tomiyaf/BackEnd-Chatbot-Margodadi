<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\PublicService;
use App\Models\ServiceCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicServiceController extends Controller
{
    /**
     * Get list of public services with search, category filtering, and category counters.
     */
    public function index(Request $request): JsonResponse
    {
        $search = $request->query('search');
        $categorySlug = $request->query('category');

        $query = PublicService::with('category')->where('is_active', true);

        if ($search) {
            $searchLower = '%'.strtolower($search).'%';
            $query->where(function ($q) use ($searchLower) {
                $q->whereRaw('LOWER(title) LIKE ?', [$searchLower])
                    ->orWhereRaw('LOWER(description) LIKE ?', [$searchLower])
                    ->orWhereRaw('LOWER(legal_basis) LIKE ?', [$searchLower]);
            });
        }

        if ($categorySlug && $categorySlug !== 'all') {
            $query->where(function ($q) use ($categorySlug) {
                $q->whereHas('category', function ($cq) use ($categorySlug) {
                    $cq->where('slug', $categorySlug);
                })
                    ->orWhereRaw('LOWER(category_badge) LIKE ?', ['%'.strtolower($categorySlug).'%'])
                    ->orWhere('slug', $categorySlug);
            });
        }

        $services = $query->orderBy('service_id', 'asc')->get()->map(function ($s) {
            return [
                'id' => $s->slug ?: (string) $s->service_id,
                'service_id' => $s->service_id,
                'category' => $s->category?->slug ?: 'surat',
                'categoryBadge' => $s->category_badge ?: 'PELAYANAN',
                'subCategoryBadge' => $s->sub_category_badge,
                'verified' => true,
                'title' => $s->title,
                'slug' => $s->slug,
                'tags' => $s->tags ?: [],
                'duration' => $s->sla_duration ?: '1 Hari Kerja',
                'cost' => $s->cost_info ?: 'Gratis (Rp 0)',
                'description' => $s->description,
                'legalBasis' => $s->legal_basis,
                'requirements' => $s->requirements ?: [],
                'steps' => $s->steps ?: [],
                'officer' => $s->officer_in_charge ?: 'Loket Pelayanan Pekon',
                'downloadUrl' => $s->download_url,
            ];
        });

        // Category summary list with dynamic counts from database
        $dbCategories = ServiceCategory::query()
            ->active()
            ->domain(\App\Enums\ServiceDomain::PUBLIC_SERVICE)
            ->whereHas('publicServices', fn ($q) => $q->active())
            ->withCount(['publicServices' => fn ($q) => $q->active()])
            ->orderBy('category_id', 'asc')
            ->get();

        $categories = [
            [
                'id' => 'all',
                'name' => 'Semua Layanan Publik',
                'icon' => 'category',
                'count' => PublicService::where('is_active', true)->count(),
            ],
        ];

        $iconMap = [
            'id-card' => 'badge',
            'users' => 'groups',
            'user' => 'person',
            'store' => 'storefront',
        ];

        foreach ($dbCategories as $cat) {
            $rawIcon = $cat->icon ?: 'description';
            $categories[] = [
                'id' => $cat->slug,
                'name' => $cat->name,
                'icon' => $iconMap[$rawIcon] ?? $rawIcon,
                'count' => (int) $cat->public_services_count,
            ];
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'services' => $services,
                'categories' => $categories,
            ],
        ]);
    }

    /**
     * Get detail of a specific public service by ID or slug.
     */
    public function show(string $idOrSlug): JsonResponse
    {
        $service = PublicService::with('category')
            ->where('is_active', true)
            ->where(function ($q) use ($idOrSlug) {
                $q->where('slug', $idOrSlug)
                    ->orWhere('service_id', is_numeric($idOrSlug) ? (int) $idOrSlug : 0);
            })
            ->first();

        if (! $service) {
            return response()->json([
                'status' => 'error',
                'message' => 'Layanan publik tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $service->slug ?: (string) $service->service_id,
                'service_id' => $service->service_id,
                'category' => $service->category?->slug ?: 'surat',
                'categoryBadge' => $service->category_badge ?: 'PELAYANAN',
                'subCategoryBadge' => $service->sub_category_badge,
                'verified' => true,
                'title' => $service->title,
                'slug' => $service->slug,
                'tags' => $service->tags ?: [],
                'duration' => $service->sla_duration ?: '1 Hari Kerja',
                'cost' => $service->cost_info ?: 'Gratis (Rp 0)',
                'description' => $service->description,
                'legalBasis' => $service->legal_basis,
                'requirements' => $service->requirements ?: [],
                'steps' => $service->steps ?: [],
                'officer' => $service->officer_in_charge ?: 'Loket Pelayanan Pekon',
                'downloadUrl' => $service->download_url,
            ],
        ]);
    }
}
