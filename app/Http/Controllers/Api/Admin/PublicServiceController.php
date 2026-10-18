<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\ChannelType;
use App\Enums\ServiceDomain;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\PublicService;
use App\Models\ServiceCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PublicServiceController extends Controller
{
    /**
     * Get paginated list of public services with search, category, and status filters.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 10);
        $perPage = max(1, min($perPage, 100));

        $query = PublicService::with('category');

        // Search term
        if ($search = $request->input('search')) {
            $term = '%' . trim(strtolower($search)) . '%';
            $query->where(function ($q) use ($term) {
                $q->whereRaw('LOWER(title) LIKE ?', [$term])
                    ->orWhereRaw('LOWER(description) LIKE ?', [$term])
                    ->orWhereRaw('LOWER(legal_basis) LIKE ?', [$term])
                    ->orWhereRaw('LOWER(officer_in_charge) LIKE ?', [$term])
                    ->orWhereRaw('LOWER(category_badge) LIKE ?', [$term])
                    ->orWhereRaw('LOWER(sub_category_badge) LIKE ?', [$term]);
            });
        }

        // Category filter
        if ($categoryId = $request->input('category_id')) {
            if ($categoryId !== 'all') {
                $query->where('category_id', (int) $categoryId);
            }
        }

        // Status filter (all, active, inactive)
        $status = $request->input('status', 'all');
        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        // Sorting
        $sort = strtolower((string) $request->input('sort', 'latest'));
        match ($sort) {
            'oldest' => $query->orderBy('service_id', 'asc'),
            'title_asc' => $query->orderBy('title', 'asc'),
            'title_desc' => $query->orderBy('title', 'desc'),
            default => $query->orderBy('service_id', 'desc'),
        };

        $paginated = $query->paginate($perPage);

        $formatted = collect($paginated->items())->map(fn (PublicService $s) => $this->formatServiceRow($s));

        return response()->json([
            'status' => 'success',
            'data' => $formatted,
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
            'stats' => [
                'total' => PublicService::count(),
                'active' => PublicService::where('is_active', true)->count(),
                'inactive' => PublicService::where('is_active', false)->count(),
            ],
        ]);
    }

    /**
     * Get detail of a specific public service.
     */
    public function show(string $idOrSlug): JsonResponse
    {
        $service = PublicService::with('category')
            ->where(function ($q) use ($idOrSlug) {
                $q->where('slug', $idOrSlug);
                if (is_numeric($idOrSlug)) {
                    $q->orWhere('service_id', (int) $idOrSlug);
                }
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
            'data' => $this->formatServiceDetail($service),
        ]);
    }

    /**
     * Create a new public service.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:200',
            'slug' => 'nullable|string|max:200|unique:public_services,slug',
            'category_id' => 'required|exists:service_categories,category_id',
            'category_badge' => 'nullable|string|max:50',
            'sub_category_badge' => 'nullable|string|max:100',
            'description' => 'required|string',
            'legal_basis' => 'nullable|string',
            'requirements' => 'nullable|array',
            'steps' => 'nullable|array',
            'sla_duration' => 'nullable|string|max:100',
            'cost_info' => 'nullable|string|max:100',
            'officer_in_charge' => 'nullable|string|max:150',
            'download_url' => 'nullable|string',
            'tags' => 'nullable|array',
            'is_active' => 'nullable|boolean',
        ]);

        if (empty($validated['slug'])) {
            $baseSlug = Str::slug($validated['title']);
            $slug = $baseSlug;
            $counter = 1;
            while (PublicService::where('slug', $slug)->exists()) {
                $slug = $baseSlug . '-' . $counter;
                $counter++;
            }
            $validated['slug'] = $slug;
        }

        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['requirements'] = $request->input('requirements', []);
        $validated['steps'] = $request->input('steps', []);
        $validated['tags'] = $request->input('tags', []);

        $service = PublicService::create($validated);
        $service->load('category');

        $this->logActivity(
            action: 'CREATE_PUBLIC_SERVICE',
            target: "Service #{$service->service_id}",
            description: "Menambahkan layanan publik baru: {$service->title}"
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Layanan publik berhasil ditambahkan.',
            'data' => $this->formatServiceDetail($service),
        ], 201);
    }

    /**
     * Update an existing public service.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $service = PublicService::find($id);

        if (! $service) {
            return response()->json([
                'status' => 'error',
                'message' => 'Layanan publik tidak ditemukan.',
            ], 404);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:200',
            'slug' => [
                'nullable',
                'string',
                'max:200',
                Rule::unique('public_services', 'slug')->ignore($service->service_id, 'service_id'),
            ],
            'category_id' => 'required|exists:service_categories,category_id',
            'category_badge' => 'nullable|string|max:50',
            'sub_category_badge' => 'nullable|string|max:100',
            'description' => 'required|string',
            'legal_basis' => 'nullable|string',
            'requirements' => 'nullable|array',
            'steps' => 'nullable|array',
            'sla_duration' => 'nullable|string|max:100',
            'cost_info' => 'nullable|string|max:100',
            'officer_in_charge' => 'nullable|string|max:150',
            'download_url' => 'nullable|string',
            'tags' => 'nullable|array',
            'is_active' => 'nullable|boolean',
        ]);

        if (empty($validated['slug'])) {
            $baseSlug = Str::slug($validated['title']);
            $slug = $baseSlug;
            $counter = 1;
            while (PublicService::where('slug', $slug)->where('service_id', '!=', $service->service_id)->exists()) {
                $slug = $baseSlug . '-' . $counter;
                $counter++;
            }
            $validated['slug'] = $slug;
        }

        if ($request->has('is_active')) {
            $validated['is_active'] = $request->boolean('is_active');
        }
        if ($request->has('requirements')) {
            $validated['requirements'] = $request->input('requirements', []);
        }
        if ($request->has('steps')) {
            $validated['steps'] = $request->input('steps', []);
        }
        if ($request->has('tags')) {
            $validated['tags'] = $request->input('tags', []);
        }

        $service->update($validated);
        $service->load('category');

        $this->logActivity(
            action: 'UPDATE_PUBLIC_SERVICE',
            target: "Service #{$service->service_id}",
            description: "Memperbarui layanan publik: {$service->title}"
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Layanan publik berhasil diperbarui.',
            'data' => $this->formatServiceDetail($service),
        ]);
    }

    /**
     * Delete a public service.
     */
    public function destroy(int $id): JsonResponse
    {
        $service = PublicService::find($id);

        if (! $service) {
            return response()->json([
                'status' => 'error',
                'message' => 'Layanan publik tidak ditemukan.',
            ], 404);
        }

        $title = $service->title;
        $serviceId = $service->service_id;
        $service->delete();

        $this->logActivity(
            action: 'DELETE_PUBLIC_SERVICE',
            target: "Service #{$serviceId}",
            description: "Menghapus layanan publik: {$title}"
        );

        return response()->json([
            'status' => 'success',
            'message' => "Layanan publik '{$title}' berhasil dihapus.",
        ]);
    }

    /**
     * Toggle or update active status of a public service.
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $service = PublicService::find($id);

        if (! $service) {
            return response()->json([
                'status' => 'error',
                'message' => 'Layanan publik tidak ditemukan.',
            ], 404);
        }

        $newStatus = $request->has('is_active')
            ? $request->boolean('is_active')
            : ! $service->is_active;

        $service->update(['is_active' => $newStatus]);

        $statusText = $newStatus ? 'diaktifkan' : 'dinonaktifkan';

        $this->logActivity(
            action: 'TOGGLE_PUBLIC_SERVICE_STATUS',
            target: "Service #{$service->service_id}",
            description: "Status layanan '{$service->title}' {$statusText}"
        );

        return response()->json([
            'status' => 'success',
            'message' => "Layanan publik berhasil {$statusText}.",
            'data' => [
                'service_id' => $service->service_id,
                'is_active' => $service->is_active,
            ],
        ]);
    }

    /**
     * Get categories list for public services.
     */
    public function categories(): JsonResponse
    {
        $categories = ServiceCategory::query()
            ->domain(ServiceDomain::PUBLIC_SERVICE)
            ->withCount(['publicServices'])
            ->orderBy('category_id', 'asc')
            ->get()
            ->map(fn (ServiceCategory $cat) => [
                'category_id' => $cat->category_id,
                'name' => $cat->name,
                'slug' => $cat->slug,
                'icon' => $this->normalizeIcon($cat->icon),
                'description' => $cat->description,
                'is_active' => $cat->is_active,
                'services_count' => (int) $cat->public_services_count,
            ]);

        return response()->json([
            'status' => 'success',
            'data' => $categories,
        ]);
    }

    /**
     * Create a new category for public services.
     */
    public function storeCategory(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'slug' => 'nullable|string|max:100|unique:service_categories,slug',
            'icon' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        if (empty($validated['slug'])) {
            $baseSlug = Str::slug($validated['name']);
            $slug = $baseSlug;
            $counter = 1;
            while (ServiceCategory::where('slug', $slug)->exists()) {
                $slug = $baseSlug . '-' . $counter;
                $counter++;
            }
            $validated['slug'] = $slug;
        }

        $validated['domain'] = ServiceDomain::PUBLIC_SERVICE;
        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['icon'] = $validated['icon'] ?: 'description';

        $category = ServiceCategory::create($validated);

        $this->logActivity(
            action: 'CREATE_SERVICE_CATEGORY',
            target: "Category #{$category->category_id}",
            description: "Menambahkan kategori layanan publik: {$category->name}"
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Kategori layanan berhasil ditambahkan.',
            'data' => $category,
        ], 201);
    }

    /**
     * Update an existing category for public services.
     */
    public function updateCategory(Request $request, int $id): JsonResponse
    {
        $category = ServiceCategory::where('domain', ServiceDomain::PUBLIC_SERVICE)
            ->where('category_id', $id)
            ->first();

        if (! $category) {
            return response()->json([
                'status' => 'error',
                'message' => 'Kategori layanan publik tidak ditemukan.',
            ], 404);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'slug' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('service_categories', 'slug')->ignore($category->category_id, 'category_id'),
            ],
            'icon' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        if (empty($validated['slug'])) {
            $baseSlug = Str::slug($validated['name']);
            $slug = $baseSlug;
            $counter = 1;
            while (ServiceCategory::where('slug', $slug)->where('category_id', '!=', $category->category_id)->exists()) {
                $slug = $baseSlug . '-' . $counter;
                $counter++;
            }
            $validated['slug'] = $slug;
        }

        if ($request->has('is_active')) {
            $validated['is_active'] = $request->boolean('is_active');
        }

        $category->update($validated);

        $this->logActivity(
            action: 'UPDATE_SERVICE_CATEGORY',
            target: "Category #{$category->category_id}",
            description: "Memperbarui kategori layanan publik: {$category->name}"
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Kategori layanan berhasil diperbarui.',
            'data' => $category,
        ]);
    }

    /**
     * Delete a category.
     */
    public function destroyCategory(int $id): JsonResponse
    {
        $category = ServiceCategory::where('domain', ServiceDomain::PUBLIC_SERVICE)
            ->where('category_id', $id)
            ->first();

        if (! $category) {
            return response()->json([
                'status' => 'error',
                'message' => 'Kategori layanan publik tidak ditemukan.',
            ], 404);
        }

        $name = $category->name;

        // Set category_id to null for associated services before deletion
        PublicService::where('category_id', $category->category_id)->update(['category_id' => null]);

        $category->delete();

        $this->logActivity(
            action: 'DELETE_SERVICE_CATEGORY',
            target: "Category #{$id}",
            description: "Menghapus kategori layanan publik: {$name}"
        );

        return response()->json([
            'status' => 'success',
            'message' => "Kategori '{$name}' berhasil dihapus.",
        ]);
    }

    /**
     * Format a public service row for listing.
     */
    protected function formatServiceRow(PublicService $s): array
    {
        return [
            'service_id' => $s->service_id,
            'id' => $s->slug ?: (string) $s->service_id,
            'title' => $s->title,
            'slug' => $s->slug,
            'category_id' => $s->category_id,
            'category' => $s->category ? [
                'category_id' => $s->category->category_id,
                'name' => $s->category->name,
                'slug' => $s->category->slug,
                'icon' => $this->normalizeIcon($s->category->icon),
            ] : null,
            'category_badge' => $s->category_badge ?: 'PELAYANAN',
            'sub_category_badge' => $s->sub_category_badge,
            'description' => $s->description,
            'requirements_count' => is_array($s->requirements) ? count($s->requirements) : 0,
            'steps_count' => is_array($s->steps) ? count($s->steps) : 0,
            'sla_duration' => $s->sla_duration ?: '1 Hari Kerja',
            'cost_info' => $s->cost_info ?: 'Gratis (Rp 0)',
            'officer_in_charge' => $s->officer_in_charge ?: 'Loket Pelayanan Pekon',
            'download_url' => $s->download_url,
            'is_active' => (bool) $s->is_active,
            'created_at' => $s->created_at?->toISOString(),
            'updated_at' => $s->updated_at?->toISOString(),
        ];
    }

    /**
     * Format a public service detail.
     */
    protected function formatServiceDetail(PublicService $s): array
    {
        return [
            'service_id' => $s->service_id,
            'id' => $s->slug ?: (string) $s->service_id,
            'title' => $s->title,
            'slug' => $s->slug,
            'category_id' => $s->category_id,
            'category' => $s->category ? [
                'category_id' => $s->category->category_id,
                'name' => $s->category->name,
                'slug' => $s->category->slug,
                'icon' => $this->normalizeIcon($s->category->icon),
            ] : null,
            'category_badge' => $s->category_badge ?: 'PELAYANAN',
            'sub_category_badge' => $s->sub_category_badge,
            'description' => $s->description,
            'legal_basis' => $s->legal_basis,
            'requirements' => is_array($s->requirements) ? $s->requirements : [],
            'steps' => is_array($s->steps) ? $s->steps : [],
            'sla_duration' => $s->sla_duration ?: '1 Hari Kerja',
            'cost_info' => $s->cost_info ?: 'Gratis (Rp 0)',
            'officer_in_charge' => $s->officer_in_charge ?: 'Loket Pelayanan Pekon',
            'download_url' => $s->download_url,
            'tags' => is_array($s->tags) ? $s->tags : [],
            'is_active' => (bool) $s->is_active,
            'created_at' => $s->created_at?->toISOString(),
            'updated_at' => $s->updated_at?->toISOString(),
        ];
    }

    /**
     * Normalize icon name to standard Google Material Symbols.
     */
    protected function normalizeIcon(?string $icon): string
    {
        $map = [
            'id-card' => 'badge',
            'id_card' => 'badge',
            'users' => 'groups',
            'user' => 'person',
            'store' => 'storefront',
        ];

        return $map[$icon] ?? ($icon ?: 'description');
    }

    /**
     * Record an activity log entry.
     */
    protected function logActivity(string $action, string $target, string $description): void
    {
        try {
            $operator = request()->user();
            ActivityLog::create([
                'operator_id' => $operator?->operator_id,
                'actor_name' => $operator?->name ?? 'Admin Pekon',
                'action' => $action,
                'target' => $target,
                'description' => $description,
                'channel' => ChannelType::PORTAL_WEB,
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // Fail gracefully
        }
    }
}
