<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RagConfiguration extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    protected $table = 'rag_configurations';
    protected $primaryKey = 'rag_config_id';

    protected $fillable = [
        'version',
        'embedding_model',
        'llm_model',
        'chunking_config',
        'retrieval_config',
        'generation_config',
        'is_active',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'chunking_config' => 'array',
            'retrieval_config' => 'array',
            'generation_config' => 'array',
            'is_active' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public function interactions(): HasMany
    {
        return $this->hasMany(RagInteraction::class, 'rag_config_id', 'rag_config_id');
    }
}
