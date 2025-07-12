<script setup lang="ts">
interface Props {
    fullHeight?: boolean;
}

withDefaults(defineProps<Props>(), {
    fullHeight: true,
});
</script>

<template>
    <div :class="['flex flex-1 flex-col gap-4 rounded-xl bg-gray-50 p-4 dark:bg-gray-900', fullHeight && 'h-full']">
        <slot />
    </div>
</template>
