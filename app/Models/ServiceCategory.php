<?php

namespace App\Models;

use App\Enums\ServiceDomain;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceCategory extends Model
{
    use HasFactory;

    protected $table = 'service_categories';
    protected $primaryKey = 'category_id';

    protected $fillable = [
        'parent_id',
        'name',
        'slug',
        'domain',
        'icon',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'domain' => ServiceDomain::class,
            'is_active' => 'boolean',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class, 'parent_id', 'category_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(ServiceCategory::class, 'parent_id', 'category_id');
    }

    public function publicServices(): HasMany
    {
        return $this->hasMany(PublicService::class, 'category_id', 'category_id');
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class, 'category_id', 'category_id');
    }

    public function umkms(): HasMany
    {
        return $this->hasMany(Umkm::class, 'category_id', 'category_id');
    }
}
