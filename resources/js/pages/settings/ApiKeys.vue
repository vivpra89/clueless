<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { AlertCircle, Check, Key, Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';

const page = usePage();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Settings',
        href: '/settings',
    },
    {
        title: 'API Keys',
        href: '/settings/api-keys',
    },
];

const form = useForm({
    openai_api_key: '',
});

const showApiKey = ref(false);
const isValidating = ref(false);
const hasApiKey = ref(page.props.hasApiKey || false);
const isUsingEnvKey = ref(page.props.isUsingEnvKey || false);

const updateApiKey = () => {
    form.put('/settings/api-keys', {
        onStart: () => {
            isValidating.value = true;
        },
        onSuccess: () => {
            form.reset();
            hasApiKey.value = true;
            alert('Success: Your OpenAI API key has been updated successfully.');
        },
        onFinish: () => {
            isValidating.value = false;
        },
    });
};

const deleteApiKey = () => {
    if (confirm('Are you sure you want to delete your API key? You will need to use the system API key or add a new one.')) {
        form.delete('/settings/api-keys', {
            onSuccess: () => {
                hasApiKey.value = false;
                alert('Your OpenAI API key has been removed.');
            },
        });
    }
};
</script>

<template>
    <Head title="API Keys" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <SettingsLayout>
            <div class="space-y-6">
                <div>
                    <h3 class="text-lg font-medium">API Keys</h3>
                    <p class="text-sm text-muted-foreground">Manage your API keys for AI services</p>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Key class="h-5 w-5" />
                            OpenAI API Key
                        </CardTitle>
                        <CardDescription>
                            Your OpenAI API key is used for AI features throughout the application.
                            <span v-if="!hasApiKey">No API key configured.</span>
                            <span v-else-if="isUsingEnvKey">Using API key from environment (.env file).</span>
                            <span v-else>Using API key configured in settings.</span>
                        </CardDescription>
                    </CardHeader>

                    <form @submit.prevent="updateApiKey">
                        <CardContent class="space-y-4">
                            <div v-if="hasApiKey" class="rounded-lg bg-green-50 p-4 dark:bg-green-900/20">
                                <div class="flex items-center gap-2">
                                    <Check class="h-5 w-5 text-green-600 dark:text-green-400" />
                                    <p class="text-sm font-medium text-green-800 dark:text-green-200">API Key Configured</p>
                                </div>
                                <p class="mt-1 text-sm text-green-700 dark:text-green-300">
                                    {{ isUsingEnvKey ? 'Using API key from .env file.' : 'Your personal API key is being used for AI features.' }}
                                </p>
                            </div>

                            <div class="space-y-2">
                                <Label for="new-api-key">
                                    {{ hasApiKey ? 'Update API Key' : 'Add API Key' }}
                                </Label>
                                <div class="relative">
                                    <Input
                                        id="new-api-key"
                                        v-model="form.openai_api_key"
                                        :type="showApiKey ? 'text' : 'password'"
                                        placeholder="sk-..."
                                        :disabled="form.processing"
                                        class="pr-20"
                                    />
                                    <button
                                        type="button"
                                        @click="showApiKey = !showApiKey"
                                        class="absolute top-1/2 right-2 -translate-y-1/2 text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100"
                                    >
                                        {{ showApiKey ? 'Hide' : 'Show' }}
                                    </button>
                                </div>
                                <p class="text-sm text-muted-foreground">
                                    Enter a new API key to
                                    {{ hasApiKey ? 'replace your existing key' : 'use your own key instead of the system default' }}.
                                </p>
                            </div>

                            <div v-if="form.errors.openai_api_key" class="flex items-center gap-2 rounded-md border border-red-500 bg-red-50 p-3 text-sm text-red-800 dark:border-red-700 dark:bg-red-950 dark:text-red-200">
                                <AlertCircle class="h-4 w-4" />
                                <span>{{ form.errors.openai_api_key }}</span>
                            </div>
                        </CardContent>

                        <CardFooter class="flex gap-3">
                            <Button type="submit" :disabled="form.processing || !form.openai_api_key">
                                <span v-if="isValidating">Validating...</span>
                                <span v-else>{{ hasApiKey ? 'Update' : 'Add' }} API Key</span>
                            </Button>

                            <Button v-if="hasApiKey" type="button" variant="destructive" @click="deleteApiKey" :disabled="form.processing">
                                <Trash2 class="mr-2 h-4 w-4" />
                                Delete API Key
                            </Button>
                        </CardFooter>
                    </form>
                </Card>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
