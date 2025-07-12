// OpenAI Realtime API types
declare module '@openai/realtime-api-beta' {
    export class RealtimeClient {
        constructor(options: { apiKey?: string; url?: string; dangerouslyAllowAPIKeyInBrowser?: boolean });

        connect(): Promise<void>;
        disconnect(): void;

        on(event: string, handler: (data: any) => void): void;
        off(event: string, handler: (data: any) => void): void;

        updateSession(options: {
            instructions?: string;
            voice?: string;
            turn_detection?: {
                type: string;
                threshold?: number;
                prefix_padding_ms?: number;
                silence_duration_ms?: number;
            };
            tools?: any[];
            input_audio_transcription?: {
                model: string;
            };
        }): Promise<void>;

        appendInputAudio(audio: Int16Array | ArrayBuffer): void;
        createResponse(): void;
        cancelResponse(id: string, sampleCount: number): void;

        sendUserMessageContent(
            content: Array<{
                type: string;
                text?: string;
                audio?: Int16Array;
            }>,
        ): void;

        conversation: {
            getItems(): any[];
        };

        realtime: {
            send(event: string, data: any): void;
        };
    }
}

// Extend Window interface for NativePHP
interface Window {
    Native?: {
        minimize(): void;
    };
}
