<script setup lang="ts">
import BaseCard from '@/components/design/BaseCard.vue';
import EmptyState from '@/components/design/EmptyState.vue';
import PageContainer from '@/components/design/PageContainer.vue';
import Button from '@/components/ui/button/Button.vue';
import Input from '@/components/ui/input/Input.vue';
import Label from '@/components/ui/label/Label.vue';
import { useVariables } from '@/composables/useVariables';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import type { Variable } from '@/types/variable';
import { Head } from '@inertiajs/vue3';
import { Plus, Variable as VariableIcon } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';

const { variables, categoryStats, loading, error, loadVariables, saveVariable: saveVariableApi, deleteVariable: deleteVariableApi } = useVariables();

// UI State
const selectedCategory = ref<string | null>(null);
const showCreateDialog = ref(false);
const editingVariable = ref<Variable | null>(null);

// Form Data
const formData = ref({
    key: '',
    value: '',
    type: 'text' as Variable['type'],
    category: 'general',
    description: '',
});

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Variables',
        href: '/variables',
    },
];

// Computed
const filteredVariables = computed(() => {
    if (!selectedCategory.value) return variables.value;
    return variables.value.filter((v) => v.category === selectedCategory.value);
});

// Methods
const editVariable = (variable: Variable) => {
    editingVariable.value = variable;
    formData.value = {
        key: variable.key,
        value: variable.value,
        type: variable.type,
        category: variable.category,
        description: variable.description || '',
    };
    showCreateDialog.value = true;
};

const deleteVariable = async (variable: Variable) => {
    if (variable.is_system) return;

    if (confirm(`Are you sure you want to delete the variable "${variable.key}"?`)) {
        await deleteVariableApi(variable.key);
    }
};

const saveVariable = async () => {
    const result = await saveVariableApi(formData.value);
    if (result) {
        closeDialog();
    }
};

const closeDialog = () => {
    showCreateDialog.value = false;
    editingVariable.value = null;
    formData.value = {
        key: '',
        value: '',
        type: 'text',
        category: 'general',
        description: '',
    };
};

// Lifecycle
onMounted(() => {
    loadVariables();
});
</script>

<template>
    <Head title="Variables" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <PageContainer>
            <!-- Header -->
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">Variables Management</h1>
                    <p class="mt-1 text-gray-600 dark:text-gray-400">Define dynamic variables for use in your templates</p>
                </div>
                <Button @click="showCreateDialog = true">
                    <Plus :size="16" class="mr-2" />
                    Add Variable
                </Button>
            </div>

            <!-- Category Filter -->
            <div class="mb-4 flex items-center gap-2">
                <span class="text-sm text-gray-600 dark:text-gray-400"> Filter by category: </span>
                <div class="flex gap-2">
                    <button
                        @click="selectedCategory = null"
                        :class="[
                            'rounded-lg px-3 py-1 text-sm transition-colors',
                            selectedCategory === null
                                ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/20 dark:text-blue-400'
                                : 'bg-gray-100 text-gray-600 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700',
                        ]"
                    >
                        All ({{ variables.length }})
                    </button>
                    <button
                        v-for="stat in categoryStats"
                        :key="stat.name"
                        @click="selectedCategory = stat.name"
                        :class="[
                            'rounded-lg px-3 py-1 text-sm transition-colors',
                            selectedCategory === stat.name
                                ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/20 dark:text-blue-400'
                                : 'bg-gray-100 text-gray-600 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700',
                        ]"
                    >
                        {{ stat.name }} ({{ stat.count }})
                    </button>
                </div>
            </div>

            <!-- Loading state -->
            <div v-if="loading && variables.length === 0" class="flex items-center justify-center py-12">
                <p class="text-gray-500 dark:text-gray-400">Loading variables...</p>
            </div>

            <!-- Empty state -->
            <div v-else-if="filteredVariables.length === 0">
                <BaseCard>
                    <EmptyState :icon="VariableIcon" title="No variables found" description="Create your first variable to get started">
                        <template #action>
                            <Button @click="showCreateDialog = true">
                                <Plus :size="16" class="mr-2" />
                                Add Variable
                            </Button>
                        </template>
                    </EmptyState>
                </BaseCard>
            </div>

            <!-- Variables List -->
            <div v-else class="space-y-4">
                <BaseCard v-for="variable in filteredVariables" :key="variable.id">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="mb-1 flex items-center gap-2">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                    {{ variable.key }}
                                </h3>
                                <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-600 dark:bg-gray-800 dark:text-gray-400">
                                    {{ variable.type }}
                                </span>
                                <span
                                    v-if="variable.is_system"
                                    class="rounded-full bg-blue-100 px-2 py-0.5 text-xs text-blue-600 dark:bg-blue-900/20 dark:text-blue-400"
                                >
                                    system
                                </span>
                                <span
                                    class="rounded-full bg-purple-100 px-2 py-0.5 text-xs text-purple-600 dark:bg-purple-900/20 dark:text-purple-400"
                                >
                                    {{ variable.category }}
                                </span>
                            </div>
                            <p v-if="variable.description" class="mb-2 text-sm text-gray-600 dark:text-gray-400">
                                {{ variable.description }}
                            </p>
                            <div class="flex items-center gap-4">
                                <div class="flex items-center gap-2">
                                    <span class="text-sm text-gray-500 dark:text-gray-500"> Value: </span>
                                    <code class="rounded bg-gray-100 px-2 py-0.5 text-sm dark:bg-gray-800">
                                        {{ variable.value }}
                                    </code>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="text-sm text-gray-500 dark:text-gray-500"> Usage: </span>
                                    <code class="rounded bg-gray-100 px-2 py-0.5 text-sm dark:bg-gray-800">
                                        {{ '{' }}{{ variable.key }}{{ '}' }}
                                    </code>
                                </div>
                            </div>
                        </div>
                        <div class="ml-4 flex items-center gap-2">
                            <Button @click="editVariable(variable)" variant="ghost" size="sm" class="p-2">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                                    />
                                </svg>
                            </Button>
                            <Button
                                @click="deleteVariable(variable)"
                                variant="ghost"
                                size="sm"
                                class="p-2 text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300"
                                :disabled="variable.is_system"
                            >
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                    />
                                </svg>
                            </Button>
                        </div>
                    </div>
                </BaseCard>
            </div>

            <!-- Error Message -->
            <div v-if="error" class="mt-4 rounded-xl border border-red-200 bg-red-50 p-4 dark:border-red-800 dark:bg-red-900/20">
                <div class="flex items-center gap-2 text-red-600 dark:text-red-400">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>{{ error }}</span>
                </div>
            </div>

            <!-- Create/Edit Dialog -->
            <div v-if="showCreateDialog" class="bg-opacity-50 fixed inset-0 z-50 flex items-center justify-center bg-black p-4">
                <BaseCard class="w-full max-w-md">
                    <h3 class="mb-4 text-lg font-semibold text-gray-900 dark:text-gray-100">{{ editingVariable ? 'Edit' : 'Create' }} Variable</h3>

                    <div class="space-y-4">
                        <div>
                            <Label for="var-key">Key</Label>
                            <Input id="var-key" v-model="formData.key" placeholder="e.g., product_name" class="mt-1" :disabled="!!editingVariable" />
                        </div>

                        <div>
                            <Label for="var-value">Value</Label>
                            <Input id="var-value" v-model="formData.value" placeholder="e.g., DevHelper" class="mt-1" />
                        </div>

                        <div>
                            <Label for="var-type">Type</Label>
                            <select
                                id="var-type"
                                v-model="formData.type"
                                class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <option value="text">Text</option>
                                <option value="number">Number</option>
                                <option value="boolean">Boolean</option>
                                <option value="json">JSON</option>
                            </select>
                        </div>

                        <div>
                            <Label for="var-category">Category</Label>
                            <Input id="var-category" v-model="formData.category" placeholder="e.g., product" class="mt-1" />
                        </div>

                        <div>
                            <Label for="var-description">Description (optional)</Label>
                            <textarea
                                id="var-description"
                                v-model="formData.description"
                                placeholder="Brief description of this variable"
                                class="mt-1 h-20 w-full resize-none rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                            />
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end gap-2">
                        <Button @click="closeDialog" variant="outline">Cancel</Button>
                        <Button @click="saveVariable" :disabled="!formData.key || !formData.value">
                            {{ editingVariable ? 'Update' : 'Create' }}
                        </Button>
                    </div>
                </BaseCard>
            </div>
        </PageContainer>
    </AppLayout>
</template>
