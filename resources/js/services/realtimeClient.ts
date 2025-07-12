import { RealtimeClient } from '@openai/realtime-api-beta';

export async function createRealtimeClient() {
    // In development, we'll use the API key directly
    // In production, this should connect to a relay server
    if (import.meta.env.DEV) {
        const apiKey = import.meta.env.VITE_OPENAI_API_KEY;

        if (!apiKey) {
            throw new Error('VITE_OPENAI_API_KEY is not set in environment variables');
        }

        console.log('Creating RealtimeClient with API key (dev mode)');

        // In production, use a relay server URL instead
        return new RealtimeClient({
            apiKey: apiKey,
            dangerouslyAllowAPIKeyInBrowser: true,
        });
    } else {
        // Production: connect to relay server
        const relayUrl = import.meta.env.VITE_REALTIME_RELAY_URL || 'wss://your-relay-server.com';
        console.log('Creating RealtimeClient with relay URL:', relayUrl);

        return new RealtimeClient({
            url: relayUrl,
        });
    }
}
