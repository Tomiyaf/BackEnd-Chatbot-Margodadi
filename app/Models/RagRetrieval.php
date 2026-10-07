<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RagRetrieval extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    protected $table = 'rag_retrievals';
    protected $primaryKey = 'retrieval_id';

    protected $fillable = [
        'rag_interaction_id',
        'chunk_id',
        'rank',
        'similarity_score',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'rank' => 'integer',
            'similarity_score' => 'float',
            'created_at' => 'datetime',
        ];
    }

    public function interaction(): BelongsTo
    {
        return $this->belongsTo(RagInteraction::class, 'rag_interaction_id', 'rag_interaction_id');
    }

    public function chunk(): BelongsTo
    {
        return $this->belongsTo(KbChunk::class, 'chunk_id', 'chunk_id');
    }
}
