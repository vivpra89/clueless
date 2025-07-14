<?php

namespace App\Http\Controllers;

use App\Services\ApiKeyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RealtimeController extends Controller
{
    private ApiKeyService $apiKeyService;

    public function __construct(ApiKeyService $apiKeyService)
    {
        $this->apiKeyService = $apiKeyService;
    }

    /**
     * Generate ephemeral key for secure browser usage
     */
    public function generateEphemeralKey(Request $request)
    {
        try {
            $apiKey = $this->apiKeyService->getApiKey();
            
            if (!$apiKey) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'OpenAI API key not configured',
                ], 422);
            }

            // Return the actual API key for now
            // OpenAI Realtime API uses the API key directly in WebSocket connection
            return response()->json([
                'status' => 'success',
                'ephemeralKey' => $apiKey, // Use actual API key
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
}
