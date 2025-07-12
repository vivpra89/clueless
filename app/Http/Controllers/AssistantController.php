<?php

namespace App\Http\Controllers;

use App\Services\AIService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AssistantController extends Controller
{
    public function __construct(
        private AIService $aiService
    ) {}

    public function chat(Request $request)
    {
        $request->validate([
            'message' => 'required|string',
        ]);

        try {
            // Get AI response
            $response = $this->aiService->chat(
                $request->input('message'),
                []
            );

            return response()->json([
                'response' => $response['content'] ?? 'I apologize, but I was unable to generate a response.',
            ]);
        } catch (\Exception $e) {
            Log::error('Chat error: ' . $e->getMessage());

            return response()->json([
                'error' => 'Failed to get AI response'
            ], 500);
        }
    }

    public function transcribe(Request $request)
    {
        $request->validate([
            'audio' => 'required|string',
        ]);

        try {
            $audioData = $request->input('audio');
            
            // Use OpenAI Whisper for transcription
            $result = $this->aiService->transcribe($audioData);

            return response()->json([
                'text' => $result['text'] ?? '',
            ]);
        } catch (\Exception $e) {
            Log::error('Transcription error: ' . $e->getMessage());

            return response()->json([
                'error' => 'Failed to transcribe audio'
            ], 500);
        }
    }

    public function analyzeConversation(Request $request)
    {
        $request->validate([
            'audio' => 'required|string',
            'context' => 'nullable|string',
            'mode' => 'required|in:sales-coaching',
        ]);

        try {
            $audioData = $request->input('audio');
            $context = $request->input('context', '');
            
            // Ensure context is never null
            if (is_null($context)) {
                $context = '';
            }
            
            // First, transcribe the audio
            $transcriptionResult = $this->aiService->transcribe($audioData);
            $transcript = $transcriptionResult['text'] ?? '';

            if (empty($transcript)) {
                return response()->json([
                    'transcript' => '',
                    'suggestions' => [],
                    'metrics' => null
                ]);
            }

            // Analyze the conversation for sales coaching
            $analysis = $this->aiService->analyzeSalesConversation($transcript, $context);

            return response()->json([
                'transcript' => $transcript,
                'suggestions' => $analysis['suggestions'] ?? [],
                'metrics' => $analysis['metrics'] ?? null
            ]);
        } catch (\Exception $e) {
            Log::error('Conversation analysis error: ' . $e->getMessage());

            return response()->json([
                'error' => 'Failed to analyze conversation'
            ], 500);
        }
    }

    public function analyzeConversationStream(Request $request)
    {
        $request->validate([
            'audio' => 'required|string',
            'context' => 'nullable|string',
            'mode' => 'required|in:sales-coaching',
            'sessionId' => 'nullable|string',
        ]);

        return response()->stream(function () use ($request) {
            // Set up SSE headers
            header('Content-Type: text/event-stream');
            header('Cache-Control: no-cache');
            header('Connection: keep-alive');
            header('X-Accel-Buffering: no'); // Disable Nginx buffering
            
            // Send initial connection event
            echo "data: " . json_encode(['type' => 'connected']) . "\n\n";
            ob_flush();
            flush();

            try {
                $audioData = $request->input('audio');
                $context = $request->input('context', '');
                $sessionId = $request->input('sessionId');
                
                // Ensure context is never null
                if (is_null($context)) {
                    $context = '';
                }
                
                // Send processing event
                echo "data: " . json_encode([
                    'type' => 'processing',
                    'sessionId' => $sessionId,
                    'timestamp' => now()->toIso8601String()
                ]) . "\n\n";
                ob_flush();
                flush();
                
                // Transcribe the audio chunk
                $transcriptionResult = $this->aiService->transcribe($audioData);
                $transcript = $transcriptionResult['text'] ?? '';
                
                if (!empty($transcript)) {
                    // Send transcription event
                    echo "data: " . json_encode([
                        'type' => 'transcription',
                        'text' => $transcript,
                        'sessionId' => $sessionId,
                        'timestamp' => now()->toIso8601String()
                    ]) . "\n\n";
                    ob_flush();
                    flush();
                    
                    // Analyze the conversation
                    $analysis = $this->aiService->analyzeSalesConversation($transcript, $context);
                    
                    // Send analysis event
                    echo "data: " . json_encode([
                        'type' => 'analysis',
                        'suggestions' => $analysis['suggestions'] ?? [],
                        'metrics' => $analysis['metrics'] ?? null,
                        'sessionId' => $sessionId,
                        'timestamp' => now()->toIso8601String()
                    ]) . "\n\n";
                    ob_flush();
                    flush();
                } else {
                    // Send empty transcription event
                    echo "data: " . json_encode([
                        'type' => 'transcription',
                        'text' => '',
                        'sessionId' => $sessionId,
                        'timestamp' => now()->toIso8601String()
                    ]) . "\n\n";
                    ob_flush();
                    flush();
                }
                
            } catch (\Exception $e) {
                // Send error event
                Log::error('Stream processing error: ' . $e->getMessage());
                echo "data: " . json_encode([
                    'type' => 'error',
                    'message' => 'Processing error occurred',
                    'error' => $e->getMessage(),
                    'timestamp' => now()->toIso8601String()
                ]) . "\n\n";
                ob_flush();
                flush();
            }
            
            // Send completion event
            echo "data: " . json_encode([
                'type' => 'complete',
                'timestamp' => now()->toIso8601String()
            ]) . "\n\n";
            ob_flush();
            flush();
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }
}