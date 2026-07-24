<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TourPackage extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'duration',
        'price',
        'highlights',
        'included',
        'category',
        'image_url',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'highlights' => 'array',
            'included' => 'array',
            'price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    // Scope to only retrieve active packages
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
