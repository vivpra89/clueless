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
        <div
            class="title-bar flex h-10 flex-shrink-0 items-center border-b border-gray-200 bg-gray-50 px-3 md:px-6 dark:border-gray-700 dark:bg-gray-900"
            style="-webkit-app-region: drag"
        >
            <!-- Left: App Title (with space for macOS controls) -->
            <div class="flex-1 pl-16 md:pl-20">
                <span class="text-xs font-semibold text-gray-800 md:text-sm dark:text-gray-200">Clueless - V2 SDK</span>
            </div>

            <!-- Mobile Menu Button (visible on small screens) -->
            <button
                @click="showMobileMenu = !showMobileMenu"
                class="p-1.5 text-gray-600 transition-colors hover:text-gray-900 md:hidden dark:text-gray-400 dark:hover:text-gray-100"
                style="-webkit-app-region: no-drag"
            >
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path v-if="!showMobileMenu" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>

            <!-- Right Side: All Controls (hidden on mobile, visible on desktop) -->
            <div class="hidden items-center gap-4 md:flex lg:gap-6" style="-webkit-app-region: no-drag">
                <!-- Coach Selector -->
                <div class="relative">
                    <button
                        @click="showCoachDropdown = !showCoachDropdown"
                        :disabled="isActive"
                        class="flex items-center gap-1.5 text-xs text-gray-600 transition-colors hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100"
                        :class="{ 'cursor-not-allowed opacity-50': isActive }"
                    >
                        <span>Coach:</span>
                        <span class="font-medium text-gray-800 dark:text-gray-200">{{ selectedTemplate?.name || 'Select' }}</span>
                        <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>

                    <!-- Coach Dropdown Menu -->
                    <div
                        v-if="showCoachDropdown"
                        class="absolute top-full right-0 left-0 z-50 mt-2 flex max-h-96 w-full flex-col rounded-lg bg-white shadow-xl md:right-0 md:left-auto md:w-80 dark:bg-gray-800"
                    >
                        <!-- Search Input -->
                        <div class="border-b border-gray-200 p-3 dark:border-gray-700">
                            <input
                                v-model="templateSearchQuery"
                                type="text"
                                placeholder="Search templates..."
                                class="w-full rounded border border-gray-200 bg-white px-3 py-1.5 text-sm transition-all focus:border-transparent focus:ring-1 focus:ring-blue-500 focus:outline-none dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200"
                                @click.stop
                            />
                        </div>

                        <!-- Template List -->
                        <div
                            class="scrollbar-thin scrollbar-thumb-gray-300 scrollbar-track-transparent hover:scrollbar-thumb-gray-400 flex-1 overflow-y-auto"
                        >
                            <div v-if="filteredTemplates.length === 0" class="p-3 text-center text-xs text-gray-600 dark:text-gray-400">
                                No templates found
                            </div>
                            <div
                                v-for="template in filteredTemplates"
                                :key="template.id"
                                @click="
                                    selectTemplateFromDropdown(template);
                                    showCoachDropdown = false;
                                    templateSearchQuery = '';
                                "
                                class="cursor-pointer px-3 py-1.5 transition-colors hover:bg-gray-50/50 dark:hover:bg-gray-700/50"
                                :class="{ 'border-l-2 border-blue-500 bg-blue-50/50': selectedTemplate?.id === template.id }"
                            >
                                <div class="flex items-center gap-2">
                                    <span class="text-sm">{{ getIconEmoji(template.icon) }}</span>
                                    <p class="text-xs text-gray-900 dark:text-gray-100">{{ template.name }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Connection Status -->
                <div class="flex items-center gap-1.5">
                    <div
                        :class="[
                            'h-2 w-2 rounded-full',
                            connectionStatus === 'connected'
                                ? 'bg-green-500'
                                : connectionStatus === 'connecting'
                                  ? 'animate-pulse bg-yellow-500'
                                  : 'bg-red-500',
                        ]"
                    ></div>
                    <span class="text-xs text-gray-600 dark:text-gray-400">
                        {{ connectionStatus === 'connected' ? 'Connected' : connectionStatus === 'connecting' ? 'Connecting...' : 'Disconnected' }}
                    </span>
                </div>

                <!-- Start/End Call Button -->
                <button
                    @click="toggleSession"
                    :disabled="!hasApiKey || connectionStatus === 'connecting' || !selectedTemplate"
                    class="rounded-lg px-3 py-1.5 text-xs transition-all"
                    :class="
                        isActive
                            ? 'bg-red-500 text-white hover:bg-red-600'
                            : 'bg-blue-500 text-white hover:bg-blue-600 disabled:cursor-not-allowed disabled:bg-gray-300 disabled:text-gray-500'
                    "
                >
                    {{ isActive ? 'End Call' : 'Start Call' }}
                </button>
            </div>
        </div>

        <!-- Mobile Control Menu (slides down on mobile) -->
        <div
            v-if="showMobileMenu"
            class="z-40 border-b border-gray-200 bg-gray-50 px-4 py-3 shadow-lg md:hidden dark:border-gray-700 dark:bg-gray-900"
        >
            <!-- Coach Selection -->
            <div class="mb-3">
                <label class="mb-1 block text-xs text-gray-600 dark:text-gray-400">Coach:</label>
                <select
                    v-model="selectedTemplateId"
                    :disabled="isActive"
                    class="w-full rounded border border-gray-200 bg-white p-2 text-sm dark:border-gray-700 dark:bg-gray-800"
                >
                    <option value="">Select a coach</option>
                    <option v-for="template in templates" :key="template.id" :value="template.id">
                        {{ getIconEmoji(template.icon) }} {{ template.name }}
                    </option>
                </select>
            </div>

            <!-- Status and Controls -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div
                        :class="[
                            'h-2 w-2 rounded-full',
                            connectionStatus === 'connected'
                                ? 'bg-green-500'
                                : connectionStatus === 'connecting'
                                  ? 'animate-pulse bg-yellow-500'
                                  : 'bg-red-500',
                        ]"
                    ></div>
                    <span class="text-xs text-gray-600 dark:text-gray-400">
                        {{ connectionStatus === 'connected' ? 'Connected' : connectionStatus === 'connecting' ? 'Connecting...' : 'Disconnected' }}
                    </span>
                </div>

                <button
                    @click="toggleSession"
                    :disabled="!hasApiKey || connectionStatus === 'connecting' || !selectedTemplate"
                    class="rounded-lg px-4 py-2 text-xs transition-all"
                    :class="
                        isActive
                            ? 'bg-red-500 text-white hover:bg-red-600'
                            : 'bg-blue-500 text-white hover:bg-blue-600 disabled:cursor-not-allowed disabled:bg-gray-300 disabled:text-gray-500'
                    "
                >
                    {{ isActive ? 'End Call' : 'Start Call' }}
                </button>
            </div>
        </div>

        <!-- Main Content Area -->
        <div class="flex flex-1 flex-col overflow-hidden">
            <div class="grid flex-1 grid-cols-1 overflow-hidden lg:grid-cols-3">
                <!-- Center: Main View Area -->
                <div class="flex flex-col overflow-hidden lg:col-span-2">
                    <!-- Header -->
                    <div class="border-b border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
                        <h2 class="text-lg font-semibold">Live Session</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Real-time coaching and transcription</p>
                    </div>

                    <!-- Transcript Area -->
                    <div
                        ref="transcriptContainer"
                        class="scrollbar-thin scrollbar-thumb-gray-300 dark:scrollbar-thumb-gray-600 scrollbar-track-transparent flex flex-1 flex-col-reverse overflow-y-auto bg-white p-4 dark:bg-gray-900"
                    >
                        <div v-if="transcriptGroups.length === 0" class="flex flex-1 items-center justify-center text-gray-400">
                            <p class="text-center">
                                No transcripts yet.<br />
                                Start a call to begin.
                            </p>
                        </div>
                        
                        <!-- Transcript Groups -->
                        <div v-for="group in transcriptGroups" :key="group.id" class="mb-4">
                            <div
                                :class="[
                                    'rounded-lg p-3',
                                    group.role === 'salesperson'
                                        ? 'bg-blue-50 dark:bg-blue-900/20'
                                        : group.role === 'customer'
                                          ? 'bg-green-50 dark:bg-green-900/20'
                                          : group.systemCategory === 'error'
                                            ? 'bg-red-50 dark:bg-red-900/20'
                                            : group.systemCategory === 'warning'
                                              ? 'bg-yellow-50 dark:bg-yellow-900/20'
                                              : group.systemCategory === 'success'
                                                ? 'bg-green-50 dark:bg-green-900/20'
                                                : 'bg-gray-50 dark:bg-gray-800',
                                ]"
                            >
                                <div class="mb-1 flex items-center gap-2">
                                    <span
                                        :class="[
                                            'text-xs font-medium',
                                            group.role === 'salesperson'
                                                ? 'text-blue-700 dark:text-blue-300'
                                                : group.role === 'customer'
                                                  ? 'text-green-700 dark:text-green-300'
                                                  : 'text-gray-600 dark:text-gray-400',
                                        ]"
                                    >
                                        {{ group.role === 'salesperson' ? 'Salesperson' : group.role === 'customer' ? 'Customer' : 'System' }}
                                    </span>
                                    <span class="text-xs text-gray-500 dark:text-gray-500">
                                        {{ new Date(group.startTime).toLocaleTimeString() }}
                                    </span>
                                </div>
                                <div class="space-y-1">
                                    <p
                                        v-for="(message, idx) in group.messages"
                                        :key="idx"
                                        :class="[
                                            'text-sm',
                                            group.role === 'system' ? 'text-gray-600 dark:text-gray-400' : 'text-gray-800 dark:text-gray-200',
                                        ]"
                                    >
                                        {{ message.text }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Audio Level Indicators -->
                    <div class="border-t border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <div class="mb-1 flex items-center justify-between">
                                    <span class="text-xs text-gray-600 dark:text-gray-400">Microphone</span>
                                    <span
                                        :class="[
                                            'text-xs',
                                            microphoneStatus === 'active'
                                                ? 'text-green-600 dark:text-green-400'
                                                : microphoneStatus === 'error'
                                                  ? 'text-red-600 dark:text-red-400'
                                                  : 'text-gray-500 dark:text-gray-500',
                                        ]"
                                    >
                                        {{ microphoneStatus }}
                                    </span>
                                </div>
                                <div class="h-2 overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                                    <div
                                        class="h-full bg-blue-500 transition-all duration-100 ease-out"
                                        :style="{ width: `${audioLevel}%` }"
                                    ></div>
                                </div>
                            </div>
                            <div>
                                <div class="mb-1 flex items-center justify-between">
                                    <span class="text-xs text-gray-600 dark:text-gray-400">System Audio</span>
                                    <span
                                        :class="[
                                            'text-xs',
                                            isSystemAudioActive ? 'text-green-600 dark:text-green-400' : 'text-gray-500 dark:text-gray-500',
                                        ]"
                                    >
                                        {{ isSystemAudioActive ? 'active' : 'inactive' }}
                                    </span>
                                </div>
                                <div class="h-2 overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                                    <div
                                        class="h-full bg-green-500 transition-all duration-100 ease-out"
                                        :style="{ width: `${systemAudioLevel}%` }"
                                    ></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right: Coaching Panel -->
                <div class="flex flex-col overflow-hidden border-l border-gray-200 dark:border-gray-700">
                    <!-- Coaching Intelligence Header -->
                    <div class="border-b border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
                        <h3 class="text-base font-semibold">Real-time Intelligence</h3>
                    </div>

                    <!-- Intelligence Content (scrollable) -->
                    <div class="scrollbar-thin scrollbar-thumb-gray-300 dark:scrollbar-thumb-gray-600 scrollbar-track-transparent flex-1 overflow-y-auto bg-gray-50 p-4 dark:bg-gray-800">
                        <!-- Topics -->
                        <div v-if="topics.length > 0" class="mb-6">
                            <h4 class="mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">Discussion Topics</h4>
                            <div class="space-y-2">
                                <div v-for="topic in topics" :key="topic.id" class="rounded bg-white p-2 text-xs shadow-sm dark:bg-gray-700">
                                    <div class="flex items-center justify-between">
                                        <span class="font-medium">{{ topic.name }}</span>
                                        <span
                                            :class="[
                                                'rounded px-1.5 py-0.5 text-xs',
                                                topic.sentiment === 'positive'
                                                    ? 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300'
                                                    : topic.sentiment === 'negative'
                                                      ? 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300'
                                                      : 'bg-gray-100 text-gray-700 dark:bg-gray-600 dark:text-gray-300',
                                            ]"
                                        >
                                            {{ topic.sentiment }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Customer Intelligence -->
                        <div v-if="customerIntelligence" class="mb-6">
                            <h4 class="mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">Customer Analysis</h4>
                            <div class="rounded bg-white p-3 shadow-sm dark:bg-gray-700">
                                <div class="space-y-2 text-xs">
                                    <div class="flex justify-between">
                                        <span class="text-gray-600 dark:text-gray-400">Intent:</span>
                                        <span class="font-medium capitalize">{{ customerIntelligence.intent }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600 dark:text-gray-400">Stage:</span>
                                        <span class="font-medium">{{ customerIntelligence.buyingStage }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600 dark:text-gray-400">Engagement:</span>
                                        <div class="flex items-center gap-2">
                                            <div class="h-2 w-20 overflow-hidden rounded-full bg-gray-200 dark:bg-gray-600">
                                                <div
                                                    class="h-full bg-blue-500 transition-all"
                                                    :style="{ width: `${customerIntelligence.engagementLevel}%` }"
                                                ></div>
                                            </div>
                                            <span>{{ customerIntelligence.engagementLevel }}%</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Key Insights -->
                        <div v-if="insights.length > 0" class="mb-6">
                            <h4 class="mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">Key Insights</h4>
                            <div class="space-y-2">
                                <div
                                    v-for="insight in insights.slice(0, 5)"
                                    :key="insight.id"
                                    class="rounded bg-white p-2 text-xs shadow-sm dark:bg-gray-700"
                                >
                                    <div class="flex items-start gap-2">
                                        <span
                                            :class="[
                                                'mt-0.5 h-2 w-2 flex-shrink-0 rounded-full',
                                                insight.type === 'pain_point'
                                                    ? 'bg-red-500'
                                                    : insight.type === 'positive_signal'
                                                      ? 'bg-green-500'
                                                      : insight.type === 'objection'
                                                        ? 'bg-orange-500'
                                                        : 'bg-blue-500',
                                            ]"
                                        ></span>
                                        <p class="flex-1">{{ insight.text }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Action Items -->
                        <div v-if="actionItems.length > 0">
                            <h4 class="mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">Action Items</h4>
                            <div class="space-y-2">
                                <div
                                    v-for="item in actionItems"
                                    :key="item.id"
                                    class="flex items-start gap-2 rounded bg-white p-2 text-xs shadow-sm dark:bg-gray-700"
                                >
                                    <input
                                        type="checkbox"
                                        :checked="item.completed"
                                        @change="toggleActionItem(item.id)"
                                        class="mt-0.5 h-3 w-3 rounded border-gray-300"
                                    />
                                    <div class="flex-1">
                                        <p :class="{ 'line-through opacity-60': item.completed }">{{ item.text }}</p>
                                        <p class="mt-0.5 text-xs text-gray-500">
                                            Owner: {{ item.owner }} | {{ item.type }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';
// import { useOverlayMode } from '@/composables/useOverlayMode';
// import { useScreenProtection } from '@/composables/useScreenProtection';
// import { useVariables } from '@/composables/useVariables';
// import { audioHealthMonitor, type AudioHealthStatus } from '@/services/audioHealthCheck';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
// OpenAI Agents SDK imports
import { 
    RealtimeAgent,
    RealtimeSession,
    type RealtimeAgentConfiguration,
    type RealtimeSessionOptions,
} from '@openai/agents-realtime';
import { tool } from '@openai/agents-core';
import { z } from 'zod';

// TypeScript interfaces (keeping the same as V1)
interface Script {
    id: string;
    text: string;
    displayText?: string;
    context: string;
    priority: 'high' | 'normal' | 'low';
    used: boolean;
    timestamp: Date;
    isStreaming?: boolean;
    rawArgs?: string;
}

interface Topic {
    id: string;
    name: string;
    firstMentioned: number;
    lastMentioned: number;
    mentions: number;
    sentiment: 'positive' | 'negative' | 'neutral' | 'mixed';
}

interface Commitment {
    id: string;
    speaker: 'salesperson' | 'customer';
    text: string;
    context: string;
    timestamp: number;
    type: 'promise' | 'next_step' | 'deliverable';
    deadline?: string;
    completed?: boolean;
}

interface Insight {
    id: string;
    type: 'pain_point' | 'objection' | 'positive_signal' | 'concern' | 'question';
    text: string;
    importance: 'high' | 'medium' | 'low';
    timestamp: number;
    addressed: boolean;
}

interface ActionItem {
    id: string;
    text: string;
    owner: 'salesperson' | 'customer' | 'both';
    type: 'follow_up' | 'send_info' | 'schedule' | 'internal';
    deadline?: string;
    completed: boolean;
    relatedCommitment?: string;
}

interface CustomerIntelligence {
    intent: 'research' | 'evaluation' | 'decision' | 'implementation' | 'unknown';
    sentiment: 'positive' | 'negative' | 'neutral';
    engagementLevel: number; // 0-100
    buyingStage: string;
}

interface TranscriptGroup {
    id: string;
    role: 'salesperson' | 'customer' | 'system';
    messages: Array<{ text: string; timestamp: number }>;
    startTime: number;
    endTime: number;
    systemCategory?: 'info' | 'warning' | 'success' | 'error';
}

interface Template {
    id: string;
    name: string;
    description: string;
    prompt: string;
    icon: string;
    category: string;
    is_system: boolean;
    usage_count: number;
    variables?: Record<string, string>;
    talking_points?: string[];
    additional_info?: Record<string, any>;
}

// Props
interface Props {
    apiKeyConfigured: boolean;
    templates: Template[];
}

const props = defineProps<Props>();

// State
const connectionStatus = ref<'disconnected' | 'connecting' | 'connected'>('disconnected');
const isActive = ref(false);
const isEndingCall = ref(false);
const audioLevel = ref(0);
const systemAudioLevel = ref(0);
const transcript = ref<{ role: string; text: string; timestamp: number }[]>([]);
const transcriptGroups = ref<TranscriptGroup[]>([]);
const microphoneStatus = ref<'inactive' | 'active' | 'error'>('inactive');
const isSystemAudioActive = ref(false);
const showMobileMenu = ref(false);
const showCoachDropdown = ref(false);
const templateSearchQuery = ref('');
const selectedTemplateId = ref<string>('');
const transcriptContainer = ref<HTMLElement | null>(null);

// Coaching state
const topics = ref<Topic[]>([]);
const commitments = ref<Commitment[]>([]);
const insights = ref<Insight[]>([]);
const actionItems = ref<ActionItem[]>([]);
const customerIntelligence = ref<CustomerIntelligence | null>(null);
const lastCustomerMessage = ref<string>('');
const currentSessionId = ref<string | null>(null);

// Audio health monitoring
const audioHealthStatus = ref<any | null>(null);

// OpenAI Agents SDK instances
let salespersonAgent: RealtimeAgent | null = null;
let customerCoachAgent: RealtimeAgent | null = null;
let salespersonSession: RealtimeSession | null = null;
let customerCoachSession: RealtimeSession | null = null;

// System audio capture (dynamically imported)
let systemAudioCapture: any = null;
let SystemAudioCapture: any = null;
let isSystemAudioAvailable: any = null;

// Audio processing
let microphoneAudioContext: AudioContext | null = null;
let microphoneSource: MediaStreamAudioSourceNode | null = null;
let microphoneProcessor: ScriptProcessorNode | null = null;
let microphoneStream: MediaStream | null = null;

// Computed
const hasApiKey = computed(() => props.apiKeyConfigured);
const selectedTemplate = computed(() => {
    if (!selectedTemplateId.value) return null;
    return props.templates.find((t) => t.id === selectedTemplateId.value) || null;
});
const templates = computed(() => props.templates || []);
const filteredTemplates = computed(() => {
    if (!templateSearchQuery.value) return templates.value;
    const query = templateSearchQuery.value.toLowerCase();
    return templates.value.filter((t) => 
        t.name.toLowerCase().includes(query) || 
        t.description?.toLowerCase().includes(query)
    );
});

// Composables
// const { isOverlayMode } = useOverlayMode();
// const { isProtectionEnabled } = useScreenProtection();
// const { processedVariables } = useVariables();

// Temporary replacements
const isOverlayMode = ref(false);
const isProtectionEnabled = ref(false);
const processedVariables = ref({});

// Helper Functions
const getIconEmoji = (icon: string | null | undefined): string => {
    const iconMap: Record<string, string> = {
        'brain': '🧠',
        'chart-line': '📈',
        'comments': '💬',
        'lightbulb': '💡',
        'target': '🎯',
        'handshake': '🤝',
        'shield-check': '🛡️',
        'rocket': '🚀',
        'star': '⭐',
        'question-circle': '❓',
        'default': '📋'
    };
    return iconMap[icon || 'default'] || iconMap.default;
};

const selectTemplateFromDropdown = (template: Template) => {
    selectedTemplateId.value = template.id;
};

const toggleActionItem = (itemId: string) => {
    const item = actionItems.value.find(a => a.id === itemId);
    if (item) {
        item.completed = !item.completed;
    }
};

// Audio Level Monitoring
const updateAudioLevel = (level: number, source: 'microphone' | 'system') => {
    if (source === 'microphone') {
        audioLevel.value = Math.min(100, Math.round(level * 100));
    } else {
        systemAudioLevel.value = Math.min(100, Math.round(level * 100));
    }
};

// Transcript Management
const addToTranscript = (role: 'salesperson' | 'customer' | 'system', text: string, systemCategory?: 'info' | 'warning' | 'success' | 'error') => {
    const timestamp = Date.now();
    const MESSAGE_GROUP_TIMEOUT = 5000;

    transcript.value.push({ role, text, timestamp });

    if (role === 'customer') {
        lastCustomerMessage.value = text;
    }

    const lastGroup = transcriptGroups.value[transcriptGroups.value.length - 1];

    if (
        lastGroup &&
        lastGroup.role === role &&
        timestamp - lastGroup.endTime < MESSAGE_GROUP_TIMEOUT &&
        (role !== 'system' || lastGroup.systemCategory === systemCategory)
    ) {
        lastGroup.messages.push({ text, timestamp });
        lastGroup.endTime = timestamp;
    } else {
        const newGroup: TranscriptGroup = {
            id: `group-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`,
            role,
            messages: [{ text, timestamp }],
            startTime: timestamp,
            endTime: timestamp,
            systemCategory,
        };
        transcriptGroups.value.push(newGroup);
    }

    scrollTranscriptToTop();
};

const scrollTranscriptToTop = () => {
    nextTick(() => {
        if (transcriptContainer.value) {
            transcriptContainer.value.scrollTop = transcriptContainer.value.scrollHeight;
        }
    });
};

// Create Coaching Tools for OpenAI Agents SDK
const createCoachingTools = () => [
    tool({
        name: 'track_discussion_topic',
        description: 'Track or update a discussion topic',
        parameters: z.object({
            name: z.string().describe('Topic name (e.g., "Pricing", "Integration", "Timeline")'),
            sentiment: z.enum(['positive', 'negative', 'neutral', 'mixed']),
            context: z.string().optional().describe('Brief context about the discussion'),
        }),
        execute: async (args) => {
            handleFunctionCall('track_discussion_topic', args);
            return `Tracked topic: ${args.name} with ${args.sentiment} sentiment`;
        },
    }),
    tool({
        name: 'detect_commitment',
        description: 'Detect commitments, promises, or next steps from either party',
        parameters: z.object({
            speaker: z.enum(['salesperson', 'customer']),
            text: z.string().describe('The commitment or promise made'),
            context: z.string().optional().describe('Context around the commitment'),
            type: z.enum(['promise', 'next_step', 'deliverable']),
            deadline: z.string().optional().describe('Optional deadline mentioned'),
        }),
        execute: async (args) => {
            handleFunctionCall('detect_commitment', args);
            return `Commitment detected: ${args.text}`;
        },
    }),
    tool({
        name: 'analyze_customer_intent',
        description: 'Analyze and update customer intent and buying stage',
        parameters: z.object({
            intent: z.enum(['research', 'evaluation', 'decision', 'implementation', 'unknown']),
            sentiment: z.enum(['positive', 'negative', 'neutral']),
            engagementLevel: z.number().min(0).max(100),
            buyingStage: z.string().describe('Current stage in buying process'),
            reasoning: z.string().optional().describe('Brief explanation for the analysis'),
        }),
        execute: async (args) => {
            handleFunctionCall('analyze_customer_intent', args);
            return `Customer analysis updated: ${args.intent} intent at ${args.engagementLevel}% engagement`;
        },
    }),
    {
        name: 'highlight_insight',
        description: 'Highlight important insights from the conversation',
        parameters: {
            type: 'object',
            properties: {
                type: { type: 'string', enum: ['pain_point', 'objection', 'positive_signal', 'concern', 'question'] },
                text: { type: 'string', description: 'The key insight' },
                importance: { type: 'string', enum: ['high', 'medium', 'low'] },
                context: { type: 'string', description: 'Additional context' },
            },
            required: ['type', 'text', 'importance'],
        },
    },
    tool({
        name: 'create_action_item',
        description: 'Create a post-call action item',
        parameters: z.object({
            text: z.string().describe('The action item'),
            owner: z.enum(['salesperson', 'customer', 'both']),
            type: z.enum(['follow_up', 'send_info', 'schedule', 'internal']),
            deadline: z.string().optional().describe('Optional deadline'),
            relatedCommitment: z.string().optional().describe('ID of related commitment if any'),
        }),
        execute: async (args) => {
            handleFunctionCall('create_action_item', args);
            return `Action item created: ${args.text}`;
        },
    }),
    tool({
        name: 'detect_information_need',
        description: 'Detect when customer needs specific information',
        parameters: z.object({
            topic: z.string().describe('What information is needed'),
            urgency: z.enum(['immediate', 'soon', 'later']),
            context: z.string().optional().describe('Why this information is needed'),
        }),
        execute: async (args) => {
            handleFunctionCall('detect_information_need', args);
            return `Information need detected: ${args.topic}`;
        },
    }),
];

// Handle function calls from agents
const handleFunctionCall = (name: string, args: any) => {
    console.log('🎯 Function called:', name, args);

    switch (name) {
        case 'track_discussion_topic':
            const existingTopic = topics.value.find(t => t.name.toLowerCase() === args.name.toLowerCase());
            if (existingTopic) {
                existingTopic.lastMentioned = Date.now();
                existingTopic.mentions++;
                existingTopic.sentiment = args.sentiment;
            } else {
                topics.value.push({
                    id: `topic-${Date.now()}`,
                    name: args.name,
                    firstMentioned: Date.now(),
                    lastMentioned: Date.now(),
                    mentions: 1,
                    sentiment: args.sentiment,
                });
            }
            break;

        case 'detect_commitment':
            commitments.value.push({
                id: `commit-${Date.now()}`,
                speaker: args.speaker,
                text: args.text,
                context: args.context || '',
                timestamp: Date.now(),
                type: args.type,
                deadline: args.deadline,
                completed: false,
            });
            break;

        case 'analyze_customer_intent':
            customerIntelligence.value = {
                intent: args.intent,
                sentiment: args.sentiment,
                engagementLevel: args.engagementLevel,
                buyingStage: args.buyingStage,
            };
            break;

        case 'highlight_insight':
            insights.value.unshift({
                id: `insight-${Date.now()}`,
                type: args.type,
                text: args.text,
                importance: args.importance,
                timestamp: Date.now(),
                addressed: false,
            });
            if (insights.value.length > 10) {
                insights.value = insights.value.slice(0, 10);
            }
            break;

        case 'create_action_item':
            actionItems.value.push({
                id: `action-${Date.now()}`,
                text: args.text,
                owner: args.owner,
                type: args.type,
                deadline: args.deadline,
                completed: false,
                relatedCommitment: args.relatedCommitment,
            });
            break;

        case 'detect_information_need':
            addToTranscript('system', `ℹ️ Customer needs information about: ${args.topic} (${args.urgency})`, 'info');
            break;
    }
};

// Process template variables
const processTemplateInstructions = (template: Template) => {
    if (!template) return '';
    
    let instructions = template.prompt;
    const variables = { ...template.variables, ...processedVariables.value };
    
    Object.entries(variables).forEach(([key, value]) => {
        instructions = instructions.replace(new RegExp(`\\{${key}\\}`, 'g'), String(value));
    });
    
    return instructions;
};

// Initialize microphone audio
const initializeMicrophoneAudio = async () => {
    try {
        const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
        microphoneStream = stream;
        microphoneStatus.value = 'active';

        microphoneAudioContext = new AudioContext({ sampleRate: 24000 });
        microphoneSource = microphoneAudioContext.createMediaStreamSource(stream);
        microphoneProcessor = microphoneAudioContext.createScriptProcessor(2048, 1, 1);

        microphoneProcessor.onaudioprocess = (e) => {
            if (!isActive.value) return;

            const inputData = e.inputBuffer.getChannelData(0);
            
            // Calculate audio level
            let sum = 0;
            for (let i = 0; i < inputData.length; i++) {
                sum += Math.abs(inputData[i]);
            }
            const average = sum / inputData.length;
            updateAudioLevel(average * 10, 'microphone');

            // Convert Float32Array to Int16Array for SDK
            const int16Data = new Int16Array(inputData.length);
            for (let i = 0; i < inputData.length; i++) {
                const s = Math.max(-1, Math.min(1, inputData[i]));
                int16Data[i] = s < 0 ? s * 0x8000 : s * 0x7FFF;
            }

            // Send to salesperson session
            if (salespersonSession && isActive.value) {
                salespersonSession.sendAudio(int16Data, { commit: false });
            }
        };

        microphoneSource.connect(microphoneProcessor);
        microphoneProcessor.connect(microphoneAudioContext.destination);
    } catch (error) {
        console.error('❌ Failed to initialize microphone:', error);
        microphoneStatus.value = 'error';
        addToTranscript('system', '❌ Failed to access microphone. Please check permissions.', 'error');
    }
};

// Initialize system audio
const initializeSystemAudio = async () => {
    try {
        const module = await import('@/services/audioCapture');
        SystemAudioCapture = module.SystemAudioCapture;
        isSystemAudioAvailable = module.isSystemAudioAvailable;

        if (!isSystemAudioAvailable()) {
            console.log('System audio not available on this platform');
            return;
        }

        systemAudioCapture = new SystemAudioCapture();

        systemAudioCapture.on('audio', (pcm16: Int16Array) => {
            if (!isActive.value) return;

            // Calculate audio level
            let sum = 0;
            for (let i = 0; i < pcm16.length; i++) {
                sum += Math.abs(pcm16[i] / 32768);
            }
            const average = sum / pcm16.length;
            updateAudioLevel(average * 10, 'system');

            // Send to customer coach session
            if (customerCoachSession && isActive.value) {
                customerCoachSession.sendAudio(pcm16, { commit: false });
            }
        });

        systemAudioCapture.on('status', (status: string) => {
            isSystemAudioActive.value = status === 'active';
            console.log('System audio status:', status);
        });

        await systemAudioCapture.start();
        addToTranscript('system', '✅ System audio capture started', 'success');
    } catch (error) {
        console.error('❌ Failed to initialize system audio:', error);
        addToTranscript('system', '⚠️ System audio not available. Customer audio will not be captured.', 'warning');
    }
};

// Start session with OpenAI Agents SDK
const startSession = async () => {
    if (!selectedTemplate.value) {
        addToTranscript('system', '⚠️ Please select a coach template first', 'warning');
        return;
    }

    isActive.value = true;
    connectionStatus.value = 'connecting';

    try {
        // Get ephemeral key
        const response = await axios.post('/api/realtime/ephemeral-key');
        const { ephemeralKey } = response.data;

        // Process template instructions
        const coachInstructions = processTemplateInstructions(selectedTemplate.value);

        // Initialize audio
        await initializeMicrophoneAudio();
        await initializeSystemAudio();

        // Create agents
        salespersonAgent = new RealtimeAgent({
            name: 'Salesperson Transcriber',
            instructions: 'Transcribe audio only. No analysis or responses.',
            voice: 'alloy', // Using a voice for the agent
        });

        customerCoachAgent = new RealtimeAgent({
            name: 'Customer Coach',
            instructions: coachInstructions,
            tools: createCoachingTools(),
            voice: 'nova', // Different voice for coaching
        });

        // Create sessions with ephemeral key
        salespersonSession = new RealtimeSession(salespersonAgent, {
            transport: 'websocket',
            apiKey: ephemeralKey,
            model: 'gpt-4o-mini-realtime-preview-2024-12-17',
            config: {
                modalities: ['text', 'audio'],
                input_audio_format: 'pcm16',
                output_audio_format: 'pcm16',
                input_audio_transcription: {
                    model: 'gpt-4o-mini-transcribe',
                },
                turn_detection: {
                    type: 'server_vad',
                    threshold: 0.5,
                    prefix_padding_ms: 100,
                    silence_duration_ms: 200,
                },
            },
        });

        customerCoachSession = new RealtimeSession(customerCoachAgent, {
            transport: 'websocket',
            apiKey: ephemeralKey,
            model: 'gpt-4o-mini-realtime-preview-2024-12-17',
            config: {
                modalities: ['text', 'audio'],
                input_audio_format: 'pcm16',
                output_audio_format: 'pcm16',
                input_audio_transcription: {
                    model: 'gpt-4o-mini-transcribe',
                },
                turn_detection: {
                    type: 'server_vad',
                    threshold: 0.6,
                    prefix_padding_ms: 50,
                    silence_duration_ms: 150,
                },
            },
        });

        // Set up event handlers for salesperson session
        salespersonSession.on('agent_response', (event) => {
            if (event.data?.text) {
                addToTranscript('salesperson', event.data.text);
            }
        });

        // Set up event handlers for customer coach session
        customerCoachSession.on('agent_response', (event) => {
            if (event.data?.text) {
                addToTranscript('customer', event.data.text);
            }
        });

        customerCoachSession.on('tool_call', (event) => {
            console.log('Tool called:', event.tool);
            // Tool execution is handled automatically by the SDK
        });

        customerCoachSession.on('error', (error: any) => {
            console.error('❌ Customer Coach error:', error);
            addToTranscript('system', `❌ Coach error: ${error.message}`, 'error');
        });

        // Connect both sessions
        await Promise.all([
            salespersonSession.connect(),
            customerCoachSession.connect(),
        ]);

        connectionStatus.value = 'connected';
        addToTranscript('system', '✅ Connected to OpenAI Realtime API (SDK)', 'success');

        // Create new session in database
        const sessionResponse = await axios.post('/conversations', {
            template_id: selectedTemplate.value.id,
            template_name: selectedTemplate.value.name,
            variables: processedVariables.value,
        });

        currentSessionId.value = sessionResponse.data.id;

    } catch (error) {
        console.error('❌ Failed to start session:', error);
        connectionStatus.value = 'disconnected';
        isActive.value = false;
        addToTranscript('system', '❌ Failed to connect. Please check your API key and try again.', 'error');
    }
};

// End session
const endSession = async () => {
    isEndingCall.value = true;
    isActive.value = false;

    // Disconnect sessions
    if (salespersonSession) {
        await salespersonSession.disconnect();
        salespersonSession = null;
    }

    if (customerCoachSession) {
        await customerCoachSession.disconnect();
        customerCoachSession = null;
    }

    // Clear agent references
    salespersonAgent = null;
    customerCoachAgent = null;

    // Stop audio
    if (microphoneProcessor) {
        microphoneProcessor.disconnect();
        microphoneProcessor = null;
    }

    if (microphoneSource) {
        microphoneSource.disconnect();
        microphoneSource = null;
    }

    if (microphoneStream) {
        microphoneStream.getTracks().forEach(track => track.stop());
        microphoneStream = null;
    }

    if (microphoneAudioContext) {
        microphoneAudioContext.close();
        microphoneAudioContext = null;
    }

    if (systemAudioCapture) {
        systemAudioCapture.stop();
        systemAudioCapture = null;
    }

    microphoneStatus.value = 'inactive';
    isSystemAudioActive.value = false;
    connectionStatus.value = 'disconnected';
    audioLevel.value = 0;
    systemAudioLevel.value = 0;

    addToTranscript('system', '📞 Call ended', 'info');
    isEndingCall.value = false;
};

// Toggle session
const toggleSession = async () => {
    if (isActive.value) {
        await endSession();
    } else {
        await startSession();
    }
};

// Component lifecycle
onMounted(() => {
    // Start audio health monitoring
    // audioHealthMonitor.startMonitoring();
    // audioHealthMonitor.on('statusChange', (status: AudioHealthStatus) => {
    //     audioHealthStatus.value = status;
    // });

    // Set default template if available
    if (templates.value.length > 0 && !selectedTemplateId.value) {
        selectedTemplateId.value = templates.value[0].id;
    }
});

onUnmounted(() => {
    if (isActive.value) {
        endSession();
    }
    // audioHealthMonitor.stopMonitoring();
});

// Watch for template changes
watch(selectedTemplateId, () => {
    if (isActive.value) {
        addToTranscript('system', '⚠️ Template changes will take effect on the next call', 'warning');
    }
});
</script>

<style scoped>
/* Reusing the same styles from Main.vue */
/* Template V2 badge */
.title-bar span::after {
    content: ' (V2)';
    @apply text-xs text-blue-500;
}

/* Scrollbar styling */
.scrollbar-thin {
    scrollbar-width: thin;
}

.scrollbar-thumb-gray-300 {
    scrollbar-color: #d1d5db transparent;
}

.dark .scrollbar-thumb-gray-600 {
    scrollbar-color: #4b5563 transparent;
}

.scrollbar-track-transparent {
    scrollbar-color: transparent transparent;
}

/* Dot pattern background */
.bg-dot-pattern {
    background-image: radial-gradient(circle, #e5e7eb 1px, transparent 1px);
    background-size: 20px 20px;
}

.dark .bg-dot-pattern {
    background-image: radial-gradient(circle, #374151 1px, transparent 1px);
}

/* Screen protection styles */
.screen-protection-active {
    position: relative;
}

.screen-protection-filter {
    filter: blur(8px);
}

.screen-protection-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    z-index: 100;
    pointer-events: none;
}

/* Overlay mode styles */
.overlay-mode-active {
    background: transparent !important;
}

/* Animation for pulsing connection status */
@keyframes pulse {
    0% {
        opacity: 1;
    }
    50% {
        opacity: 0.5;
    }
    100% {
        opacity: 1;
    }
}

.animate-pulse {
    animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
</style>