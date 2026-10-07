<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EducationExposure extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    protected $table = 'education_exposures';
    protected $primaryKey = 'exposure_id';

    protected $fillable = [
        'research_session_id',
        'topic_id',
        'interaction_count',
        'quiz_score',
        'started_at',
        'completed_at',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'interaction_count' => 'integer',
            'quiz_score' => 'float',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function researchSession(): BelongsTo
    {
        return $this->belongsTo(ResearchSession::class, 'research_session_id', 'research_session_id');
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(EducationTopic::class, 'topic_id', 'topic_id');
    }
}
