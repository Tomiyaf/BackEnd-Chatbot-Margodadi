<?php

namespace App\Models;

use App\Enums\ChannelType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    protected $table = 'activity_logs';
    protected $primaryKey = 'log_id';

    protected $fillable = [
        'operator_id',
        'actor_name',
        'action',
        'target',
        'description',
        'channel',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'channel' => ChannelType::class,
            'created_at' => 'datetime',
        ];
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(Operator::class, 'operator_id', 'operator_id');
    }
}
