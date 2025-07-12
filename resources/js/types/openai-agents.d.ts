// OpenAI Agents SDK types
declare module '@openai/agents' {
    export interface AgentConfig {
        name: string;
        instructions?: string;
        tools?: Tool[];
    }

    export interface Tool {
        name: string;
        description: string;
        input_schema: {
            type: string;
            properties: Record<string, any>;
            required?: string[];
        };
        handler: (params: any) => Promise<any>;
    }

    export class RealtimeAgent {
        constructor(config: AgentConfig);
    }
}

declare module '@openai/agents/realtime' {
    import { RealtimeAgent } from '@openai/agents';

    export interface SessionOptions {
        apiKey: string;
        transport?: 'websocket' | 'webrtc' | any;
        model?: string;
    }

    export interface SessionConfig {
        apiKey: string;
        transportMode?: 'websocket' | 'webrtc';
    }

    export class RealtimeSession {
        constructor(agent: RealtimeAgent, options?: SessionOptions);

        connect(config: SessionConfig): Promise<void>;
        disconnect(): Promise<void>;

        on(event: string, handler: (data: any) => void): void;
        off(event: string, handler: (data: any) => void): void;
    }

    export { RealtimeAgent };
}

declare module '@openai/agents/realtime' {
    export interface WebSocketOptions {
        useInsecureApiKey?: boolean;
    }

    export class OpenAIRealtimeWebSocket {
        constructor(options?: WebSocketOptions);
    }

    export { RealtimeAgent, RealtimeSession } from '@openai/agents-realtime';
}
