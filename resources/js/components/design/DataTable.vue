<script setup lang="ts">
interface Column {
    key: string;
    label: string;
    align?: 'left' | 'center' | 'right';
}

interface Props {
    columns: Column[];
    data: Record<string, any>[];
}

defineProps<Props>();
</script>

<template>
    <div class="overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-800">
                <tr>
                    <th
                        v-for="column in columns"
                        :key="column.key"
                        :class="[
                            'px-6 py-3 text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-400',
                            column.align === 'center' && 'text-center',
                            column.align === 'right' && 'text-right',
                            (!column.align || column.align === 'left') && 'text-left',
                        ]"
                    >
                        {{ column.label }}
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                <tr v-for="(row, index) in data" :key="index" class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-800">
                    <td
                        v-for="column in columns"
                        :key="column.key"
                        :class="[
                            'px-6 py-4 text-sm text-gray-900 dark:text-gray-100',
                            column.align === 'center' && 'text-center',
                            column.align === 'right' && 'text-right',
                            (!column.align || column.align === 'left') && 'text-left',
                        ]"
                    >
                        <slot :name="`cell-${column.key}`" :value="row[column.key]" :row="row">
                            {{ row[column.key] }}
                        </slot>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
