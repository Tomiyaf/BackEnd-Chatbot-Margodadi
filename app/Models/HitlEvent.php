<?php

namespace App\Models;

use App\Enums\HitlEventType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HitlEvent extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    protected $table = 'hitl_events';
    protected $primaryKey = 'event_id';

    protected $fillable = [
        'conversation_id',
        'operator_id',
        'event_type',
        'notes',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'event_type' => HitlEventType::class,
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
}
