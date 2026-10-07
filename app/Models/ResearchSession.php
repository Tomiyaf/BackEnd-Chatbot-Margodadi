<?php

namespace App\Models;

use App\Enums\ResearchSessionStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ResearchSession extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    protected $table = 'research_sessions';
    protected $primaryKey = 'research_session_id';

    protected $fillable = [
        'user_id',
        'conversation_id',
        'anonymous_code',
        'education_version',
        'quiz_score',
        'status',
        'started_at',
        'completed_at',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'quiz_score' => 'float',
            'status' => ResearchSessionStatus::class,
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class, 'conversation_id', 'conversation_id');
    }

    public function exposures(): HasMany
    {
        return $this->hasMany(EducationExposure::class, 'research_session_id', 'research_session_id');
    }
}
