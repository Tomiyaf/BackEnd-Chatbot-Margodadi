<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EducationTopic extends Model
{
    use HasFactory;

    protected $table = 'education_topics';
    protected $primaryKey = 'topic_id';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'knowledge_base_version',
        'sequence_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'sequence_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function exposures(): HasMany
    {
        return $this->hasMany(EducationExposure::class, 'topic_id', 'topic_id');
    }
}
