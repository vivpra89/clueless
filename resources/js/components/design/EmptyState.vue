<script setup lang="ts">
import { type Component } from 'vue';

interface Props {
    icon?: Component;
    title: string;
    description?: string;
}

defineProps<Props>();
</script>

<template>
    <div class="flex flex-col items-center justify-center py-12 text-center">
        <div v-if="icon" class="mb-4">
            <component :is="icon" class="h-16 w-16 text-gray-400 dark:text-gray-600" />
        </div>
        <h3 class="mb-2 text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ title }}
        </h3>
        <p v-if="description" class="text-sm text-gray-500 dark:text-gray-400">
            {{ description }}
        </p>
        <div v-if="$slots.action" class="mt-6">
            <slot name="action" />
        </div>
    </div>
</template>
