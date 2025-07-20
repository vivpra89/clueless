<template>
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
            {{ connectionStatus }}
        </span>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useRealtimeAgentStore } from '@/stores/realtimeAgent';

// Store
const realtimeStore = useRealtimeAgentStore();

// Computed
const connectionStatus = computed(() => realtimeStore.connectionStatus);
</script>