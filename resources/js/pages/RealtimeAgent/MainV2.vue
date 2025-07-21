<template>
    <div
        class="bg-dot-pattern scrollbar-thin scrollbar-thumb-gray-300 dark:scrollbar-thumb-gray-600 scrollbar-track-transparent flex min-h-screen flex-col overflow-y-auto bg-gray-50 text-gray-900 dark:bg-gray-950 dark:text-gray-100"
        :class="{
            'screen-protection-active': isProtectionEnabled,
            'screen-protection-filter': isProtectionEnabled,
            'overlay-mode-active': isOverlayMode,
        }"
    >
        <!-- Screen Protection Indicator -->
        <div v-if="isProtectionEnabled" class="screen-protection-indicator">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
            </svg>
            <span>Screen Recording Blocked</span>
        </div>

        <!-- Professional Navigation Title Bar -->
        <TitleBar 
            title="Clueless"
            @dashboard-click="handleDashboardClick"
            @toggle-session="toggleSession"
        />

        <!-- Mobile Menu Dropdown -->
        <MobileMenu @dashboard-click="handleDashboardClick" />

        <!-- Main Container with scrollable layout -->
        <div class="flex flex-1 flex-col p-4 pb-6 relative">
            <!-- Screen Protection Overlay (content area only) -->
            <div v-if="isProtectionEnabled" class="screen-protection-content-overlay" aria-hidden="true"></div>
            
            <!-- Main Content Area with Responsive Columns -->
            <div class="grid min-h-[calc(100vh-5rem)] grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-3">
                <!-- Column 1: Live Transcription -->
                <LiveTranscription />

                <!-- Column 2: Real-time Intelligence -->
                <div class="col-span-1 flex flex-col gap-3">
                    <!-- Customer Intelligence Card -->
                    <CustomerIntelligence />

                    <!-- Key Insights Card -->
                    <KeyInsights />

                    <!-- Discussion Topics Card -->
                    <DiscussionTopics />

                    <!-- Talking Points Card -->
                    <TalkingPoints />
                </div>

                <!-- Column 3: Contextual & Actions -->
                <div class="col-span-1 flex flex-col gap-3 md:col-span-2 xl:col-span-1">
                    <!-- Contextual Information Card -->
                    <div style="min-height: 150px">
                        <ContextualInformation
                            :prompt="selectedTemplate?.prompt || ''"
                            :conversation-context="conversationContext"
                            :last-customer-message="lastCustomerMessage"
                        />
                    </div>

                    <!-- Commitments Made Card -->
                    <CommitmentsList />

                    <!-- Post-Call Actions Card -->
                    <PostCallActions />
                </div>
            </div>
        </div>

        <!-- Customer Info Modal -->
        <CustomerInfoModal 
            @start-with-info="startWithCustomerInfo"
            @skip="skipCustomerInfo"
        />
    </div>
</template>

<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import { 
    RealtimeAgent,
    RealtimeSession,
    type RealtimeAgentConfiguration,
    type RealtimeSessionOptions,
} from '@openai/agents-realtime';
import { tool } from '@openai/agents-core';
import { z } from 'zod';

// Components
import TitleBar from '@/components/RealtimeAgent/Navigation/TitleBar.vue';
import MobileMenu from '@/components/RealtimeAgent/Navigation/MobileMenu.vue';
import LiveTranscription from '@/components/RealtimeAgent/Content/LiveTranscription.vue';
import CustomerIntelligence from '@/components/RealtimeAgent/Content/CustomerIntelligence.vue';
import KeyInsights from '@/components/RealtimeAgent/Content/KeyInsights.vue';
import DiscussionTopics from '@/components/RealtimeAgent/Content/DiscussionTopics.vue';
import TalkingPoints from '@/components/RealtimeAgent/Content/TalkingPoints.vue';
import CommitmentsList from '@/components/RealtimeAgent/Actions/CommitmentsList.vue';
import PostCallActions from '@/components/RealtimeAgent/Actions/PostCallActions.vue';
import CustomerInfoModal from '@/components/RealtimeAgent/Modals/CustomerInfoModal.vue';
import ContextualInformation from '@/components/ContextualInformation.vue';

// Stores
import { useRealtimeAgentStore } from '@/stores/realtimeAgent';
import { useSettingsStore } from '@/stores/settings';
import { useOpenAIStore } from '@/stores/openai';

// Composables
import { useOverlayMode } from '@/composables/useOverlayMode';
import { useScreenProtection } from '@/composables/useScreenProtection';

// Store instances
const realtimeStore = useRealtimeAgentStore();
const settingsStore = useSettingsStore();
const openaiStore = useOpenAIStore();

// Composables
const overlayMode = useOverlayMode();
const screenProtection = useScreenProtection();

// Set composable support in settings after they initialize
onMounted(() => {
    // Wait for composables to initialize
    setTimeout(() => {
        settingsStore.setOverlaySupported(overlayMode.isSupported.value);
        settingsStore.setProtectionSupported(screenProtection.isProtectionSupported.value);
    }, 200);
});

// Computed
const selectedTemplate = computed(() => realtimeStore.selectedTemplate);
const conversationContext = computed(() => realtimeStore.conversationContext);
const lastCustomerMessage = computed(() => realtimeStore.lastCustomerMessage);
const isProtectionEnabled = computed(() => screenProtection.isProtectionEnabled.value);
const isOverlayMode = computed(() => settingsStore.isOverlayMode);

// Store SDK objects outside of Vue's reactivity to avoid proxy issues
let salespersonAgent: any = null;
let coachAgent: any = null;
let salespersonSession: any = null;
let coachSession: any = null;

// Audio capture
let audioCapture: any = null;
let currentAudioData = ref<ArrayBuffer | null>(null);
let audioContext: AudioContext | null = null;
let micStream: MediaStream | null = null;
let sessionsReady = false;

// Tool definitions using SDK
const coachingTools = [
    tool({
        name: 'track_discussion_topic',
        description: 'Track or update a discussion topic',
        parameters: z.object({
            name: z.string().describe('Topic name (e.g., "Pricing", "Integration", "Timeline")'),
            sentiment: z.enum(['positive', 'negative', 'neutral', 'mixed']).describe('Sentiment of the discussion'),
            context: z.string().nullable().describe('Brief context about the discussion'),
        }),
        execute: async (args: { name: string; sentiment: string; context?: string | null }) => {
            handleFunctionCall('track_discussion_topic', args);
            return `Tracked topic: ${args.name} with ${args.sentiment} sentiment`;
        },
    }),
    
    tool({
        name: 'detect_commitment',
        description: 'Log a commitment made by either party',
        parameters: z.object({
            speaker: z.enum(['salesperson', 'customer']).describe('Who made the commitment'),
            text: z.string().describe('What was committed'),
            type: z.enum(['promise', 'next_step', 'deliverable']).describe('Type of commitment'),
            deadline: z.string().nullable().describe('When it should be done'),
            context: z.string().nullable().describe('Additional context'),
        }),
        execute: async (args: { speaker: string; text: string; type: string; deadline?: string | null; context?: string | null }) => {
            handleFunctionCall('detect_commitment', args);
            return `Captured ${args.speaker}'s commitment: ${args.text}`;
        },
    }),
    
    tool({
        name: 'analyze_customer_intent',
        description: 'Update customer intelligence analysis',
        parameters: z.object({
            intent: z.enum(['research', 'evaluation', 'decision', 'implementation', 'unknown']).describe('Customer intent'),
            buyingStage: z.string().nullable().describe('Current stage in buying process'),
            sentiment: z.enum(['positive', 'negative', 'neutral']).describe('Overall sentiment'),
            engagementLevel: z.number().min(0).max(100).describe('Engagement level percentage'),
        }),
        execute: async (args: { intent: string; buyingStage?: string | null; sentiment: string; engagementLevel: number }) => {
            handleFunctionCall('analyze_customer_intent', args);
            return `Updated customer intelligence`;
        },
    }),
    
    tool({
        name: 'highlight_insight',
        description: 'Capture a key insight from the conversation',
        parameters: z.object({
            type: z.enum(['pain_point', 'objection', 'positive_signal', 'concern', 'question']).describe('Type of insight'),
            text: z.string().describe('The insight text'),
            importance: z.enum(['high', 'medium', 'low']).describe('Importance level'),
        }),
        execute: async (args: { type: string; text: string; importance: string }) => {
            handleFunctionCall('highlight_insight', args);
            return `Captured ${args.type}: ${args.text}`;
        },
    }),
    
    tool({
        name: 'create_action_item',
        description: 'Create a post-call action item',
        parameters: z.object({
            text: z.string().describe('What needs to be done'),
            owner: z.enum(['salesperson', 'customer', 'both']).describe('Who owns this action'),
            type: z.enum(['follow_up', 'send_info', 'schedule', 'internal']).describe('Type of action'),
            deadline: z.string().nullable().describe('When it should be done'),
            relatedCommitment: z.string().nullable().describe('ID of related commitment if any'),
        }),
        execute: async (args: { text: string; owner: string; type: string; deadline?: string | null; relatedCommitment?: string | null }) => {
            handleFunctionCall('create_action_item', args);
            return `Created action item: ${args.text}`;
        },
    }),
    
    tool({
        name: 'detect_information_need',
        description: 'Detect when customer is asking about or discussing topics that require specific information',
        parameters: z.object({
            topic: z.string().describe('The main topic being discussed (e.g., "pricing", "features", "integration")'),
            context: z.string().describe('The specific context or question from the customer'),
            urgency: z.enum(['high', 'medium', 'low']).describe('How urgently the information is needed'),
        }),
        execute: async (args: { topic: string; context: string; urgency: string }) => {
            handleFunctionCall('detect_information_need', args);
            return `Detected information need about ${args.topic}`;
        },
    }),
];

// Function call handler (same as original)
const handleFunctionCall = (name: string, args: any) => {
    console.log('🔧 Handling function call:', name, args);
    
    switch (name) {
        case 'track_discussion_topic':
            realtimeStore.trackDiscussionTopic(args.name, args.sentiment, args.context);
            break;
            
        case 'analyze_customer_intent':
            realtimeStore.updateCustomerIntelligence({
                intent: args.intent,
                buyingStage: args.buyingStage || realtimeStore.customerIntelligence.buyingStage,
                sentiment: args.sentiment,
                engagementLevel: args.engagementLevel,
            });
            break;
            
        case 'highlight_insight':
            realtimeStore.addKeyInsight(args.type, args.text, args.importance);
            break;
            
        case 'detect_commitment':
            realtimeStore.captureCommitment(args.speaker, args.text, args.type, args.deadline);
            break;
            
        case 'create_action_item':
            realtimeStore.addActionItem(args.text, args.owner, args.type, args.deadline, args.relatedCommitment);
            break;
            
        case 'detect_information_need':
            console.log('📋 Information need detected:', args);
            // Could update conversation context or trigger specific responses
            realtimeStore.setConversationContext(`Customer asking about ${args.topic}: ${args.context}`);
            break;
    }
};

// Initialize function
const initialize = async () => {
    try {
        // Fetch templates
        const { data } = await axios.get('/templates');
        realtimeStore.setTemplates(data);
        
        // Select first template by default
        if (data.length > 0 && !realtimeStore.selectedTemplate) {
            realtimeStore.setSelectedTemplate(data[0]);
        }
    } catch (error) {
        console.error('Failed to fetch templates:', error);
    }
};

// Session management
const toggleSession = () => {
    if (realtimeStore.isActive) {
        endCall();
    } else {
        realtimeStore.setShowCustomerModal(true);
    }
};

const startWithCustomerInfo = (info: { name: string; company: string }) => {
    realtimeStore.setCustomerInfo(info);
    startCall();
};

const skipCustomerInfo = () => {
    startCall();
};

const startCall = async () => {
    try {
        realtimeStore.setConnectionStatus('connecting');
        
        // Get ephemeral key
        const { data } = await axios.post('/api/realtime/ephemeral-key');
        
        if (data.status === 'error') {
            throw new Error(data.message || 'Failed to get ephemeral key');
        }
        
        openaiStore.setEphemeralKey(data.ephemeralKey, 60);
        
        // Initialize agents
        await initializeAgents(data.ephemeralKey);
        
        // Start audio capture
        console.log('🎤 Starting audio capture...');
        await startAudioCapture();
        
        realtimeStore.setActiveState(true);
        realtimeStore.setConnectionStatus('connected');
        
        // Auto-enable screen protection during calls
        screenProtection.enableForCall();
        
        // Test audio connection
        setTimeout(() => {
            console.log('🔍 Audio capture status check:', {
                isActive: realtimeStore.isActive,
                microphoneStatus: realtimeStore.microphoneStatus,
                systemAudioActive: realtimeStore.systemAudioActive,
                audioLevel: realtimeStore.audioLevel,
                systemAudioLevel: realtimeStore.systemAudioLevel,
            });
        }, 3000);
        
        // Add initial system message
        realtimeStore.addTranscriptGroup({
            id: `system-${Date.now()}`,
            role: 'system',
            messages: [{ text: 'Call started. Coach is ready to assist.', timestamp: Date.now() }],
            startTime: Date.now(),
        });
        
    } catch (error) {
        console.error('Failed to start call:', error);
        realtimeStore.setConnectionStatus('disconnected');
    }
};

const endCall = async () => {
    try {
        // Stop audio capture
        if (audioCapture) {
            await audioCapture.stop();
            audioCapture = null;
        }
        
        // Stop microphone stream
        if (micStream) {
            micStream.getTracks().forEach(track => track.stop());
            micStream = null;
        }
        
        // Close audio context
        if (audioContext) {
            await audioContext.close();
            audioContext = null;
        }
        
        // Disconnect sessions directly to avoid Vue proxy issues
        if (salespersonSession) {
            try {
                salespersonSession.close();
            } catch (error) {
                console.error('Error closing salesperson session:', error);
            }
            salespersonSession = null;
        }
        
        if (coachSession) {
            try {
                coachSession.close();
            } catch (error) {
                console.error('Error closing coach session:', error);
            }
            coachSession = null;
        }
        
        // Clear agents
        salespersonAgent = null;
        coachAgent = null;
        
        // Reset sessions ready flag
        sessionsReady = false;
        
        // Clear from store
        openaiStore.clearAgents();
        
        realtimeStore.setActiveState(false);
        realtimeStore.setConnectionStatus('disconnected');
        
        // Add end message
        realtimeStore.addTranscriptGroup({
            id: `system-${Date.now()}`,
            role: 'system',
            messages: [{ text: 'Call ended.', timestamp: Date.now() }],
            startTime: Date.now(),
        });
        
    } catch (error) {
        console.error('Failed to end call:', error);
    }
};

const initializeAgents = async (apiKey: string) => {
    // Create salesperson agent (transcription only)
    const salespersonConfig: RealtimeAgentConfiguration = {
        name: 'Salesperson',
        description: 'Transcribes the salesperson audio',
        instructions: 'You are a transcription assistant. Your only job is to transcribe what the salesperson says.',
        tools: [],
    };
    
    salespersonAgent = new RealtimeAgent(salespersonConfig);
    salespersonSession = new RealtimeSession(salespersonAgent, {
        transport: 'websocket',
        model: 'gpt-4o-mini-realtime-preview-2024-12-17',
        apiKey: apiKey,
    });
    
    // Create coach agent with tools
    const coachConfig: RealtimeAgentConfiguration = {
        name: 'Customer Coach',
        description: 'Analyzes conversations and provides real-time coaching',
        instructions: selectedTemplate.value?.prompt || 'You are a helpful sales coach.',
        tools: coachingTools,
    };
    
    coachAgent = new RealtimeAgent(coachConfig);
    coachSession = new RealtimeSession(coachAgent, {
        transport: 'websocket',
        model: 'gpt-4o-mini-realtime-preview-2024-12-17',
        apiKey: apiKey,
    });
    
    // Set up event handlers
    setupSessionHandlers();
    
    // Connect sessions
    await salespersonSession.connect({});
    await coachSession.connect({});
    
    // Configure sessions after connection
    console.log('🔧 Configuring sessions for audio...');
    
    // Configure salesperson session for transcription
    if (salespersonSession?.transport) {
        salespersonSession.transport.sendEvent({
            type: 'session.update',
            session: {
                modalities: ['audio', 'text'],
                instructions: 'You are a transcription assistant. Your only job is to transcribe what you hear.',
                input_audio_format: 'pcm16',
                output_audio_format: 'pcm16',
                input_audio_transcription: {
                    model: 'whisper-1',
                },
                turn_detection: {
                    type: 'server_vad',
                    threshold: 0.5,
                    prefix_padding_ms: 300,
                    silence_duration_ms: 200,
                },
                voice: 'alloy',
                temperature: 0.8,
                max_output_tokens: 4096,
            },
        });
        console.log('✅ Salesperson session configured for audio transcription');
    }
    
    // Configure coach session with tools
    if (coachSession?.transport) {
        const coachInstructions = selectedTemplate.value?.prompt || 'You are a helpful sales coach.';
        
        // Convert tools to the format expected by OpenAI
        const toolDefinitions = coachingTools.map(tool => ({
            type: 'function',
            function: {
                name: tool.name,
                description: tool.description,
                parameters: tool.parameters,
            }
        }));
        
        coachSession.transport.sendEvent({
            type: 'session.update',
            session: {
                modalities: ['audio', 'text'],
                instructions: coachInstructions,
                input_audio_format: 'pcm16',
                output_audio_format: 'pcm16',
                input_audio_transcription: {
                    model: 'whisper-1',
                },
                turn_detection: {
                    type: 'server_vad',
                    threshold: 0.5,
                    prefix_padding_ms: 300,
                    silence_duration_ms: 200,
                },
                voice: 'alloy',
                temperature: 0.8,
                max_output_tokens: 4096,
                tools: toolDefinitions,
                tool_choice: 'auto',
            },
        });
        console.log('✅ Coach session configured with tools:', toolDefinitions.length);
    }
    
    // Mark sessions as ready
    sessionsReady = true;
    console.log('✅ All sessions configured and ready for audio');
    
    // Store in OpenAI store
    openaiStore.setSalespersonAgent(salespersonAgent, salespersonSession);
    openaiStore.setCoachAgent(coachAgent, coachSession);
};

const setupSessionHandlers = () => {
    if (!salespersonSession || !coachSession) return;
    
    // Debug logging to understand session structure
    console.log('Setting up session handlers...');
    console.log('Salesperson session:', salespersonSession);
    console.log('Coach session:', coachSession);
    
    // Listen for all events on transport layer for debugging
    if (salespersonSession.transport) {
        salespersonSession.transport.on('*', (event: any) => {
            if (event.type !== 'input_audio_buffer.append') { // Don't log audio append events
                console.log('Salesperson transport event:', event.type, event);
            }
        });
    }
    
    if (coachSession.transport) {
        coachSession.transport.on('*', (event: any) => {
            if (event.type !== 'input_audio_buffer.append') { // Don't log audio append events
                console.log('Coach transport event:', event.type, event);
            }
        });
    }
    
    // Salesperson session handlers (transcription)
    salespersonSession.on('conversation.updated', (event: any) => {
        console.log('Salesperson conversation updated:', event);
        const { item } = event;
        if (item.role === 'user' && item.formatted?.transcript) {
            // Add salesperson transcript
            const groupId = `salesperson-${Date.now()}`;
            realtimeStore.addTranscriptGroup({
                id: groupId,
                role: 'salesperson',
                messages: [{ text: item.formatted.transcript, timestamp: Date.now() }],
                startTime: Date.now(),
            });
            
            // Forward to coach for analysis
            if (coachSession && coachSession.transport) {
                coachSession.transport.sendEvent({
                    type: 'conversation.item.create',
                    item: {
                        type: 'message',
                        role: 'user',
                        content: [{
                            type: 'input_text',
                            text: `Salesperson said: "${item.formatted.transcript}"`
                        }]
                    }
                });
            }
        }
    });
    
    // Coach session handlers
    coachSession.on('conversation.updated', (event: any) => {
        console.log('Coach conversation updated:', event);
        const { item } = event;
        
        // Handle customer transcripts
        if (item.role === 'user' && item.formatted?.transcript) {
            const text = item.formatted.transcript;
            
            // Check if it's a customer message (not forwarded from salesperson)
            if (!text.startsWith('Salesperson said:')) {
                const groupId = `customer-${Date.now()}`;
                realtimeStore.addTranscriptGroup({
                    id: groupId,
                    role: 'customer',
                    messages: [{ text, timestamp: Date.now() }],
                    startTime: Date.now(),
                });
                
                // Update last customer message
                realtimeStore.setLastCustomerMessage(text);
            }
        }
    });
    
    // Handle transcription events
    if (salespersonSession.transport) {
        salespersonSession.transport.on('conversation.item.input_audio_transcription.completed', (event: any) => {
            console.log('Salesperson transcription completed:', event);
            if (event.transcript) {
                const groupId = `salesperson-${Date.now()}`;
                realtimeStore.addTranscriptGroup({
                    id: groupId,
                    role: 'salesperson',
                    messages: [{ text: event.transcript, timestamp: Date.now() }],
                    startTime: Date.now(),
                });
                
                // Forward to coach for analysis
                if (coachSession && coachSession.transport) {
                    coachSession.transport.sendEvent({
                        type: 'conversation.item.create',
                        item: {
                            type: 'message',
                            role: 'user',
                            content: [{
                                type: 'input_text',
                                text: `Salesperson said: "${event.transcript}"`
                            }]
                        }
                    });
                }
            }
        });
    }
    
    if (coachSession.transport) {
        coachSession.transport.on('conversation.item.input_audio_transcription.completed', (event: any) => {
            console.log('Customer transcription completed:', event);
            if (event.transcript) {
                const groupId = `customer-${Date.now()}`;
                realtimeStore.addTranscriptGroup({
                    id: groupId,
                    role: 'customer',
                    messages: [{ text: event.transcript, timestamp: Date.now() }],
                    startTime: Date.now(),
                });
                
                // Update last customer message
                realtimeStore.setLastCustomerMessage(event.transcript);
            }
        });
    }
    
    if (coachSession.transport) {
        coachSession.transport.on('conversation.item.input_audio_transcription.completed', (event: any) => {
            console.log('Coach transcription completed:', event);
            if (event.transcript) {
                const groupId = `customer-${Date.now()}`;
                realtimeStore.addTranscriptGroup({
                    id: groupId,
                    role: 'customer',
                    messages: [{ text: event.transcript, timestamp: Date.now() }],
                    startTime: Date.now(),
                });
                
                realtimeStore.setLastCustomerMessage(event.transcript);
            }
        });
    }
    
    // Error handlers
    salespersonSession.on('error', (error: any) => {
        console.error('Salesperson session error:', error);
        realtimeStore.addTranscriptGroup({
            id: `error-${Date.now()}`,
            role: 'system',
            messages: [{ text: `Salesperson connection error: ${error.message || JSON.stringify(error)}`, timestamp: Date.now() }],
            startTime: Date.now(),
            systemCategory: 'error',
        });
    });
    
    coachSession.on('error', (error: any) => {
        console.error('Coach session error:', error);
        realtimeStore.addTranscriptGroup({
            id: `error-${Date.now()}`,
            role: 'system',
            messages: [{ text: `Coach connection error: ${error.message || JSON.stringify(error)}`, timestamp: Date.now() }],
            startTime: Date.now(),
            systemCategory: 'error',
        });
    });
};

const startAudioCapture = async () => {
    try {
        console.log('🎤 Setting up dual audio capture...');
        
        // Setup microphone capture for salesperson's voice
        micStream = await navigator.mediaDevices.getUserMedia({
            audio: {
                sampleRate: 24000,
                channelCount: 1,
                echoCancellation: true,
                noiseSuppression: true,
                autoGainControl: true,
            },
        });
        realtimeStore.setMicrophoneStatus('active');
        console.log('✅ Microphone access granted (Salesperson audio)');
        
        // Create audio context with 24kHz sample rate (required by OpenAI)
        audioContext = new AudioContext({ sampleRate: 24000 });
        
        // Resume audio context if it's suspended
        if (audioContext.state === 'suspended') {
            await audioContext.resume();
            console.log('✅ Audio context resumed');
        }
        
        // Setup microphone processor
        const micSource = audioContext.createMediaStreamSource(micStream);
        const micProcessor = audioContext.createScriptProcessor(4096, 1, 1);
        
        micProcessor.onaudioprocess = (event) => {
            if (realtimeStore.isActive && sessionsReady) {
                const inputData = event.inputBuffer.getChannelData(0);
                
                // Update audio level for visualization
                const sum = inputData.reduce((acc, val) => acc + Math.abs(val), 0);
                realtimeStore.setAudioLevel(Math.min(100, (sum / inputData.length) * 500));
                
                // Convert to PCM16 and send audio
                const pcm16 = convertFloat32ToPCM16(inputData);
                
                // Send to salesperson session
                if (salespersonSession && salespersonSession.transport) {
                    const base64Audio = arrayBufferToBase64(pcm16.buffer);
                    try {
                        salespersonSession.transport.sendEvent({
                            type: 'input_audio_buffer.append',
                            audio: base64Audio,
                        });
                        
                        // Log occasionally for debugging
                        if (Math.random() < 0.02) {
                            console.log('🎤 Sent audio to salesperson session', {
                                audioLength: base64Audio.length,
                                samples: pcm16.length,
                                sessionsReady,
                                transportConnected: !!salespersonSession.transport,
                            });
                        }
                    } catch (error) {
                        console.error('Failed to send audio to salesperson:', error);
                    }
                }
            } else if (!sessionsReady && Math.random() < 0.1) {
                console.log('⏳ Audio ready but sessions not configured yet');
            }
        };
        
        micSource.connect(micProcessor);
        micProcessor.connect(audioContext.destination);
        
        // Try to setup system audio capture using Swift helper
        try {
            // Dynamic import to avoid SSR issues
            const { SystemAudioCapture, isSystemAudioAvailable } = await import('@/services/audioCapture');
            
            // Check if system audio capture is available
            const isAvailable = await isSystemAudioAvailable();
            console.log('🔍 System audio available check result:', isAvailable);
            
            if (isAvailable) {
                console.log('🔊 System audio capture available, starting...');
                
                audioCapture = new SystemAudioCapture();
                
                // Check permission first
                const hasPermission = await audioCapture.checkPermission();
                console.log('🔒 Screen recording permission:', hasPermission ? 'granted' : 'denied');
                
                // Handle audio data from system
                audioCapture.on('audio', (pcm16: Int16Array) => {
                    if (realtimeStore.isActive && sessionsReady) {
                        // Update system audio level
                        const sum = Array.from(pcm16).reduce((acc, val) => acc + Math.abs(val), 0);
                        realtimeStore.setSystemAudioLevel(Math.min(100, (sum / pcm16.length) * 0.5));
                        
                        // Send to coach session
                        if (coachSession && coachSession.transport) {
                            const base64Audio = arrayBufferToBase64(pcm16.buffer);
                            try {
                                coachSession.transport.sendEvent({
                                    type: 'input_audio_buffer.append',
                                    audio: base64Audio,
                                });
                                
                                // Log occasionally for debugging
                                if (Math.random() < 0.05) {
                                    console.log('📞 Sent system audio to coach session', {
                                        audioLength: base64Audio.length,
                                        samples: pcm16.length,
                                        level: realtimeStore.systemAudioLevel,
                                        sessionsReady,
                                        transportConnected: !!coachSession.transport,
                                    });
                                }
                            } catch (error) {
                                console.error('Failed to send audio to coach:', error);
                            }
                        }
                    }
                });
                
                // Handle status updates
                audioCapture.on('status', (state: string) => {
                    console.log('🔊 System audio status:', state);
                    
                    // Map status states to our UI states
                    switch (state) {
                        case 'capturing':
                            realtimeStore.setSystemAudioActive(true);
                            break;
                        case 'stopped':
                            realtimeStore.setSystemAudioActive(false);
                            break;
                        case 'retrying':
                        case 'restarting':
                            realtimeStore.addTranscriptGroup({
                                id: `system-${Date.now()}`,
                                role: 'system',
                                messages: [{ text: `🔄 System audio is ${state}...`, timestamp: Date.now() }],
                                startTime: Date.now(),
                                systemCategory: 'warning',
                            });
                            break;
                    }
                });
                
                // Handle errors
                audioCapture.on('error', (error: Error) => {
                    console.error('❌ System audio error:', error);
                    realtimeStore.setSystemAudioActive(false);
                    
                    // Check if it's a permission error
                    if (error.message.includes('Screen recording permission')) {
                        realtimeStore.addTranscriptGroup({
                            id: `system-${Date.now()}`,
                            role: 'system',
                            messages: [
                                { text: '🔒 Screen Recording Permission Required', timestamp: Date.now() },
                                { text: '1. System Preferences should open automatically', timestamp: Date.now() },
                                { text: '2. Enable Screen Recording for this app', timestamp: Date.now() },
                                { text: '3. Restart the session after granting permission', timestamp: Date.now() },
                            ],
                            startTime: Date.now(),
                            systemCategory: 'error',
                        });
                    } else {
                        realtimeStore.addTranscriptGroup({
                            id: `system-${Date.now()}`,
                            role: 'system',
                            messages: [{ text: `⚠️ System audio error: ${error.message}`, timestamp: Date.now() }],
                            startTime: Date.now(),
                            systemCategory: 'error',
                        });
                    }
                });
                
                // Start capture
                await audioCapture.start();
                
                realtimeStore.addTranscriptGroup({
                    id: `system-${Date.now()}`,
                    role: 'system',
                    messages: [{ text: '✅ Dual audio capture active: Microphone (You) + System Audio (Customer)', timestamp: Date.now() }],
                    startTime: Date.now(),
                    systemCategory: 'success',
                });
            } else {
                throw new Error('System audio capture not available');
            }
        } catch (error) {
            console.warn('⚠️ System audio capture not available:', error);
            realtimeStore.setSystemAudioActive(false);
            
            // Fallback to mixed audio mode
            realtimeStore.addTranscriptGroup({
                id: `system-${Date.now()}`,
                role: 'system',
                messages: [
                    { text: '⚠️ System audio capture unavailable. Using mixed audio mode.', timestamp: Date.now() },
                    { text: '• Use headphones to reduce echo', timestamp: Date.now() },
                    { text: '• AI will identify speakers based on conversation context', timestamp: Date.now() },
                ],
                startTime: Date.now(),
                systemCategory: 'warning',
            });
        }
        
        console.log('✅ Audio pipeline setup complete');
        
    } catch (error) {
        console.error('❌ Failed to setup audio:', error);
        realtimeStore.setMicrophoneStatus('error');
        throw error;
    }
};

// Helper function to convert Float32Array to Int16Array
const convertFloat32ToPCM16 = (float32Array: Float32Array): Int16Array => {
    const pcm16 = new Int16Array(float32Array.length);
    for (let i = 0; i < float32Array.length; i++) {
        const clamped = Math.max(-1, Math.min(1, float32Array[i]));
        pcm16[i] = Math.floor(clamped * 32767);
    }
    return pcm16;
};

// Helper function to convert ArrayBuffer to base64
const arrayBufferToBase64 = (buffer: ArrayBuffer): string => {
    const uint8Array = new Uint8Array(buffer);
    let base64Audio = '';
    const chunkSize = 8192; // Process in 8KB chunks
    
    for (let i = 0; i < uint8Array.length; i += chunkSize) {
        const chunk = uint8Array.slice(i, Math.min(i + chunkSize, uint8Array.length));
        base64Audio += btoa(String.fromCharCode(...chunk));
    }
    
    return base64Audio;
};

const calculateAudioLevel = (audioData: ArrayBuffer): number => {
    const dataArray = new Int16Array(audioData);
    let sum = 0;
    for (let i = 0; i < dataArray.length; i++) {
        sum += Math.abs(dataArray[i]);
    }
    const average = sum / dataArray.length;
    const normalized = average / 32768;
    return Math.min(100, normalized * 500);
};

const handleDashboardClick = () => {
    if (!realtimeStore.isActive) {
        router.visit('/conversations');
    }
};

// Watch for template changes
watch(selectedTemplate, (newTemplate) => {
    if (newTemplate && coachSession?.transport) {
        // Update coach instructions via transport
        coachSession.transport.sendEvent({
            type: 'session.update',
            session: {
                instructions: newTemplate.prompt,
            },
        });
    }
});

// Lifecycle
onMounted(() => {
    initialize();
    
    // Close dropdowns on outside click
    document.addEventListener('click', () => {
        settingsStore.closeAllDropdowns();
    });
});

onUnmounted(async () => {
    if (realtimeStore.isActive) {
        await endCall();
    }
    
    // Clean up
    openaiStore.reset();
    document.removeEventListener('click', settingsStore.closeAllDropdowns);
});
</script>

<style>
/* V2 Badge - for testing */
.title-bar::after {
    content: 'V2';
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    background: #3b82f6;
    color: white;
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 10px;
    font-weight: 600;
}

/* Apple-style Glassmorphism */
.glass-card {
    background: rgba(255, 255, 255, 0.7);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.3);
    box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.15);
}

.glass-card-darker {
    background: rgba(255, 255, 255, 0.5);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.2);
}

/* Clean, minimal transitions */
.glass-card {
    transition: all 0.3s ease;
}

.glass-card:hover {
    box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.25);
    transform: translateY(-1px);
}

/* Dark mode glass cards */
:is(.dark *) .glass-card {
    background: rgba(0, 0, 0, 0.4);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.3);
}

:is(.dark *) .glass-card-darker {
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.05);
    box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.5);
}

/* Custom scrollbar styles for better visibility */
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}

.custom-scrollbar::-webkit-scrollbar-track {
    background: rgba(0, 0, 0, 0.1);
    border-radius: 3px;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(139, 92, 246, 0.3);
    border-radius: 3px;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: rgba(139, 92, 246, 0.5);
}

/* Dark mode scrollbar */
:is(.dark *) .custom-scrollbar::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.1);
}

:is(.dark *) .custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(139, 92, 246, 0.5);
}

:is(.dark *) .custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: rgba(139, 92, 246, 0.7);
}

/* Subtle animations */
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(-5px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-fadeIn {
    animation: fadeIn 0.2s ease-out;
}
</style>