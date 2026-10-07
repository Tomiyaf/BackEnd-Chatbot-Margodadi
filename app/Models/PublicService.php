<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PublicService extends Model
{
    use HasFactory;

    protected $table = 'public_services';
    protected $primaryKey = 'service_id';

    protected $fillable = [
        'category_id',
        'title',
        'slug',
        'category_badge',
        'sub_category_badge',
        'description',
        'legal_basis',
        'requirements',
        'steps',
        'sla_duration',
        'cost_info',
        'officer_in_charge',
        'download_url',
        'tags',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'requirements' => 'array',
            'steps' => 'array',
            'tags' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class, 'category_id', 'category_id');
    }
}
