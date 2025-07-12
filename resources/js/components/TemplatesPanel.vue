<script setup lang="ts">
import BaseCard from '@/components/design/BaseCard.vue';
import Button from '@/components/ui/button/Button.vue';
import Input from '@/components/ui/input/Input.vue';
import axios from 'axios';
import { BookOpen, Bug, CheckSquare, Code2, FileText, Lightbulb, MessageSquare, Plus as PlusIcon, X as XIcon, Zap } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';

interface Template {
    id: string;
    name: string;
    description: string;
    prompt: string;
    icon: string;
    category: string;
    is_system: boolean;
    usage_count: number;
}

const emit = defineEmits<{
    close: [];
    select: [template: Template];
    create: [];
}>();

const searchQuery = ref('');
const templates = ref<Template[]>([]);
const loading = ref(false);

const filteredTemplates = computed(() => {
    if (!searchQuery.value) return templates.value;

    const query = searchQuery.value.toLowerCase();
    return templates.value.filter(
        (template) =>
            template.name.toLowerCase().includes(query) ||
            template.description.toLowerCase().includes(query) ||
            template.category.toLowerCase().includes(query),
    );
});

const iconMap: Record<string, any> = {
    Code2,
    Bug,
    FileText,
    Lightbulb,
    MessageSquare,
    Zap,
    BookOpen,
    CheckSquare,
};

const getIcon = (iconName: string) => {
    return iconMap[iconName] || MessageSquare;
};

const selectTemplate = async (template: Template) => {
    // Track usage
    try {
        await axios.post(route('templates.use', template.id));
    } catch (error) {
        console.error('Failed to track template usage:', error);
    }
    emit('select', template);
};

const createTemplate = () => {
    emit('create');
};

const fetchTemplates = async () => {
    loading.value = true;
    try {
        const response = await axios.get(route('templates.index'));
        templates.value = response.data.templates;
    } catch (error) {
        console.error('Failed to fetch templates:', error);
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    fetchTemplates();
});
</script>

<template>
    <div class="absolute right-0 bottom-full left-0 mb-2">
        <BaseCard class="flex max-h-96 flex-col" :no-padding="true">
            <!-- Header -->
            <div class="flex items-center justify-between border-b border-gray-200 p-4 dark:border-gray-700">
                <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Templates</h3>
                <button
                    @click="$emit('close')"
                    class="rounded-lg p-1 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-800 dark:hover:text-gray-300"
                >
                    <XIcon :size="20" />
                </button>
            </div>

            <!-- Search -->
            <div class="border-b border-gray-200 p-3 dark:border-gray-700">
                <Input v-model="searchQuery" type="text" placeholder="Search templates..." class="w-full" />
            </div>

            <!-- Templates List -->
            <div class="flex-1 overflow-y-auto p-2">
                <div v-if="loading" class="py-8 text-center">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Loading templates...</p>
                </div>
                <div v-else-if="filteredTemplates.length === 0" class="py-8 text-center">
                    <p class="text-sm text-gray-500 dark:text-gray-400">No templates found</p>
                </div>
                <div v-else class="space-y-1">
                    <button
                        v-for="template in filteredTemplates"
                        :key="template.id"
                        @click="selectTemplate(template)"
                        class="flex w-full items-center gap-3 rounded-lg p-3 text-left transition-colors hover:bg-gray-50 dark:hover:bg-gray-800"
                    >
                        <div
                            class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400"
                        >
                            <component :is="getIcon(template.icon)" :size="18" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                {{ template.name }}
                            </h4>
                            <p class="truncate text-xs text-gray-500 dark:text-gray-400">
                                {{ template.description }}
                            </p>
                        </div>
                    </button>
                </div>
            </div>

            <!-- Footer -->
            <div class="border-t border-gray-200 p-3 dark:border-gray-700">
                <Button @click="createTemplate" variant="outline" class="w-full" size="sm">
                    <PlusIcon :size="16" class="mr-2" />
                    Create Template
                </Button>
            </div>
        </BaseCard>
    </div>
</template>
