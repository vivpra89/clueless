<template>
    <div
        class="bg-dot-pattern scrollbar-thin scrollbar-thumb-gray-300 dark:scrollbar-thumb-gray-600 scrollbar-track-transparent flex min-h-screen flex-col overflow-y-auto bg-gray-50 text-gray-900 dark:bg-gray-950 dark:text-gray-100"
        :class="{
            'screen-protection-active': isProtectionEnabled,
            'screen-protection-filter': isProtectionEnabled,
            'overlay-mode-active': isOverlayMode,
        }"
    >
        <!-- Screen Protection Overlay -->
        <div v-if="isProtectionEnabled" class="screen-protection-overlay" aria-hidden="true"></div>

        <!-- Professional Navigation Title Bar -->
        <TitleBar 
            title="Clueless"
            @dashboard-click="handleDashboardClick"
            @toggle-session="toggleSession"
        />

        <!-- Mobile Menu Dropdown -->
        <MobileMenu @dashboard-click="handleDashboardClick" />

        <!-- Main Container with scrollable layout -->
        <div class="flex flex-1 flex-col p-4 pb-6">
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

// Set composable support in settings
settingsStore.setOverlaySupported(overlayMode.isSupported.value);
settingsStore.setProtectionSupported(screenProtection.isProtectionSupported.value);

// Computed
const selectedTemplate = computed(() => realtimeStore.selectedTemplate);
const conversationContext = computed(() => realtimeStore.conversationContext);
const lastCustomerMessage = computed(() => realtimeStore.lastCustomerMessage);
const isProtectionEnabled = computed(() => settingsStore.isProtectionEnabled);
const isOverlayMode = computed(() => settingsStore.isOverlayMode);

// Refs
const salespersonAgent = ref<RealtimeAgent | null>(null);
const coachAgent = ref<RealtimeAgent | null>(null);
const salespersonSession = ref<RealtimeSession | null>(null);
const coachSession = ref<RealtimeSession | null>(null);

// Audio capture
let audioCapture: any = null;
let currentAudioData = ref<ArrayBuffer | null>(null);

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
        openaiStore.setEphemeralKey(data.client_secret.value, 60);
        
        // Initialize agents
        await initializeAgents(data.client_secret.value);
        
        // Start audio capture
        await startAudioCapture();
        
        realtimeStore.setActiveState(true);
        realtimeStore.setConnectionStatus('connected');
        
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
            audioCapture.stop();
            audioCapture = null;
        }
        
        // Disconnect sessions
        await openaiStore.disconnectAllAgents();
        
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
    
    salespersonAgent.value = new RealtimeAgent(salespersonConfig);
    salespersonSession.value = new RealtimeSession({
        url: 'wss://api.openai.com/v1/realtime',
        api_key: apiKey,
        model: 'gpt-4o-mini-realtime-preview-2024-12-17',
        modalities: ['text'],
        instructions: salespersonConfig.instructions,
        input_audio_format: 'pcm16',
        output_audio_format: 'pcm16',
        input_audio_transcription: {
            model: 'gpt-4o-mini-transcribe',
        },
        turn_detection: null,
        temperature: 0.7,
    });
    
    // Create coach agent with tools
    const coachConfig: RealtimeAgentConfiguration = {
        name: 'Customer Coach',
        description: 'Analyzes conversations and provides real-time coaching',
        instructions: selectedTemplate.value?.prompt || 'You are a helpful sales coach.',
        tools: coachingTools,
    };
    
    coachAgent.value = new RealtimeAgent(coachConfig);
    coachSession.value = new RealtimeSession({
        url: 'wss://api.openai.com/v1/realtime',
        api_key: apiKey,
        model: 'gpt-4o-mini-realtime-preview-2024-12-17',
        modalities: ['text'],
        instructions: coachConfig.instructions,
        tools: coachingTools,
        tool_choice: 'auto',
        input_audio_format: 'pcm16',
        output_audio_format: 'pcm16',
        input_audio_transcription: {
            model: 'gpt-4o-mini-transcribe',
        },
        turn_detection: null,
        temperature: 0.7,
        max_response_output_tokens: 4096,
    });
    
    // Set up event handlers
    setupSessionHandlers();
    
    // Connect sessions
    await salespersonSession.value.connect();
    await coachSession.value.connect();
    
    // Store in OpenAI store
    openaiStore.setSalespersonAgent(salespersonAgent.value, salespersonSession.value);
    openaiStore.setCoachAgent(coachAgent.value, coachSession.value);
};

const setupSessionHandlers = () => {
    if (!salespersonSession.value || !coachSession.value) return;
    
    // Salesperson session handlers (transcription)
    salespersonSession.value.on('conversation.updated', (event: any) => {
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
            if (coachSession.value) {
                coachSession.value.conversation.item.create({
                    type: 'message',
                    role: 'user',
                    content: [{
                        type: 'input_text',
                        text: `Salesperson said: "${item.formatted.transcript}"`
                    }]
                });
            }
        }
    });
    
    // Coach session handlers
    coachSession.value.on('conversation.updated', (event: any) => {
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
    
    // Error handlers
    salespersonSession.value.on('error', (error: any) => {
        console.error('Salesperson session error:', error);
        realtimeStore.addTranscriptGroup({
            id: `error-${Date.now()}`,
            role: 'system',
            messages: [{ text: `Salesperson connection error: ${error.message}`, timestamp: Date.now() }],
            startTime: Date.now(),
            systemCategory: 'error',
        });
    });
    
    coachSession.value.on('error', (error: any) => {
        console.error('Coach session error:', error);
        realtimeStore.addTranscriptGroup({
            id: `error-${Date.now()}`,
            role: 'system',
            messages: [{ text: `Coach connection error: ${error.message}`, timestamp: Date.now() }],
            startTime: Date.now(),
            systemCategory: 'error',
        });
    });
};

const startAudioCapture = async () => {
    try {
        // Dynamic import to avoid SSR issues
        const { SystemAudioCapture } = await import('@/services/audioCapture');
        
        audioCapture = new SystemAudioCapture();
        await audioCapture.start();
        
        // Set up audio data handler
        audioCapture.on('audioData', (data: { salesperson: ArrayBuffer; customer: ArrayBuffer }) => {
            // Send salesperson audio to salesperson session
            if (salespersonSession.value && data.salesperson) {
                salespersonSession.value.input_audio_buffer.append(data.salesperson);
                
                // Update audio level
                const level = calculateAudioLevel(data.salesperson);
                realtimeStore.setAudioLevel(level);
            }
            
            // Send customer audio to coach session
            if (coachSession.value && data.customer) {
                coachSession.value.input_audio_buffer.append(data.customer);
                
                // Update system audio level
                const level = calculateAudioLevel(data.customer);
                realtimeStore.setSystemAudioLevel(level);
            }
        });
        
        audioCapture.on('error', (error: Error) => {
            console.error('Audio capture error:', error);
            realtimeStore.setMicrophoneStatus('error');
        });
        
        realtimeStore.setMicrophoneStatus('active');
        realtimeStore.setSystemAudioActive(true);
        
    } catch (error) {
        console.error('Failed to start audio capture:', error);
        realtimeStore.setMicrophoneStatus('error');
    }
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
    if (newTemplate && coachSession.value) {
        // Update coach instructions
        coachSession.value.session.update({
            instructions: newTemplate.prompt,
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
</style>