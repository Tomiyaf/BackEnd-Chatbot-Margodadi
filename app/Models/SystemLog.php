<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SystemLog extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    protected $table = 'system_logs';
    protected $primaryKey = 'log_id';

    protected $fillable = [
        'conversation_id',
        'message_id',
        'service',
        'event_type',
        'status',
        'error_code',
        'latency_ms',
        'metadata',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'latency_ms' => 'integer',
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class, 'conversation_id', 'conversation_id');
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class, 'message_id', 'message_id');
    }
}
