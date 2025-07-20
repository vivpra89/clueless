<script setup lang="ts">
import BaseCard from '@/components/design/BaseCard.vue';
import PageContainer from '@/components/design/PageContainer.vue';
import Button from '@/components/ui/button/Button.vue';
import Input from '@/components/ui/input/Input.vue';
import Label from '@/components/ui/label/Label.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import axios from 'axios';
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
    variables?: Record<string, string>;
    talking_points?: string[];
}

const props = defineProps<{
    template?: Template;
}>();

const isEditing = computed(() => !!props.template);
const saving = ref(false);

const form = ref({
    name: '',
    description: '',
    prompt: '',
    icon: 'MessageSquare',
    category: 'sales_coach',
    talking_points: [] as string[],
});

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Templates',
        href: '/templates',
    },
    {
        title: isEditing.value ? 'Edit Template' : 'Create Template',
        href: '#',
    },
];

const availableIcons = [
    'MessageSquare',
    'Zap',
    'Heart',
    'Shield',
    'TrendingUp',
    'Search',
    'Code2',
    'Smile',
    'Target',
    'Lightbulb',
    'DollarSign',
    'Calculator',
    'BookOpen',
    'Users',
    'Briefcase',
    'Award',
];

onMounted(() => {
    if (props.template) {
        form.value = {
            name: props.template.name,
            description: props.template.description || '',
            prompt: props.template.prompt,
            icon: props.template.icon,
            category: props.template.category,
            talking_points: props.template.talking_points || [],
        };
    }
});

// Talking points management
const addTalkingPoint = () => {
    form.value.talking_points.push('');
};

const removeTalkingPoint = (index: number) => {
    form.value.talking_points.splice(index, 1);
};

const getIconEmoji = (icon: string): string => {
    const iconMap: Record<string, string> = {
        MessageSquare: '💬',
        Zap: '⚡',
        Heart: '❤️',
        Shield: '🛡️',
        TrendingUp: '📈',
        Search: '🔍',
        Code2: '💻',
        Smile: '😊',
        Target: '🎯',
        Lightbulb: '💡',
        DollarSign: '💰',
        Calculator: '🔢',
        BookOpen: '📖',
        Users: '👥',
        Briefcase: '💼',
        Award: '🏆',
    };
    return iconMap[icon] || '📝';
};

const saveTemplate = async () => {
    saving.value = true;

    try {
        const payload = {
            ...form.value,
            variables: {
                product_name: 'Clueless',
                product_price: '$49/user/month',
                key_benefit: 'captures everything automatically, saves 11 hours/week',
            },
        };

        if (isEditing.value && props.template) {
            await axios.put(`/templates/${props.template.id}`, payload);
        } else {
            await axios.post('/templates', payload);
        }

        router.visit('/templates');
    } catch (error) {
        console.error('Failed to save template:', error);
        alert('Failed to save template. Please try again.');
    } finally {
        saving.value = false;
    }
};

const cancel = () => {
    router.visit('/templates');
};
</script>

<template>
    <Head :title="isEditing ? 'Edit Template' : 'Create Template'" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <PageContainer>
            <div class="mx-auto max-w-4xl">
                <!-- Header -->
                <div class="mb-6">
                    <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">
                        {{ isEditing ? 'Edit Template' : 'Create Template' }}
                    </h1>
                </div>

                <!-- Form -->
                <BaseCard>
                    <form @submit.prevent="saveTemplate" class="space-y-6">
                        <!-- Basic Information Section -->
                        <div class="space-y-4">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Basic Information</h2>

                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div class="space-y-2">
                                    <Label for="name">Template Name</Label>
                                    <Input id="name" v-model="form.name" placeholder="e.g., Friendly Consultant" required />
                                </div>

                                <div class="space-y-2">
                                    <Label for="icon">Icon</Label>
                                    <div class="flex flex-wrap gap-2 rounded-xl border border-sidebar-border/70 p-3 dark:border-sidebar-border">
                                        <button
                                            v-for="icon in availableIcons"
                                            :key="icon"
                                            type="button"
                                            @click="form.icon = icon"
                                            :class="[
                                                'flex h-10 w-10 items-center justify-center rounded-lg border transition-all',
                                                form.icon === icon
                                                    ? 'border-blue-600 bg-blue-50 dark:border-blue-400 dark:bg-blue-900/20'
                                                    : 'border-gray-200 hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800',
                                            ]"
                                        >
                                            <span class="text-sm">{{ getIconEmoji(icon) }}</span>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="space-y-2">
                                <Label for="description">Description</Label>
                                <Input id="description" v-model="form.description" placeholder="Brief description of this coaching style" />
                            </div>

                            <div class="space-y-2">
                                <Label for="prompt">Coach Instructions</Label>
                                <textarea
                                    id="prompt"
                                    v-model="form.prompt"
                                    rows="6"
                                    class="w-full resize-none rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                                    placeholder="Enter the coaching instructions for the AI..."
                                    required
                                />
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    Available variables: {product_name}, {product_price}, {key_benefit}
                                </p>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                    <strong>Pro tip:</strong> Structure your prompt with sections like PRODUCT INFO, PRICING, OBJECTION HANDLING, etc.
                                    for better contextual assistance.
                                </p>
                            </div>
                        </div>

                        <!-- Talking Points Section -->
                        <div class="space-y-4 border-t border-gray-200 pt-6 dark:border-gray-700">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Talking Points</h2>
                            <p class="text-sm text-gray-600 dark:text-gray-400">These points will be shown as a checklist during calls</p>

                            <div class="space-y-2">
                                <div v-for="(point, index) in form.talking_points" :key="`point-${index}`" class="flex gap-2">
                                    <Input v-model="form.talking_points[index]" placeholder="Enter a talking point..." class="flex-1" />
                                    <Button
                                        @click="removeTalkingPoint(index)"
                                        type="button"
                                        variant="ghost"
                                        size="sm"
                                        class="text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300"
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

                                <Button @click="addTalkingPoint" type="button" variant="outline" size="sm" class="w-full">
                                    <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                    </svg>
                                    Add Talking Point
                                </Button>
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="flex gap-3 border-t border-gray-200 pt-6 dark:border-gray-700">
                            <Button type="button" variant="outline" @click="cancel"> Cancel </Button>
                            <Button type="submit" :disabled="!form.name || !form.prompt || saving">
                                {{ saving ? 'Saving...' : isEditing ? 'Update Template' : 'Create Template' }}
                            </Button>
                        </div>
                    </form>
                </BaseCard>
            </div>
        </PageContainer>
    </AppLayout>
</template>
