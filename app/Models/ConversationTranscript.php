<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConversationTranscript extends Model
{
    protected $fillable = [
        'session_id',
        'speaker',
        'text',
        'spoken_at',
        'group_id',
        'system_category',
        'order_index',
    ];

    protected $casts = [
        'spoken_at' => 'datetime',
        'order_index' => 'integer',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(ConversationSession::class, 'session_id');
    }

    public function getSpeakerLabelAttribute(): string
    {
        return match ($this->speaker) {
            'salesperson' => 'You',
            'customer' => 'Customer',
            'system' => 'System',
            default => ucfirst($this->speaker),
        };
    }

    public function getSpeakerColorAttribute(): string
    {
        return match ($this->speaker) {
            'salesperson' => 'text-blue-600',
            'customer' => 'text-green-600',
            'system' => 'text-gray-500',
            default => 'text-gray-700',
        };
    }
}
