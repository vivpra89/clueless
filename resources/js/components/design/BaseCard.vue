<script setup lang="ts">
interface Props {
    noPadding?: boolean;
    hover?: boolean;
    clickable?: boolean;
}

withDefaults(defineProps<Props>(), {
    noPadding: false,
    hover: false,
    clickable: false,
});
</script>

<template>
    <div
        :class="[
            'rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800',
            !noPadding && 'p-4',
            hover && 'transition-all hover:border-gray-300 hover:shadow-sm dark:hover:border-gray-600',
            clickable && 'cursor-pointer',
        ]"
    >
        <slot />
    </div>
</template>
