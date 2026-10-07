<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Sanctum\HasApiTokens;

class User extends Model
{
    use HasApiTokens, HasFactory;

    protected $table = 'users';
    protected $primaryKey = 'user_id';

    protected $fillable = [
        'anonymous_code',
        'phone_number',
    ];

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class, 'user_id', 'user_id');
    }

    public function feedback(): HasMany
    {
        return $this->hasMany(Feedback::class, 'user_id', 'user_id');
    }

    public function researchSessions(): HasMany
    {
        return $this->hasMany(ResearchSession::class, 'user_id', 'user_id');
    }
}
