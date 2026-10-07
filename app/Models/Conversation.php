<?php

namespace App\Models;

use App\Enums\ChannelType;
use App\Enums\ConversationStatus;
use App\Enums\PriorityLevel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends Model
{
    use HasFactory;

    protected $table = 'conversations';
    protected $primaryKey = 'conversation_id';

    protected $fillable = [
        'user_id',
        'category_id',
        'assigned_operator_id',
        'channel',
        'status',
        'priority',
        'needs_human',
        'citizen_name',
        'external_conversation_id',
        'started_at',
        'last_message_at',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'channel' => ChannelType::class,
            'status' => ConversationStatus::class,
            'priority' => PriorityLevel::class,
            'needs_human' => 'boolean',
            'started_at' => 'datetime',
            'last_message_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class, 'category_id', 'category_id');
    }

    public function assignedOperator(): BelongsTo
    {
        return $this->belongsTo(Operator::class, 'assigned_operator_id', 'operator_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class, 'conversation_id', 'conversation_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(ConversationAssignment::class, 'conversation_id', 'conversation_id');
    }

    public function hitlEvents(): HasMany
    {
        return $this->hasMany(HitlEvent::class, 'conversation_id', 'conversation_id');
    }

    public function feedback(): HasMany
    {
        return $this->hasMany(Feedback::class, 'conversation_id', 'conversation_id');
    }

    public function researchSessions(): HasMany
    {
        return $this->hasMany(ResearchSession::class, 'conversation_id', 'conversation_id');
    }

    public function systemLogs(): HasMany
    {
        return $this->hasMany(SystemLog::class, 'conversation_id', 'conversation_id');
    }
}
