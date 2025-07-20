<template>
    <div class="col-span-1">
        <div class="flex h-[600px] flex-col rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
            <!-- Transcription Header -->
            <div class="border-b border-gray-200 px-4 py-3 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Live Transcription</h3>
                    <div class="flex items-center gap-3">
                        <div class="flex items-center gap-1.5">
                            <div class="h-2 w-2 rounded-full bg-green-500"></div>
                            <span class="text-xs text-gray-600 dark:text-gray-400">You</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <div class="h-2 w-2 rounded-full bg-green-500"></div>
                            <span class="text-xs text-gray-600 dark:text-gray-400">Customer</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Transcription Content - Reversed order, newest first -->
            <div
                ref="transcriptContainer"
                class="scrollbar-thin scrollbar-thumb-gray-300 dark:scrollbar-thumb-gray-600 scrollbar-track-transparent flex flex-1 flex-col-reverse overflow-y-auto p-4 pb-8"
            >
                <div v-if="transcriptGroups.length === 0" class="py-12 text-center">
                    <p class="text-sm text-gray-600 dark:text-gray-400">Waiting for conversation to begin...</p>
                </div>

                <div
                    v-for="group in [...transcriptGroups].reverse()"
                    :key="group.id"
                    class="mb-3"
                    :class="[
                        'group relative',
                        group.role === 'salesperson' ? 'pr-12 pl-0' : '',
                        group.role === 'customer' ? 'pr-0 pl-12' : '',
                        group.role === 'system' ? 'pr-6 pl-6' : '',
                    ]"
                >
                    <div
                        :class="[
                            'animate-fadeIn mb-2 rounded-lg p-3',
                            group.role === 'salesperson' ? 'bg-blue-50 text-right dark:bg-blue-900/20' : '',
                            group.role === 'customer' ? 'bg-gray-50 text-left dark:bg-gray-800' : '',
                            group.role === 'system' ? 'bg-yellow-50 text-center text-sm dark:bg-yellow-900/20' : '',
                        ]"
                    >
                        <div class="flex-1">
                            <p v-for="(msg, idx) in group.messages" :key="idx" :class="{ 'mt-1': idx > 0 }">
                                {{ msg.text }}
                            </p>
                        </div>
                        <span class="mt-2 block text-xs text-gray-600 dark:text-gray-400">
                            {{ formatTime(group.startTime) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, ref, watch, nextTick } from 'vue';
import { useRealtimeAgentStore } from '@/stores/realtimeAgent';

// Store
const realtimeStore = useRealtimeAgentStore();

// Refs
const transcriptContainer = ref<HTMLElement>();

// Computed
const transcriptGroups = computed(() => realtimeStore.transcriptGroups);

// Watch for new messages to auto-scroll
watch(
    () => transcriptGroups.value.length,
    async () => {
        await nextTick();
        if (transcriptContainer.value) {
            // Since we're using flex-col-reverse, scrollTop = 0 is the bottom
            transcriptContainer.value.scrollTop = 0;
        }
    }
);

// Methods
const formatTime = (timestamp: number) => {
    const date = new Date(timestamp);
    const hours = date.getHours().toString().padStart(2, '0');
    const minutes = date.getMinutes().toString().padStart(2, '0');
    const seconds = date.getSeconds().toString().padStart(2, '0');
    return `${hours}:${minutes}:${seconds}`;
};
</script>