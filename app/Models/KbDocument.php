<?php

namespace App\Models;

use App\Enums\ServiceDomain;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KbDocument extends Model
{
    use HasFactory;

    protected $table = 'kb_documents';
    protected $primaryKey = 'document_id';

    protected $fillable = [
        'title',
        'domain',
        'source',
        'validator',
        'version',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'domain' => ServiceDomain::class,
            'is_active' => 'boolean',
        ];
    }

    public function chunks(): HasMany
    {
        return $this->hasMany(KbChunk::class, 'document_id', 'document_id');
    }
}
