<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Variable extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'key',
        'value',
        'type',
        'description',
        'category',
        'is_system',
        'validation_rules',
    ];

    protected $casts = [
        'is_system' => 'boolean',
        'validation_rules' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the typed value based on the type field
     */
    public function getTypedValueAttribute()
    {
        return match ($this->type) {
            'number' => (float) $this->value,
            'boolean' => filter_var($this->value, FILTER_VALIDATE_BOOLEAN),
            'json' => json_decode($this->value, true),
            default => $this->value,
        };
    }

    /**
     * Set the value with type conversion
     */
    public function setValueAttribute($value)
    {
        $this->attributes['value'] = match ($this->type ?? 'text') {
            'json' => is_string($value) ? $value : json_encode($value),
            'boolean' => $value ? 'true' : 'false',
            default => (string) $value,
        };
    }

    /**
     * Validate the value against the validation rules
     */
    public function validate(): bool
    {
        if (empty($this->validation_rules)) {
            return true;
        }

        $validator = validator(
            ['value' => $this->typed_value],
            ['value' => $this->validation_rules]
        );

        return !$validator->fails();
    }

    /**
     * Scope to filter by category
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope to filter system variables
     */
    public function scopeSystem($query)
    {
        return $query->where('is_system', true);
    }

    /**
     * Scope to filter user variables
     */
    public function scopeUser($query)
    {
        return $query->where('is_system', false);
    }
}