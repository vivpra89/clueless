<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContextSnapshot extends Model
{
    protected $fillable = [
        'type',
        'content',
        'metadata',
        'embedding',
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function conversations()
    {
        return $this->belongsToMany(Conversation::class, 'conversation_context_snapshot');
    }
}
