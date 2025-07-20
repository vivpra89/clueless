<template>
    <div
        class="flex-none rounded-lg border border-gray-200 bg-white p-4 transition-all duration-300 dark:border-gray-700 dark:bg-gray-800"
        :class="{ 'animate-fadeIn': intelligenceUpdating }"
    >
        <h3 class="mb-3 flex items-center gap-2 text-sm font-semibold text-gray-900 dark:text-gray-100">
            Customer Intelligence
            <span v-if="intelligenceUpdating" class="animate-pulse text-xs text-gray-900 dark:text-gray-100">
                Updating...
            </span>
        </h3>

        <!-- Intent & Stage -->
        <div class="mb-3 grid grid-cols-2 gap-3">
            <div class="rounded-lg bg-gray-50 p-3 dark:bg-gray-900">
                <p class="text-xs text-gray-600 dark:text-gray-400">Intent</p>
                <p class="text-sm font-medium text-gray-900 capitalize dark:text-gray-100">
                    {{ customerIntelligence.intent }}
                </p>
            </div>
            <div class="rounded-lg bg-gray-50 p-3 dark:bg-gray-900">
                <p class="text-xs text-gray-600 dark:text-gray-400">Stage</p>
                <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                    {{ customerIntelligence.buyingStage }}
                </p>
            </div>
        </div>

        <!-- Engagement Level -->
        <div class="omega-metric-card">
            <div class="mb-2 flex items-center justify-between">
                <p class="omega-metric-label">Engagement Level</p>
                <p class="text-xs font-medium text-gray-900 dark:text-gray-100">
                    {{ customerIntelligence.engagementLevel }}%
                </p>
            </div>
            <div class="h-2 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                <div
                    class="h-full bg-blue-500 transition-all duration-300 ease-out"
                    :style="{ width: `${customerIntelligence.engagementLevel}%` }"
                ></div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useRealtimeAgentStore } from '@/stores/realtimeAgent';

// Store
const realtimeStore = useRealtimeAgentStore();

// Computed
const customerIntelligence = computed(() => realtimeStore.customerIntelligence);
const intelligenceUpdating = computed(() => realtimeStore.intelligenceUpdating);
</script>