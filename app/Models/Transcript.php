<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Transcript extends Model
{
    use HasUuids;

    protected $fillable = [
        'session_start',
        'session_end',
        'transcript',
        'segments',
        'host_transcript',
        'guest_transcript',
        'summary',
        'teleprompter_suggestions',
        'insights',
        'metadata',
        'is_active',
    ];

    protected $casts = [
        'session_start' => 'datetime',
        'session_end' => 'datetime',
        'segments' => 'array',
        'insights' => 'array',
        'metadata' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Get the current active transcript
     */
    public static function activeTranscript()
    {
        return self::where('is_active', true)
            ->orderBy('session_start', 'desc')
            ->first();
    }

    /**
     * Append text to the transcript
     */
    public function appendTranscript(string $text): void
    {
        $this->transcript = trim($this->transcript.' '.$text);
        $this->save();
    }

    /**
     * Append a segment with speaker differentiation
     */
    public function appendSegment(string $text, string $speaker = 'unknown', ?float $timestamp = null): void
    {
        $segments = $this->segments ?? [];
        
        $segment = [
            'speaker' => $speaker,
            'text' => $text,
            'timestamp' => $timestamp ?? now()->timestamp,
        ];
        
        $segments[] = $segment;
        $this->segments = $segments;
        
        // Update speaker-specific transcripts
        if ($speaker === 'host') {
            $this->host_transcript = trim(($this->host_transcript ?? '').' '.$text);
        } elseif ($speaker === 'guest') {
            $this->guest_transcript = trim(($this->guest_transcript ?? '').' '.$text);
        }
        
        // Update combined transcript with speaker labels
        $this->transcript = trim($this->transcript.' ['.$speaker.'] '.$text);
        
        $this->save();
    }

    /**
     * End the current session
     */
    public function endSession(): void
    {
        $this->session_end = now();
        $this->is_active = false;
        $this->save();
    }
}
