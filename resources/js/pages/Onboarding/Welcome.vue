<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Head, router, useForm } from '@inertiajs/vue3';
import { AlertCircle, ArrowRight, ExternalLink, Key } from 'lucide-vue-next';
import { ref } from 'vue';

const form = useForm({
    openai_api_key: '',
});

const showApiKey = ref(false);
const isValidating = ref(false);

const submit = () => {
    form.post('/onboarding', {
        onStart: () => {
            isValidating.value = true;
        },
        onFinish: () => {
            isValidating.value = false;
        },
    });
};

const skipOnboarding = () => {
    router.post('/onboarding/skip');
};
</script>

<template>
    <Head title="Welcome - Setup OpenAI" />

    <div class="flex min-h-screen items-center justify-center bg-gray-50 px-4 py-12 sm:px-6 lg:px-8 dark:bg-gray-900">
        <div class="w-full max-w-md space-y-8">
            <div class="text-center">
                <h1 class="text-4xl font-bold tracking-tight text-gray-900 dark:text-gray-100">Welcome to Clueless</h1>
                <p class="mt-2 text-lg text-gray-600 dark:text-gray-400">Let's get you set up with OpenAI</p>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Key class="h-5 w-5" />
                        OpenAI API Key Setup
                    </CardTitle>
                    <CardDescription>
                        To use AI features, you'll need an OpenAI API key. Your key will be encrypted and stored securely.
                    </CardDescription>
                </CardHeader>

                <form @submit.prevent="submit">
                    <CardContent class="space-y-4">
                        <div class="space-y-2">
                            <Label for="api-key">API Key</Label>
                            <div class="relative">
                                <Input
                                    id="api-key"
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
                            <p class="text-sm text-gray-500">
                                Don't have an API key?
                                <a
                                    href="https://platform.openai.com/api-keys"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="inline-flex items-center gap-1 text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300"
                                >
                                    Get one from OpenAI
                                    <ExternalLink class="h-3 w-3" />
                                </a>
                            </p>
                        </div>

                        <div v-if="form.errors.openai_api_key" class="flex items-center gap-2 rounded-md border border-red-500 bg-red-50 p-3 text-sm text-red-800 dark:border-red-700 dark:bg-red-950 dark:text-red-200">
                            <AlertCircle class="h-4 w-4" />
                            <span>{{ form.errors.openai_api_key }}</span>
                        </div>
                    </CardContent>

                    <CardFooter class="flex flex-col gap-3">
                        <Button type="submit" class="w-full" :disabled="form.processing || !form.openai_api_key">
                            <span v-if="isValidating">Validating...</span>
                            <span v-else class="flex items-center gap-2">
                                Continue
                                <ArrowRight class="h-4 w-4" />
                            </span>
                        </Button>

                        <Button type="button" variant="ghost" class="w-full" @click="skipOnboarding" :disabled="form.processing">
                            Skip for now (use system API key)
                        </Button>
                    </CardFooter>
                </form>
            </Card>

            <div class="text-center text-sm text-gray-500 dark:text-gray-400">
                <p>You can always update your API key later in Settings</p>
            </div>
        </div>
    </div>
</template>
