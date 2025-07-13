<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RealtimeController extends Controller
{
    /**
     * Create a relay connection to OpenAI Realtime API
     * This will return WebSocket connection details for the frontend
     */
    public function createSession(Request $request)
    {
        try {
            $apiKey = config('openai.api_key');

            // For now, we'll return connection info that the frontend can use
            // In production, you'd want to create a proper WebSocket proxy
            return response()->json([
                'status' => 'success',
                'session' => [
                    'id' => uniqid('session_'),
                    'model' => 'gpt-4o-realtime-preview-2024-12-17',
                    'instructions' => $request->input('instructions', 'You are an expert sales coach providing real-time guidance.'),
                    'voice' => $request->input('voice', 'alloy'),
                    'turn_detection' => [
                        'type' => 'server_vad',
                        'threshold' => 0.5,
                        'prefix_padding_ms' => 300,
                        'silence_duration_ms' => 500,
                    ],
                    'tools' => $this->getSalesTools(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to create realtime session: '.$e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create session',
            ], 500);
        }
    }

    /**
     * Generate ephemeral key for secure browser usage
     */
    public function generateEphemeralKey(Request $request)
    {
        try {
            $apiKey = config('openai.api_key');

            // For now, return a mock ephemeral key structure
            // In production, you would call OpenAI's API to generate a real ephemeral key
            // Example: POST https://api.openai.com/v1/realtime/sessions

            // TODO: Implement actual ephemeral key generation when OpenAI provides the endpoint
            return response()->json([
                'status' => 'success',
                'ephemeralKey' => 'ek_'.bin2hex(random_bytes(32)), // Mock ephemeral key
                'expiresAt' => now()->addMinutes(60)->toIso8601String(),
                'model' => 'gpt-4o-realtime-preview-2024-12-17',
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to generate ephemeral key: '.$e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to generate ephemeral key',
            ], 500);
        }
    }

    /**
     * Get the sales-specific tools for the AI
     */
    private function getSalesTools()
    {
        return [
            [
                'type' => 'function',
                'name' => 'show_teleprompter_script',
                'description' => 'Display a script for the salesperson to read',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'text' => [
                            'type' => 'string',
                            'description' => 'The complete script text to display',
                        ],
                        'priority' => [
                            'type' => 'string',
                            'enum' => ['high', 'normal', 'low'],
                            'description' => 'Priority level of the script',
                        ],
                        'context' => [
                            'type' => 'string',
                            'description' => 'Brief context about when to use this script',
                        ],
                    ],
                    'required' => ['text', 'priority', 'context'],
                ],
            ],
            [
                'type' => 'function',
                'name' => 'update_sales_metrics',
                'description' => 'Update real-time sales metrics display',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'talkRatio' => [
                            'type' => 'number',
                            'description' => 'Percentage of time salesperson is talking (0-100)',
                        ],
                        'sentiment' => [
                            'type' => 'string',
                            'enum' => ['positive', 'negative', 'neutral'],
                            'description' => 'Current conversation sentiment',
                        ],
                        'topics' => [
                            'type' => 'array',
                            'items' => ['type' => 'string'],
                            'description' => 'Key topics discussed',
                        ],
                        'buyingSignals' => [
                            'type' => 'number',
                            'description' => 'Number of buying signals detected',
                        ],
                    ],
                    'required' => ['talkRatio', 'sentiment'],
                ],
            ],
            [
                'type' => 'function',
                'name' => 'highlight_opportunity',
                'description' => 'Highlight a sales opportunity or important moment',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'type' => [
                            'type' => 'string',
                            'enum' => ['buying_signal', 'objection', 'question', 'closing_opportunity'],
                            'description' => 'Type of opportunity',
                        ],
                        'confidence' => [
                            'type' => 'number',
                            'description' => 'Confidence level (0-1)',
                        ],
                        'suggestion' => [
                            'type' => 'string',
                            'description' => 'Suggested action to take',
                        ],
                        'urgency' => [
                            'type' => 'string',
                            'enum' => ['immediate', 'soon', 'low'],
                            'description' => 'How urgent is this opportunity',
                        ],
                    ],
                    'required' => ['type', 'confidence', 'suggestion'],
                ],
            ],
            [
                'type' => 'function',
                'name' => 'show_battle_card',
                'description' => 'Display competitive information or product details',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'topic' => [
                            'type' => 'string',
                            'description' => 'Topic of the battle card',
                        ],
                        'content' => [
                            'type' => 'object',
                            'properties' => [
                                'keyPoints' => [
                                    'type' => 'array',
                                    'items' => ['type' => 'string'],
                                    'description' => 'Key talking points',
                                ],
                                'advantages' => [
                                    'type' => 'array',
                                    'items' => ['type' => 'string'],
                                    'description' => 'Our advantages',
                                ],
                                'statistics' => [
                                    'type' => 'array',
                                    'items' => ['type' => 'string'],
                                    'description' => 'Relevant statistics',
                                ],
                            ],
                        ],
                    ],
                    'required' => ['topic', 'content'],
                ],
            ],
            [
                'type' => 'function',
                'name' => 'calculate_roi',
                'description' => 'Calculate and display ROI for the customer',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'investment' => [
                            'type' => 'number',
                            'description' => 'Initial investment amount',
                        ],
                        'savings' => [
                            'type' => 'number',
                            'description' => 'Monthly or annual savings',
                        ],
                        'timeframe' => [
                            'type' => 'string',
                            'description' => 'Timeframe for ROI calculation',
                        ],
                        'additionalBenefits' => [
                            'type' => 'array',
                            'items' => ['type' => 'string'],
                            'description' => 'Non-monetary benefits',
                        ],
                    ],
                    'required' => ['investment', 'savings', 'timeframe'],
                ],
            ],
        ];
    }
}
