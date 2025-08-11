<template>
    <div
        class="flex flex-col rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900"
    >
        <div class="mb-3 flex items-center justify-between">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">LLM Insights</h3>
            <div class="flex items-center gap-2">
                <div class="text-xs text-gray-600 dark:text-gray-400">
                    {{ llmResponses.length }} responses
                </div>
                <button
                    @click="clearResponses"
                    class="text-xs text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300"
                    v-if="llmResponses.length > 0"
                >
                    Clear All
                </button>
            </div>
        </div>

        <div
            class="scrollbar-thin scrollbar-thumb-gray-300 dark:scrollbar-thumb-gray-600 scrollbar-track-transparent flex-1 space-y-3 overflow-y-auto"
            v-if="llmResponses.length > 0"
        >
            <div 
                v-for="(response, index) in llmResponses" 
                :key="index" 
                class="rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-gray-600 dark:bg-gray-800"
            >
                <div class="mb-2 flex items-start justify-between">
                    <div class="text-sm font-medium text-gray-700 dark:text-gray-300">
                        {{ response.prompt.substring(0, 60) }}{{ response.prompt.length > 60 ? '...' : '' }}
                    </div>
                    <button
                        @click="removeResponse(index)"
                        class="text-sm text-gray-400 hover:text-red-500 dark:text-gray-500 dark:hover:text-red-400"
                    >
                        ×
                    </button>
                </div>
                <div class="text-sm text-gray-800 dark:text-gray-200 whitespace-pre-wrap leading-relaxed">
                    {{ response.response }}
                </div>
                <div class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                    {{ formatTime(response.timestamp) }}
                </div>
            </div>
        </div>

        <div v-else class="text-center py-6">
            <div class="text-gray-400 dark:text-gray-500 mb-2">
                <svg class="w-6 h-6 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                </svg>
            </div>
            <p class="text-xs text-gray-500 dark:text-gray-400">
                No LLM responses yet
            </p>
            <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                Use the LLM Prompt section to ask questions
            </p>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { useRealtimeAgentStore } from '@/stores/realtimeAgent';

// Store
const realtimeStore = useRealtimeAgentStore();

// Props
interface Props {
    llmResponses?: Array<{
        prompt: string;
        response: string;
        timestamp: Date;
    }>;
}

const props = withDefaults(defineProps<Props>(), {
    llmResponses: () => []
});

// Emits
const emit = defineEmits<{
    'remove-response': [index: number];
    'clear-responses': [];
}>();

// Computed
const llmResponses = computed(() => props.llmResponses);

// Methods
const removeResponse = (index: number) => {
    emit('remove-response', index);
};

const clearResponses = () => {
    emit('clear-responses');
};

const formatTime = (timestamp: Date) => {
    return timestamp.toLocaleTimeString([], { 
        hour: '2-digit', 
        minute: '2-digit',
        second: '2-digit'
    });
};
</script>