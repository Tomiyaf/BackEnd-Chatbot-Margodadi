<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RagInteraction extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    protected $table = 'rag_interactions';
    protected $primaryKey = 'rag_interaction_id';

    protected $fillable = [
        'message_id',
        'rag_config_id',
        'query',
        'response',
        'status',
        'latency_ms',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'latency_ms' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class, 'message_id', 'message_id');
    }

    public function config(): BelongsTo
    {
        return $this->belongsTo(RagConfiguration::class, 'rag_config_id', 'rag_config_id');
    }

    public function retrievals(): HasMany
    {
        return $this->hasMany(RagRetrieval::class, 'rag_interaction_id', 'rag_interaction_id');
    }
}
