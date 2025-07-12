<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Template extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'name',
        'description',
        'prompt',
        'variables',
        'talking_points',
        'additional_info',
        'category',
        'icon',
        'is_system',
        'usage_count',
        'shortcut',
    ];

    protected $casts = [
        'variables' => 'array',
        'talking_points' => 'array',
        'additional_info' => 'array',
        'is_system' => 'boolean',
        'usage_count' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }
}
