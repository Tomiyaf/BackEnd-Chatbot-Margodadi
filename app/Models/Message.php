<?php

namespace App\Models;

use App\Enums\SenderType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Message extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    protected $table = 'messages';
    protected $primaryKey = 'message_id';

    protected $fillable = [
        'conversation_id',
        'operator_id',
        'sender_type',
        'content',
        'metadata',
        'external_message_id',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'sender_type' => SenderType::class,
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class, 'conversation_id', 'conversation_id');
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(Operator::class, 'operator_id', 'operator_id');
    }

    public function ragInteractions(): HasMany
    {
        return $this->hasMany(RagInteraction::class, 'message_id', 'message_id');
    }

    public function feedback(): HasMany
    {
        return $this->hasMany(Feedback::class, 'message_id', 'message_id');
    }

    public function systemLogs(): HasMany
    {
        return $this->hasMany(SystemLog::class, 'message_id', 'message_id');
    }
}
