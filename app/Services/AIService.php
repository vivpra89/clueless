<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AIService
{
    private string $defaultModel = 'gpt-4o-mini';

    /**
     * Send a chat message to the AI
     */
    public function chat(string $message, array $contextSnapshotIds = []): array
    {
        try {
            $apiKey = config('openai.api_key');
            if (empty($apiKey)) {
                throw new \Exception('OpenAI API key is not configured. Please add OPENAI_API_KEY to your .env file.');
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->post('https://api.openai.com/v1/chat/completions', [
                'model' => $this->defaultModel,
                'messages' => [
                    ['role' => 'system', 'content' => 'You are a helpful AI assistant.'],
                    ['role' => 'user', 'content' => $message]
                ],
                'max_tokens' => 1000,
                'temperature' => 0.7,
            ]);

            if (!$response->successful()) {
                throw new \Exception('OpenAI API error: ' . $response->body());
            }

            $data = $response->json();
            
            return [
                'content' => $data['choices'][0]['message']['content'] ?? 'No response',
                'model' => $this->defaultModel,
                'provider' => 'openai',
            ];
        } catch (\Exception $e) {
            Log::error('AI Service error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Transcribe audio to text using OpenAI Whisper
     */
    public function transcribe(string $audioData): array
    {
        try {
            $apiKey = config('openai.api_key');
            if (empty($apiKey)) {
                throw new \Exception('OpenAI API key is not configured. Please add OPENAI_API_KEY to your .env file.');
            }

            // Convert base64 audio to file
            $audioContent = base64_decode($audioData);
            $tempFile = tempnam(sys_get_temp_dir(), 'audio_') . '.webm';
            file_put_contents($tempFile, $audioContent);

            // Send to OpenAI Whisper API
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
            ])->timeout(5)->attach(
                'file', file_get_contents($tempFile), 'audio.webm'
            )->post('https://api.openai.com/v1/audio/transcriptions', [
                'model' => 'whisper-1',
                'language' => 'en',
                'response_format' => 'text',
            ]);

            // Clean up temp file
            unlink($tempFile);

            if (!$response->successful()) {
                throw new \Exception('Whisper API error: ' . $response->body());
            }

            // Response is plain text when using response_format: 'text'
            $text = $response->body();
            
            return [
                'text' => trim($text),
            ];
        } catch (\Exception $e) {
            Log::error('Transcription error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Analyze sales conversation and provide coaching suggestions
     */
    public function analyzeSalesConversation(string $newTranscript, string $context = ''): array
    {
        try {
            $apiKey = config('openai.api_key');
            if (empty($apiKey)) {
                throw new \Exception('OpenAI API key is not configured.');
            }

            $systemPrompt = "You are an expert sales coach AI assistant. Analyze the conversation and provide real-time coaching for the salesperson.

For talking-points, provide COMPLETE, NATURAL SENTENCES that the salesperson can read word-for-word, like a teleprompter. Make them sound conversational and human - not robotic. Include transitions, acknowledgments, and natural flow.

Focus on:
1. Customer objections → Provide empathetic responses with value propositions
2. Buying signals → Give closing scripts
3. Pain points → Offer discovery questions and solutions
4. Silence/lulls → Suggest engaging questions or value statements

Example talking-point scripts:
- \"I completely understand your concern about the budget. Many of our clients felt the same way initially, but they found that the time savings alone - typically 10 hours per week - more than justified the investment.\"
- \"That's a great question about integration. Let me walk you through exactly how seamless the process is - it typically takes less than 30 minutes to set up.\"

Return JSON format:
{
  \"suggestions\": [
    {
      \"type\": \"action\"|\"talking-point\"|\"insight\",
      \"priority\": \"high\"|\"normal\",
      \"title\": \"Brief context (e.g., 'Budget Objection Response')\",
      \"content\": \"Complete teleprompter script for talking-points, or action description\"
    }
  ],
  \"metrics\": {
    \"talkRatio\": 50,
    \"sentiment\": \"positive\"|\"negative\"|\"neutral\",
    \"topics\": [\"key topics discussed\"]
  }
}

Generate 1-2 high-priority talking-points max. Keep scripts concise but complete.";

            $userPrompt = "New transcript:\n$newTranscript\n\nPrevious context:\n$context\n\nAnalyze this sales conversation and provide coaching suggestions.";

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(5)->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-4o-mini',
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $userPrompt]
                ],
                'max_tokens' => 200,
                'temperature' => 0.3,
                'response_format' => ['type' => 'json_object']
            ]);

            if (!$response->successful()) {
                throw new \Exception('OpenAI API error: ' . $response->body());
            }

            $data = $response->json();
            $content = $data['choices'][0]['message']['content'] ?? '{}';
            
            return json_decode($content, true) ?: [
                'suggestions' => [],
                'metrics' => null
            ];
        } catch (\Exception $e) {
            Log::error('Sales analysis error: ' . $e->getMessage());
            return [
                'suggestions' => [],
                'metrics' => null
            ];
        }
    }
}