<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    protected $fillable = [
        'context_snapshot_ids',
        'user_message',
        'assistant_response',
        'model',
        'provider',
        'tokens_used',
    ];

    protected $casts = [
        'context_snapshot_ids' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function getContextSnapshotsAttribute()
    {
        if (empty($this->context_snapshot_ids)) {
            return collect();
        }

        return ContextSnapshot::whereIn('id', $this->context_snapshot_ids)->get();
    }
}
