<template>
    <button
        @click="toggleOverlayMode"
        class="flex items-center gap-1.5 text-xs transition-colors"
        :class="[
            isOverlayMode
                ? 'text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300'
                : 'text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100',
        ]"
        :title="isOverlayMode ? 'Exit overlay mode' : 'Enter overlay mode'"
    >
        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
                v-if="!isOverlayMode"
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"
            />
            <path
                v-else
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
            />
        </svg>
        <span class="font-medium">
            {{ overlayStatusText }}
        </span>
    </button>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useSettingsStore } from '@/stores/settings';

// Store
const settingsStore = useSettingsStore();

// Computed
const isOverlayMode = computed(() => settingsStore.isOverlayMode);
const overlayStatusText = computed(() => settingsStore.overlayStatusText);

// Methods
const toggleOverlayMode = () => {
    settingsStore.toggleOverlayMode();
};
</script>