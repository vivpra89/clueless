<?php

namespace App\Http\Controllers;

use App\Services\ApiKeyService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAIController extends Controller
{
    private ApiKeyService $apiKeyService;

    public function __construct(ApiKeyService $apiKeyService)
    {
        $this->apiKeyService = $apiKeyService;
    }

    /**
     * Handle chat completion requests
     */
    public function chat(Request $request): JsonResponse
    {
        try {
            // Validate request
            $request->validate([
                'messages' => 'required|array',
                'messages.*.role' => 'required|in:user,assistant,system',
                'messages.*.content' => 'required|string',
                'model' => 'string|in:gpt-4o-mini,gpt-4o,gpt-3.5-turbo',
                'max_tokens' => 'integer|min:1|max:4000',
                'temperature' => 'numeric|min:0|max:2',
            ]);

            // Get API key from the service (which provides the key when app starts)
            $apiKey = $this->apiKeyService->getApiKey();
            if (!$apiKey) {
                return response()->json([
                    'error' => 'OpenAI API key not available'
                ], 500);
            }

            // Prepare request to OpenAI
            $openaiRequest = [
                'model' => $request->input('model', 'gpt-4o-mini'),
                'messages' => $request->input('messages'),
                'max_tokens' => $request->input('max_tokens', 500),
                'temperature' => $request->input('temperature', 0.7),
            ];

            // Make request to OpenAI
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->post('https://api.openai.com/v1/chat/completions', $openaiRequest);

            if ($response->successful()) {
                return response()->json($response->json());
            } else {
                Log::error('OpenAI API error', [
                    'status' => $response->status(),
                    'response' => $response->json(),
                ]);

                return response()->json([
                    'error' => 'OpenAI API request failed',
                    'details' => $response->json()
                ], $response->status());
            }

        } catch (\Exception $e) {
            Log::error('OpenAI chat error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Internal server error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check OpenAI API status
     */
    public function status(): JsonResponse
    {
        $apiKey = $this->apiKeyService->getApiKey();
        
        return response()->json([
            'hasApiKey' => !empty($apiKey),
            'configured' => !empty($apiKey),
        ]);
    }
} 