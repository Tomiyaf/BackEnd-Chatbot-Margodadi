<?php

namespace App\Services;

use App\Models\KbChunk;
use App\Models\KbDocument;
use Illuminate\Support\Str;

class VectorChunkingService
{
    /**
     * Split document text into overlapping chunks.
     *
     * @param string $text
     * @param int $chunkSize Target character size per chunk
     * @param int $overlap Overlap characters between consecutive chunks
     * @return array<string>
     */
    public function chunkText(string $text, int $chunkSize = 500, int $overlap = 50): array
    {
        $cleanText = trim(preg_replace('/\s+/', ' ', $text));
        if (empty($cleanText)) {
            return [];
        }

        // If text is smaller than chunk size, return single chunk
        if (mb_strlen($cleanText) <= $chunkSize) {
            return [$cleanText];
        }

        $chunks = [];
        $length = mb_strlen($cleanText);
        $start = 0;

        while ($start < $length) {
            $end = min($start + $chunkSize, $length);
            
            // If not at the very end, try to break at a sentence or word boundary
            if ($end < $length) {
                $slice = mb_substr($cleanText, $start, $chunkSize);
                // Look for sentence end (.!?) or newline/space
                $boundary = preg_match('/[.!?]\s+/u', $slice, $matches, PREG_OFFSET_CAPTURE);
                if ($boundary && end($matches)[1] > ($chunkSize * 0.5)) {
                    $end = $start + end($matches)[1] + mb_strlen(end($matches)[0]);
                } else {
                    // Fallback to last space
                    $lastSpace = mb_strrpos($slice, ' ');
                    if ($lastSpace !== false && $lastSpace > ($chunkSize * 0.6)) {
                        $end = $start + $lastSpace + 1;
                    }
                }
            }

            $chunkContent = trim(mb_substr($cleanText, $start, $end - $start));
            if (!empty($chunkContent)) {
                $chunks[] = $chunkContent;
            }

            if ($end >= $length) {
                break;
            }

            // Move forward taking overlap into account
            $start = max($end - $overlap, $start + 1);
        }

        return $chunks;
    }

    /**
     * Process document and persist chunks to database.
     *
     * @param KbDocument $document
     * @param string|array $content
     * @param int $chunkSize
     * @param int $overlap
     * @return int Number of chunks created
     */
    public function processAndSaveChunks(
        KbDocument $document,
        string|array $content,
        int $chunkSize = 500,
        int $overlap = 50
    ): int {
        // Delete previous chunks for clean re-indexing
        $document->chunks()->delete();

        $chunkTexts = is_array($content) ? $content : $this->chunkText($content, $chunkSize, $overlap);
        $savedCount = 0;

        foreach ($chunkTexts as $index => $chunkText) {
            $chunkText = trim($chunkText);
            if (empty($chunkText)) {
                continue;
            }

            // Approximate vector embedding mockup (vector dimension: 8 float points for visual representation)
            $pseudoEmbedding = $this->generatePseudoEmbedding($chunkText);

            KbChunk::create([
                'document_id' => $document->document_id,
                'chunk_index' => $index,
                'content' => $chunkText,
                'embedding' => json_encode($pseudoEmbedding),
                'metadata' => [
                    'document_id' => $document->document_id,
                    'title' => $document->title,
                    'domain' => $document->domain?->value ?? 'PUBLIC_SERVICE',
                    'source' => $document->source,
                    'validator' => $document->validator,
                    'version' => $document->version,
                    'char_count' => mb_strlen($chunkText),
                    'word_count' => str_word_count($chunkText),
                    'indexed_at' => now()->toIso8601String(),
                ],
                'created_at' => now(),
            ]);

            $savedCount++;
        }

        return $savedCount;
    }

    /**
     * Perform Cosine Similarity scoring test against active knowledge chunks.
     *
     * @param string $query
     * @param int $topK
     * @param string|null $domainFilter
     * @return array
     */
    public function testRetrieval(string $query, int $topK = 4, ?string $domainFilter = null): array
    {
        $startTime = microtime(true);
        $queryTokens = $this->tokenize($query);

        $chunksQuery = KbChunk::with('document')->whereHas('document', function ($q) use ($domainFilter) {
            $q->where('is_active', true);
            if ($domainFilter && strtoupper($domainFilter) !== 'ALL') {
                $q->where('domain', strtoupper($domainFilter));
            }
        });

        $chunks = $chunksQuery->get();
        $scored = [];

        foreach ($chunks as $chunk) {
            $chunkTokens = $this->tokenize($chunk->content . ' ' . ($chunk->document?->title ?? ''));
            $similarity = $this->calculateCosineSimilarity($queryTokens, $chunkTokens);

            // Boost score if keyword exactly matches
            if (stripos($chunk->content, $query) !== false) {
                $similarity = min(0.995, $similarity + 0.25);
            }

            // Always give a baseline score if relevant tokens match
            if ($similarity > 0.05 || empty($queryTokens)) {
                $scored[] = [
                    'chunk_id' => $chunk->chunk_id,
                    'chunk_index' => $chunk->chunk_index,
                    'document_id' => $chunk->document_id,
                    'document_title' => $chunk->document?->title ?? 'Dokumen SOP',
                    'domain' => $chunk->document?->domain?->value ?? 'PUBLIC_SERVICE',
                    'validator' => $chunk->document?->validator ?? 'Aparatur Pekon',
                    'source' => $chunk->document?->source ?? '-',
                    'content' => $chunk->content,
                    'similarity' => round($similarity, 4),
                    'similarity_percentage' => round($similarity * 100, 1) . '%',
                    'char_count' => mb_strlen($chunk->content),
                    'metadata' => $chunk->metadata,
                ];
            }
        }

        // Sort descending by similarity score
        usort($scored, fn ($a, $b) => $b['similarity'] <=> $a['similarity']);
        $topResults = array_slice($scored, 0, $topK);

        $executionTimeMs = round((microtime(true) - $startTime) * 1000, 2);

        return [
            'query' => $query,
            'top_k' => $topK,
            'total_candidates' => count($chunks),
            'matched_count' => count($topResults),
            'execution_time' => $executionTimeMs . ' ms',
            'results' => $topResults,
        ];
    }

    /**
     * Simple tokenization for Indonesian language text.
     */
    private function tokenize(string $text): array
    {
        $clean = mb_strtolower(preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $text));
        $words = preg_split('/\s+/', $clean, -1, PREG_SPLIT_NO_EMPTY);
        
        // Stopwords filter for common Indonesian words
        $stopwords = [
            'yang', 'di', 'ke', 'dari', 'dan', 'ini', 'itu', 'untuk', 'pada', 'adalah',
            'dengan', 'atau', 'dalam', 'saya', 'kami', 'anda', 'bisa', 'dapat', 'apa',
            'bagaimana', 'kapan', 'dimana', 'apakah'
        ];
        
        $tokens = array_filter($words, fn ($w) => !in_array($w, $stopwords) && mb_strlen($w) > 1);
        return array_values($tokens);
    }

    /**
     * Compute term-frequency Cosine Similarity between query tokens and document tokens.
     */
    private function calculateCosineSimilarity(array $queryTokens, array $docTokens): float
    {
        if (empty($queryTokens) || empty($docTokens)) {
            return 0.0;
        }

        $queryVector = array_count_values($queryTokens);
        $docVector = array_count_values($docTokens);

        $allTerms = array_unique(array_merge(array_keys($queryVector), array_keys($docVector)));

        $dotProduct = 0.0;
        $queryMagnitude = 0.0;
        $docMagnitude = 0.0;

        foreach ($allTerms as $term) {
            $qCount = $queryVector[$term] ?? 0;
            $dCount = $docVector[$term] ?? 0;

            $dotProduct += $qCount * $dCount;
            $queryMagnitude += $qCount * $qCount;
            $docMagnitude += $dCount * $dCount;
        }

        if ($queryMagnitude == 0 || $docMagnitude == 0) {
            return 0.0;
        }

        $score = $dotProduct / (sqrt($queryMagnitude) * sqrt($docMagnitude));
        return min(1.0, max(0.0, $score));
    }

    /**
     * Generate normalized pseudo-embedding float vector.
     */
    private function generatePseudoEmbedding(string $text): array
    {
        $hash = md5($text);
        $vector = [];
        for ($i = 0; $i < 8; $i++) {
            $hex = substr($hash, $i * 4, 4);
            $val = (hexdec($hex) / 65535) * 2 - 1; // Range -1.0 to 1.0
            $vector[] = round($val, 4);
        }
        return $vector;
    }
}
