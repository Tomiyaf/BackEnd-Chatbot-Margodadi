<?php

namespace App\Models;

use App\Enums\OperatorRole;
use App\Enums\OperatorStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Operator extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'operators';
    protected $primaryKey = 'operator_id';

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'avatar_url',
        'role',
        'status',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'role' => OperatorRole::class,
            'status' => OperatorStatus::class,
            'is_active' => 'boolean',
        ];
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class, 'assigned_operator_id', 'operator_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class, 'operator_id', 'operator_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(ConversationAssignment::class, 'operator_id', 'operator_id');
    }

    public function hitlEvents(): HasMany
    {
        return $this->hasMany(HitlEvent::class, 'operator_id', 'operator_id');
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class, 'operator_id', 'operator_id');
    }
}
