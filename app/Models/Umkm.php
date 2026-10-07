<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Umkm extends Model
{
    use HasFactory;

    protected $table = 'umkms';
    protected $primaryKey = 'umkm_id';

    protected $fillable = [
        'category_id',
        'reg_number',
        'name',
        'sub_title',
        'owner_name',
        'phone',
        'wa_number',
        'address',
        'description',
        'history',
        'banner_image_url',
        'gallery_urls',
        'legal_certification',
        'legal_number',
        'production_capacity',
        'capacity_note',
        'group_name',
        'group_location',
        'map_title',
        'map_address',
        'map_url',
        'last_verified_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'gallery_urls' => 'array',
            'last_verified_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class, 'category_id', 'category_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(UmkmProduct::class, 'umkm_id', 'umkm_id');
    }
}
