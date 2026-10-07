<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\ChannelType;
use App\Enums\ServiceDomain;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\KbChunk;
use App\Models\KbDocument;
use App\Models\RagConfiguration;
use App\Services\VectorChunkingService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KnowledgeBaseController extends Controller
{
    public function __construct(
        protected VectorChunkingService $chunkingService
    ) {}

    /**
     * Get summary metrics for Knowledge Base and Vector Store.
     */
    public function stats(): JsonResponse
    {
        $totalDocs = KbDocument::count();
        $activeDocs = KbDocument::where('is_active', true)->count();
        $totalChunks = KbChunk::count();
        
        $domainBreakdown = [
            'PUBLIC_SERVICE' => KbDocument::where('domain', ServiceDomain::PUBLIC_SERVICE)->count(),
            'UMKM' => KbDocument::where('domain', ServiceDomain::UMKM)->count(),
            'WASTE_EDUCATION' => KbDocument::where('domain', ServiceDomain::WASTE_EDUCATION)->count(),
        ];

        $ragConfig = RagConfiguration::where('is_active', true)->latest('created_at')->first();

        $avgChunksPerDoc = $totalDocs > 0 ? round($totalChunks / $totalDocs, 1) : 0;
        $lastIndexed = KbChunk::latest('created_at')->value('created_at');

        return response()->json([
            'status' => 'success',
            'data' => [
                'total_documents' => $totalDocs,
                'active_documents' => $activeDocs,
                'total_chunks' => $totalChunks,
                'avg_chunks_per_doc' => $avgChunksPerDoc,
                'domain_breakdown' => $domainBreakdown,
                'embedding_model' => $ragConfig->embedding_model ?? 'text-embedding-3-small',
                'llm_model' => $ragConfig->llm_model ?? 'gpt-4o-mini',
                'chunk_size' => $ragConfig->chunking_config['size'] ?? 500,
                'chunk_overlap' => $ragConfig->chunking_config['overlap'] ?? 50,
                'last_indexed_at' => $lastIndexed ? Carbon::parse($lastIndexed)->translatedFormat('d M Y, H:i') . ' WIB' : 'Belum pernah',
            ],
        ]);
    }

    /**
     * Display a listing of knowledge documents.
     */
    public function index(Request $request): JsonResponse
    {
        $query = KbDocument::withCount('chunks')->orderBy('updated_at', 'desc');

        if ($domain = $request->query('domain')) {
            if (strtoupper($domain) !== 'ALL') {
                $query->where('domain', strtoupper($domain));
            }
        }

        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->query('is_active'), FILTER_VALIDATE_BOOLEAN));
        }

        if ($search = $request->query('search')) {
            $searchTerm = '%' . strtolower(trim($search)) . '%';
            $query->where(function ($q) use ($searchTerm) {
                $q->whereRaw('LOWER(title) LIKE ?', [$searchTerm])
                  ->orWhereRaw('LOWER(source) LIKE ?', [$searchTerm])
                  ->orWhereRaw('LOWER(validator) LIKE ?', [$searchTerm])
                  ->orWhereHas('chunks', function ($cq) use ($searchTerm) {
                      $cq->whereRaw('LOWER(content) LIKE ?', [$searchTerm]);
                  });
            });
        }

        $perPage = (int) $request->query('per_page', 10);
        $documents = $query->paginate($perPage);

        $formatted = collect($documents->items())->map(function (KbDocument $doc) {
            return [
                'id' => $doc->document_id,
                'document_id' => $doc->document_id,
                'doc_code' => 'DOC-' . str_pad($doc->document_id, 3, '0', STR_PAD_LEFT),
                'title' => $doc->title,
                'domain' => $doc->domain instanceof ServiceDomain ? $doc->domain->value : (string) $doc->domain,
                'source' => $doc->source ?? '-',
                'validator' => $doc->validator ?? 'Aparatur Pekon',
                'version' => $doc->version ?? 'v1.0',
                'is_active' => (bool) $doc->is_active,
                'chunks_count' => (int) $doc->chunks_count,
                'created_at' => $doc->created_at?->translatedFormat('d M Y, H:i') . ' WIB',
                'updated_at' => $doc->updated_at?->translatedFormat('d M Y, H:i') . ' WIB',
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => $formatted,
            'meta' => [
                'current_page' => $documents->currentPage(),
                'last_page' => $documents->lastPage(),
                'per_page' => $documents->perPage(),
                'total' => $documents->total(),
            ],
        ]);
    }

    /**
     * Store a newly created knowledge document and auto-generate vector chunks.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'domain' => ['required', 'string', 'in:PUBLIC_SERVICE,UMKM,WASTE_EDUCATION'],
            'source' => ['nullable', 'string', 'max:255'],
            'validator' => ['nullable', 'string', 'max:100'],
            'version' => ['nullable', 'string', 'max:50'],
            'is_active' => ['nullable', 'boolean'],
            'content' => ['nullable', 'string'],
            'chunks' => ['nullable', 'array'],
        ]);

        $document = KbDocument::create([
            'title' => $validated['title'],
            'domain' => ServiceDomain::from($validated['domain']),
            'source' => $validated['source'] ?? null,
            'validator' => $validated['validator'] ?? 'Aparatur Pekon',
            'version' => $validated['version'] ?? 'v1.0',
            'is_active' => $validated['is_active'] ?? true,
        ]);

        $contentToChunk = $validated['content'] ?? ($validated['chunks'] ?? []);
        $chunksCount = 0;

        if (!empty($contentToChunk)) {
            $chunksCount = $this->chunkingService->processAndSaveChunks($document, $contentToChunk);
        }

        // Activity Audit Log
        $operator = $request->user();
        ActivityLog::create([
            'operator_id' => $operator?->operator_id,
            'actor_name' => $operator?->name ?? 'Aparatur Pekon',
            'action' => 'VECTOR_DOC_CREATE',
            'target' => '#DOC-' . str_pad($document->document_id, 3, '0', STR_PAD_LEFT),
            'description' => "Menambahkan dokumen SOP/Knowledge Base: '{$document->title}' ({$chunksCount} chunks vektor dibuat)",
            'channel' => ChannelType::WEB,
            'created_at' => now(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Dokumen pengetahuan berhasil ditambahkan dan ' . $chunksCount . ' chunk vektor telah diindeks.',
            'data' => [
                'document_id' => $document->document_id,
                'title' => $document->title,
                'chunks_count' => $chunksCount,
            ],
        ], 201);
    }

    /**
     * Display the specified knowledge document and all its chunks.
     */
    public function show(string|int $id): JsonResponse
    {
        $document = KbDocument::with(['chunks' => function ($q) {
            $q->orderBy('chunk_index', 'asc');
        }])->findOrFail($id);

        $chunks = $document->chunks->map(function (KbChunk $chunk) {
            return [
                'chunk_id' => $chunk->chunk_id,
                'chunk_index' => $chunk->chunk_index,
                'content' => $chunk->content,
                'char_count' => mb_strlen($chunk->content),
                'word_count' => str_word_count($chunk->content),
                'embedding_sample' => json_decode($chunk->embedding ?? '[]'),
                'metadata' => $chunk->metadata,
                'created_at' => $chunk->created_at?->translatedFormat('d M Y, H:i') . ' WIB',
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => [
                'document_id' => $document->document_id,
                'doc_code' => 'DOC-' . str_pad($document->document_id, 3, '0', STR_PAD_LEFT),
                'title' => $document->title,
                'domain' => $document->domain instanceof ServiceDomain ? $document->domain->value : (string) $document->domain,
                'source' => $document->source,
                'validator' => $document->validator,
                'version' => $document->version,
                'is_active' => (bool) $document->is_active,
                'chunks_count' => $chunks->count(),
                'chunks' => $chunks,
                'created_at' => $document->created_at?->translatedFormat('d M Y, H:i') . ' WIB',
                'updated_at' => $document->updated_at?->translatedFormat('d M Y, H:i') . ' WIB',
            ],
        ]);
    }

    /**
     * Update the specified knowledge document and re-chunk content if provided.
     */
    public function update(Request $request, string|int $id): JsonResponse
    {
        $document = KbDocument::findOrFail($id);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'domain' => ['required', 'string', 'in:PUBLIC_SERVICE,UMKM,WASTE_EDUCATION'],
            'source' => ['nullable', 'string', 'max:255'],
            'validator' => ['nullable', 'string', 'max:100'],
            'version' => ['nullable', 'string', 'max:50'],
            'is_active' => ['nullable', 'boolean'],
            'content' => ['nullable', 'string'],
            'chunks' => ['nullable', 'array'],
        ]);

        $document->update([
            'title' => $validated['title'],
            'domain' => ServiceDomain::from($validated['domain']),
            'source' => $validated['source'] ?? $document->source,
            'validator' => $validated['validator'] ?? $document->validator,
            'version' => $validated['version'] ?? $document->version,
            'is_active' => isset($validated['is_active']) ? (bool) $validated['is_active'] : $document->is_active,
        ]);

        $chunksCount = $document->chunks()->count();

        // If new content or raw chunks are supplied, re-generate chunks
        if (isset($validated['content']) || isset($validated['chunks'])) {
            $contentToChunk = $validated['content'] ?? ($validated['chunks'] ?? []);
            $chunksCount = $this->chunkingService->processAndSaveChunks($document, $contentToChunk);
        }

        $operator = $request->user();
        ActivityLog::create([
            'operator_id' => $operator?->operator_id,
            'actor_name' => $operator?->name ?? 'Aparatur Pekon',
            'action' => 'VECTOR_DOC_UPDATE',
            'target' => '#DOC-' . str_pad($document->document_id, 3, '0', STR_PAD_LEFT),
            'description' => "Memperbarui dokumen pengetahuan: '{$document->title}'",
            'channel' => ChannelType::WEB,
            'created_at' => now(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Dokumen pengetahuan berhasil diperbarui.',
            'data' => [
                'document_id' => $document->document_id,
                'title' => $document->title,
                'chunks_count' => $chunksCount,
            ],
        ]);
    }

    /**
     * Remove the specified knowledge document and all its chunks.
     */
    public function destroy(Request $request, string|int $id): JsonResponse
    {
        $document = KbDocument::findOrFail($id);
        $title = $document->title;
        $docId = $document->document_id;

        $document->chunks()->delete();
        $document->delete();

        $operator = $request->user();
        ActivityLog::create([
            'operator_id' => $operator?->operator_id,
            'actor_name' => $operator?->name ?? 'Aparatur Pekon',
            'action' => 'VECTOR_DOC_DELETE',
            'target' => '#DOC-' . str_pad($docId, 3, '0', STR_PAD_LEFT),
            'description' => "Menghapus dokumen pengetahuan: '{$title}' dan seluruh potongan vektornya",
            'channel' => ChannelType::WEB,
            'created_at' => now(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Dokumen dan seluruh chunk vektor terkait berhasil dihapus.',
        ]);
    }

    /**
     * Re-index chunks for a specific document.
     */
    public function reindex(Request $request, string|int $id): JsonResponse
    {
        $document = KbDocument::with('chunks')->findOrFail($id);
        $existingChunks = $document->chunks->pluck('content')->toArray();

        $count = $this->chunkingService->processAndSaveChunks($document, $existingChunks);

        $operator = $request->user();
        ActivityLog::create([
            'operator_id' => $operator?->operator_id,
            'actor_name' => $operator?->name ?? 'Aparatur Pekon',
            'action' => 'VECTOR_REINDEX',
            'target' => '#DOC-' . str_pad($document->document_id, 3, '0', STR_PAD_LEFT),
            'description' => "Melakukan re-indexing vektor untuk dokumen: '{$document->title}' ({$count} chunks)",
            'channel' => ChannelType::WEB,
            'created_at' => now(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Indeks vektor berhasil diperbarui untuk dokumen ini (' . $count . ' chunks).',
        ]);
    }

    /**
     * Re-index all active knowledge documents.
     */
    public function reindexAll(Request $request): JsonResponse
    {
        $documents = KbDocument::with('chunks')->where('is_active', true)->get();
        $totalChunks = 0;

        foreach ($documents as $doc) {
            $chunkTexts = $doc->chunks->pluck('content')->toArray();
            if (!empty($chunkTexts)) {
                $totalChunks += $this->chunkingService->processAndSaveChunks($doc, $chunkTexts);
            }
        }

        $operator = $request->user();
        ActivityLog::create([
            'operator_id' => $operator?->operator_id,
            'actor_name' => $operator?->name ?? 'Aparatur Pekon',
            'action' => 'VECTOR_REINDEX_ALL',
            'target' => '#ALL-DOCS',
            'description' => "Sinkronisasi & re-indexing menyeluruh seluruh basis data vektor ({$documents->count()} dokumen, {$totalChunks} chunks)",
            'channel' => ChannelType::WEB,
            'created_at' => now(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Seluruh basis data vektor berhasil disinkronkan (' . $documents->count() . ' dokumen, ' . $totalChunks . ' chunks terindeks).',
            'data' => [
                'total_documents' => $documents->count(),
                'total_chunks' => $totalChunks,
            ],
        ]);
    }

    /**
     * Simulate semantic search retrieval across all active knowledge chunks.
     */
    public function testRetrieval(Request $request): JsonResponse
    {
        $request->validate([
            'query' => ['required', 'string'],
            'top_k' => ['nullable', 'integer', 'min:1', 'max:10'],
            'domain' => ['nullable', 'string'],
        ]);

        $query = trim($request->input('query'));
        $topK = (int) $request->input('top_k', 4);
        $domain = $request->input('domain');

        $retrievalResult = $this->chunkingService->testRetrieval($query, $topK, $domain);

        return response()->json([
            'status' => 'success',
            'data' => $retrievalResult,
        ]);
    }

    /**
     * Update an individual chunk content.
     */
    public function updateChunk(Request $request, int $chunkId): JsonResponse
    {
        $chunk = KbChunk::with('document')->findOrFail($chunkId);
        $validated = $request->validate([
            'content' => ['required', 'string'],
        ]);

        $chunk->content = trim($validated['content']);
        $chunk->metadata = array_merge($chunk->metadata ?? [], [
            'char_count' => mb_strlen($chunk->content),
            'word_count' => str_word_count($chunk->content),
            'updated_at' => now()->toIso8601String(),
        ]);
        $chunk->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Potongan teks chunk berhasil diperbarui.',
            'data' => [
                'chunk_id' => $chunk->chunk_id,
                'content' => $chunk->content,
            ],
        ]);
    }

    /**
     * Delete an individual chunk.
     */
    public function deleteChunk(int $chunkId): JsonResponse
    {
        $chunk = KbChunk::findOrFail($chunkId);
        $docId = $chunk->document_id;
        $chunk->delete();

        // Re-index remaining chunks index order
        $remaining = KbChunk::where('document_id', $docId)->orderBy('chunk_index')->get();
        foreach ($remaining as $idx => $remChunk) {
            $remChunk->chunk_index = $idx;
            $remChunk->save();
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Chunk berhasil dihapus.',
        ]);
    }
}
