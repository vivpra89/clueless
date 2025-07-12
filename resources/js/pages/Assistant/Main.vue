<template>
    <div class="flex h-screen overflow-hidden rounded-lg border border-gray-800/50 bg-black/85 text-white backdrop-blur-md">
        <!-- Main Content Area -->
        <div class="flex flex-1 flex-col">
            <!-- Header (draggable area) -->
            <div class="flex items-center justify-between border-b border-gray-800/50 bg-gray-900/50 px-6 py-3" style="-webkit-app-region: drag">
                <div class="flex items-center gap-3">
                    <div class="relative">
                        <div :class="['h-2 w-2 rounded-full', isRecording ? 'animate-pulse bg-red-500' : 'bg-gray-600']"></div>
                        <div v-if="isRecording" class="absolute inset-0 h-2 w-2 animate-ping rounded-full bg-red-500"></div>
                    </div>
                    <h1 class="text-sm font-medium text-gray-300">Sales Assistant</h1>
                    <div v-if="isProcessing" class="flex items-center gap-1.5 text-xs text-blue-400">
                        <svg class="h-3 w-3 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                            />
                        </svg>
                        <span>Processing...</span>
                    </div>
                </div>

                <div class="flex items-center gap-2" style="-webkit-app-region: no-drag">
                    <button @click="toggleTranscript" class="rounded p-1.5 transition-colors hover:bg-gray-800" title="Toggle Transcript">
                        <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                            />
                        </svg>
                    </button>
                    <button
                        @click="toggleRecording"
                        :class="[
                            'rounded-md px-3 py-1.5 text-xs font-medium transition-all',
                            isRecording ? 'bg-red-600/80 text-white hover:bg-red-700' : 'bg-green-600/80 text-white hover:bg-green-700',
                        ]"
                    >
                        {{ isRecording ? 'Stop' : 'Start' }}
                    </button>
                    <button @click="minimizeWindow" class="rounded p-1 transition-colors hover:bg-gray-800">
                        <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Main Content -->
            <div class="flex flex-1">
                <!-- Left Panel: Teleprompter Scripts -->
                <div class="flex-1 overflow-y-auto bg-gray-900/30 p-6">
                    <h2 class="mb-4 text-sm font-semibold tracking-wider text-gray-400 uppercase">Suggested Scripts</h2>

                    <div v-if="currentScripts.length === 0" class="py-12 text-center text-gray-500">
                        <svg class="mx-auto mb-3 h-12 w-12 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="1.5"
                                d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"
                            />
                        </svg>
                        <p class="text-sm">Scripts will appear here as the conversation progresses</p>
                    </div>

                    <div v-else class="space-y-4">
                        <!-- Active Script (Teleprompter Style) -->
                        <div v-if="activeScript" class="mb-6">
                            <div class="rounded-lg border border-blue-700/50 bg-blue-900/20 p-6">
                                <div class="mb-3 flex items-start gap-3">
                                    <div class="rounded-lg bg-blue-600/20 p-2">
                                        <svg class="h-5 w-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"
                                            />
                                        </svg>
                                    </div>
                                    <div class="flex-1">
                                        <h3 class="mb-2 text-sm font-medium text-blue-300">Say This Now:</h3>
                                        <p class="text-lg leading-relaxed text-white">{{ activeScript.text }}</p>
                                        <div class="mt-3 flex items-center gap-2">
                                            <button
                                                @click="markScriptAsUsed(activeScript.id)"
                                                class="rounded bg-gray-800 px-2 py-1 text-xs transition-colors hover:bg-gray-700"
                                            >
                                                Mark as Said
                                            </button>
                                            <span class="text-xs text-gray-500">{{ activeScript.context }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Upcoming Scripts -->
                        <div
                            v-for="script in upcomingScripts"
                            :key="script.id"
                            class="rounded-lg border border-gray-700/50 bg-gray-800/50 p-4 opacity-75"
                        >
                            <div class="flex items-start gap-3">
                                <div class="rounded bg-gray-700/50 p-1.5">
                                    <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
                                        />
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm text-gray-300">{{ script.text }}</p>
                                    <p class="mt-1 text-xs text-gray-500">{{ script.context }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Panel: Live Coaching & Metrics -->
                <div class="flex w-80 flex-col border-l border-gray-800/50 bg-gray-900/30">
                    <!-- Live Coaching Section -->
                    <div class="flex-1 overflow-y-auto p-4">
                        <h2 class="mb-4 text-sm font-semibold tracking-wider text-gray-400 uppercase">Live Coaching</h2>

                        <div v-if="suggestions.length > 0" class="space-y-3">
                            <div
                                v-for="(suggestion, index) in suggestions"
                                :key="index"
                                :class="[
                                    'rounded-lg border p-3 transition-all duration-300',
                                    suggestion.priority === 'high' ? 'border-amber-700/50 bg-amber-900/20' : 'border-gray-700/50 bg-gray-800/50',
                                ]"
                            >
                                <div class="flex items-start gap-2">
                                    <svg
                                        v-if="suggestion.type === 'action'"
                                        class="mt-0.5 h-4 w-4 flex-shrink-0 text-amber-500"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                                        />
                                    </svg>
                                    <svg
                                        v-else-if="suggestion.type === 'talking-point'"
                                        class="mt-0.5 h-4 w-4 flex-shrink-0 text-blue-500"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"
                                        />
                                    </svg>
                                    <svg
                                        v-else
                                        class="mt-0.5 h-4 w-4 flex-shrink-0 text-green-500"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                        />
                                    </svg>
                                    <div class="flex-1">
                                        <h3 class="mb-1 text-xs font-medium">{{ suggestion.title }}</h3>
                                        <p class="text-xs text-gray-300">{{ suggestion.content }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-else class="py-8 text-center text-gray-500">
                            <svg class="mx-auto mb-2 h-10 w-10 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="1.5"
                                    d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"
                                />
                            </svg>
                            <p class="text-xs">Coaching tips will appear here</p>
                        </div>
                    </div>

                    <!-- Metrics Section -->
                    <div class="border-t border-gray-800/50 p-4">
                        <h3 class="mb-3 text-sm font-semibold tracking-wider text-gray-400 uppercase">Call Metrics</h3>
                        <div class="space-y-3">
                            <div>
                                <div class="mb-1 flex justify-between text-xs">
                                    <span class="text-gray-400">Talk Ratio</span>
                                    <span>{{ metrics.talkRatio }}%</span>
                                </div>
                                <div class="h-1.5 w-full rounded-full bg-gray-800">
                                    <div
                                        class="h-1.5 rounded-full bg-blue-500 transition-all duration-300"
                                        :style="`width: ${metrics.talkRatio}%`"
                                    ></div>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-3 text-xs">
                                <div>
                                    <p class="text-gray-400">Duration</p>
                                    <p class="font-medium">{{ formatDuration(metrics.duration) }}</p>
                                </div>
                                <div>
                                    <p class="text-gray-400">Sentiment</p>
                                    <p
                                        :class="[
                                            'font-medium',
                                            metrics.sentiment === 'positive'
                                                ? 'text-green-400'
                                                : metrics.sentiment === 'negative'
                                                  ? 'text-red-400'
                                                  : 'text-yellow-400',
                                        ]"
                                    >
                                        {{ metrics.sentiment }}
                                    </p>
                                </div>
                            </div>
                            <div>
                                <p class="mb-1 text-xs text-gray-400">Key Topics</p>
                                <div class="flex flex-wrap gap-1">
                                    <span v-for="topic in metrics.topics" :key="topic" class="rounded bg-gray-800 px-2 py-0.5 text-xs">
                                        {{ topic }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Hidden Transcript (Toggle) -->
            <div v-if="showTranscript" class="absolute inset-0 z-50 flex flex-col bg-black/95">
                <div class="flex items-center justify-between border-b border-gray-800 px-6 py-3">
                    <h2 class="text-sm font-semibold text-gray-300">Live Transcript</h2>
                    <button @click="showTranscript = false" class="rounded p-1 hover:bg-gray-800">
                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div class="flex-1 overflow-y-auto p-6">
                    <div v-if="transcript.length === 0" class="py-12 text-center text-gray-500">
                        <p>No transcript yet...</p>
                    </div>
                    <div v-else class="space-y-2">
                        <div v-for="(entry, index) in transcript" :key="index" class="flex gap-3">
                            <div class="w-16 flex-shrink-0 text-xs text-gray-500">
                                {{ formatTime(entry.timestamp) }}
                            </div>
                            <div class="flex-1">
                                <span :class="['text-xs font-medium', entry.speaker === 'customer' ? 'text-blue-400' : 'text-green-400']">
                                    {{ entry.speaker === 'customer' ? 'Customer' : 'You' }}:
                                </span>
                                <p class="mt-0.5 text-sm text-gray-300">{{ entry.text }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import axios from 'axios';
import { computed, onMounted, onUnmounted, ref } from 'vue';

interface TranscriptEntry {
    timestamp: Date;
    speaker: 'you' | 'customer';
    text: string;
}

interface Suggestion {
    type: 'action' | 'talking-point' | 'insight';
    priority: 'high' | 'normal';
    title: string;
    content: string;
}

interface Script {
    id: string;
    text: string;
    context: string;
    used: boolean;
    timestamp: Date;
}

const isRecording = ref(false);
const isProcessing = ref(false);
const showTranscript = ref(false);
const transcript = ref<TranscriptEntry[]>([]);
const suggestions = ref<Suggestion[]>([]);
const scripts = ref<Script[]>([]);
const metrics = ref({
    talkRatio: 50,
    duration: 0,
    sentiment: 'neutral' as 'positive' | 'negative' | 'neutral',
    topics: [] as string[],
});

const mediaRecorder = ref<MediaRecorder | null>(null);
const audioChunks = ref<Blob[]>([]);
const recordingInterval = ref<number | null>(null);
const startTime = ref<Date | null>(null);
const conversationBuffer = ref<string>('');

// Computed properties for script management
const activeScript = computed(() => {
    return scripts.value.find((s) => !s.used) || null;
});

const upcomingScripts = computed(() => {
    return scripts.value.filter((s) => !s.used).slice(1, 4);
});

const currentScripts = computed(() => {
    return scripts.value.filter((s) => !s.used);
});

const formatTime = (date: Date) => {
    return date.toLocaleTimeString('en-US', {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
    });
};

const formatDuration = (seconds: number) => {
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;
    return `${mins}:${secs.toString().padStart(2, '0')}`;
};

const toggleTranscript = () => {
    showTranscript.value = !showTranscript.value;
};

const markScriptAsUsed = (scriptId: string) => {
    const script = scripts.value.find((s) => s.id === scriptId);
    if (script) {
        script.used = true;
    }
};

const startRecording = async () => {
    try {
        // For now, just use microphone. In production, we'd capture system audio
        const stream = await navigator.mediaDevices.getUserMedia({ audio: true });

        mediaRecorder.value = new MediaRecorder(stream);
        audioChunks.value = [];
        startTime.value = new Date();

        mediaRecorder.value.ondataavailable = (event) => {
            if (event.data.size > 0) {
                audioChunks.value.push(event.data);
            }
        };

        // Start recording in chunks
        mediaRecorder.value.start();
        isRecording.value = true;

        // Process audio chunks every 2 seconds for faster response
        recordingInterval.value = window.setInterval(() => {
            if (mediaRecorder.value && mediaRecorder.value.state === 'recording') {
                mediaRecorder.value.stop();
                processAudioChunk();

                // Restart recording for next chunk
                audioChunks.value = [];
                mediaRecorder.value.start();
            }
        }, 2000);

        // Update duration
        setInterval(() => {
            if (startTime.value) {
                metrics.value.duration = Math.floor((Date.now() - startTime.value.getTime()) / 1000);
            }
        }, 1000);
    } catch (err) {
        console.error('Error starting recording:', err);
    }
};

const stopRecording = () => {
    if (mediaRecorder.value) {
        mediaRecorder.value.stop();
        mediaRecorder.value.stream.getTracks().forEach((track) => track.stop());
        mediaRecorder.value = null;
    }

    if (recordingInterval.value) {
        clearInterval(recordingInterval.value);
        recordingInterval.value = null;
    }

    isRecording.value = false;
    startTime.value = null;
};

const toggleRecording = () => {
    if (isRecording.value) {
        stopRecording();
    } else {
        startRecording();
    }
};

const minimizeWindow = () => {
    // In Electron, we can minimize the window
    if (window.Native) {
        window.Native.minimize();
    }
};

const processAudioChunk = async () => {
    if (audioChunks.value.length === 0) return;

    const audioBlob = new Blob(audioChunks.value, { type: 'audio/webm' });

    // Convert to base64
    const reader = new FileReader();
    reader.readAsDataURL(audioBlob);
    reader.onloadend = async () => {
        const base64Audio = reader.result as string;
        const base64Data = base64Audio.split(',')[1];

        // Show processing indicator
        isProcessing.value = true;

        try {
            // Send to backend with timeout
            const response = await axios.post(
                '/api/assistant/analyze-conversation',
                {
                    audio: base64Data,
                    context: conversationBuffer.value || '',
                    mode: 'sales-coaching',
                },
                {
                    timeout: 8000, // 8 second timeout
                },
            );

            if (response.data.transcript) {
                // Add to transcript immediately
                transcript.value.push({
                    timestamp: new Date(),
                    speaker: 'you', // In real app, we'd detect speaker
                    text: response.data.transcript,
                });

                // Update conversation buffer
                conversationBuffer.value = [...transcript.value]
                    .slice(-10)
                    .map((t) => `${t.speaker}: ${t.text}`)
                    .join('\n');
            }

            if (response.data.suggestions) {
                // Update suggestions - don't replace all, merge smartly
                response.data.suggestions.forEach((newSugg: Suggestion) => {
                    // Check if similar suggestion exists
                    const exists = suggestions.value.some((s) => s.title === newSugg.title || s.content === newSugg.content);
                    if (!exists) {
                        suggestions.value.unshift(newSugg);
                        // Keep only last 5 suggestions
                        if (suggestions.value.length > 5) {
                            suggestions.value.pop();
                        }
                    }
                });

                // Generate scripts based on suggestions
                generateScripts(response.data.suggestions);
            }

            if (response.data.metrics) {
                // Update metrics smoothly
                Object.assign(metrics.value, response.data.metrics);
            }
        } catch (err) {
            if (axios.isAxiosError(err) && err.code === 'ECONNABORTED') {
                console.error('Request timeout - processing took too long');
            } else {
                console.error('Error processing audio chunk:', err);
            }
        } finally {
            isProcessing.value = false;
        }
    };
};

const generateScripts = (suggestions: Suggestion[]) => {
    // Generate teleprompter scripts from suggestions
    suggestions.forEach((suggestion) => {
        if (suggestion.type === 'talking-point' && suggestion.priority === 'high') {
            // Check if we already have this script
            const exists = scripts.value.some((s) => s.text === suggestion.content);
            if (!exists) {
                scripts.value.push({
                    id: Date.now().toString() + Math.random(),
                    text: suggestion.content,
                    context: suggestion.title,
                    used: false,
                    timestamp: new Date(),
                });
            }
        }
    });

    // Remove old unused scripts (older than 1 minute)
    const oneMinuteAgo = new Date(Date.now() - 60000);
    scripts.value = scripts.value.filter((s) => s.used || s.timestamp > oneMinuteAgo);
};

// Demo data for visualization
onMounted(() => {
    // Add demo suggestions
    suggestions.value = [
        {
            type: 'action',
            priority: 'high',
            title: 'Address Budget Concern',
            content: 'Customer mentioned budget constraints. Show ROI calculation.',
        },
        {
            type: 'talking-point',
            priority: 'normal',
            title: 'Value Proposition',
            content: 'Emphasize time savings: 10 hours per week on average.',
        },
    ];

    // Add demo scripts
    scripts.value = [
        {
            id: '1',
            text: 'I understand budget is a key consideration. Let me show you how our solution typically pays for itself within 3 months through efficiency gains.',
            context: 'Budget Objection Response',
            used: false,
            timestamp: new Date(),
        },
        {
            id: '2',
            text: 'Our clients typically see a 40% reduction in manual tasks, which translates to about 10 hours saved per week.',
            context: 'Value Proposition',
            used: false,
            timestamp: new Date(),
        },
    ];

    // Demo metrics
    metrics.value.topics = ['pricing', 'efficiency', 'integration'];
});

onUnmounted(() => {
    stopRecording();
});
</script>
