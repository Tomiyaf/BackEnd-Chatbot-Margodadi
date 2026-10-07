<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KbChunk extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    protected $table = 'kb_chunks';
    protected $primaryKey = 'chunk_id';

    protected $fillable = [
        'document_id',
        'chunk_index',
        'content',
        'embedding',
        'metadata',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'chunk_index' => 'integer',
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(KbDocument::class, 'document_id', 'document_id');
    }

    public function retrievals(): HasMany
    {
        return $this->hasMany(RagRetrieval::class, 'chunk_id', 'chunk_id');
    }
}
