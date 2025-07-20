<template>
    <div class="relative">
        <button
            @click="toggleDropdown"
            :disabled="isActive"
            class="flex items-center gap-1.5 text-xs text-gray-600 transition-colors hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100"
            :class="{ 'cursor-not-allowed opacity-50': isActive }"
        >
            <span>Coach:</span>
            <span class="font-medium text-gray-800 dark:text-gray-200">
                {{ selectedTemplate?.name || 'Select' }}
            </span>
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
                    v-model="searchQuery"
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
                    @click="selectTemplate(template)"
                    class="cursor-pointer px-3 py-1.5 transition-colors hover:bg-gray-50/50 dark:hover:bg-gray-700/50"
                    :class="{ 
                        'border-l-2 border-blue-500 bg-blue-50/50': selectedTemplate?.id === template.id 
                    }"
                >
                    <div class="flex items-center gap-2">
                        <span class="text-sm">{{ getIconEmoji(template.icon) }}</span>
                        <p class="text-xs text-gray-900 dark:text-gray-100">{{ template.name }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useRealtimeAgentStore } from '@/stores/realtimeAgent';
import { useSettingsStore } from '@/stores/settings';

// Stores
const realtimeStore = useRealtimeAgentStore();
const settingsStore = useSettingsStore();

// Computed
const isActive = computed(() => realtimeStore.isActive);
const selectedTemplate = computed(() => realtimeStore.selectedTemplate);
const templates = computed(() => realtimeStore.templates);
const showCoachDropdown = computed(() => settingsStore.showCoachDropdown);
const searchQuery = computed({
    get: () => settingsStore.templateSearchQuery,
    set: (value) => settingsStore.setTemplateSearchQuery(value)
});

const filteredTemplates = computed(() => {
    if (!searchQuery.value) return templates.value;
    
    const query = searchQuery.value.toLowerCase();
    return templates.value.filter(template => 
        template.name.toLowerCase().includes(query)
    );
});

// Methods
const toggleDropdown = () => {
    settingsStore.toggleCoachDropdown();
};

const selectTemplate = (template: any) => {
    realtimeStore.setSelectedTemplate(template);
    settingsStore.closeAllDropdowns();
    settingsStore.setTemplateSearchQuery('');
};

const getIconEmoji = (icon?: string) => {
    if (!icon) return '📋';
    
    const iconMap: Record<string, string> = {
        'discovery': '🔍',
        'demo': '🎯',
        'negotiation': '💰',
        'support': '🛟',
        'onboarding': '🚀',
        'feedback': '💭',
        'renewal': '🔄',
        'upsell': '📈',
        'default': '📋'
    };
    
    return iconMap[icon] || iconMap.default;
};
</script>