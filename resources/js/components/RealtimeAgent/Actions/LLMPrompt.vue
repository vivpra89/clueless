<template>
    <div
        class="flex flex-col rounded-lg border border-gray-200 bg-white p-3 transition-all duration-300 dark:border-gray-700 dark:bg-gray-900"
    >
        <h3 class="mb-2 text-sm font-semibold text-gray-900 dark:text-gray-100">LLM Prompt</h3>

        <!-- Input Section -->
        <div class="mb-2">
            <div class="flex gap-2">
                <textarea
                    v-model="promptText"
                    placeholder="Ask the LLM anything about the conversation..."
                    class="flex-1 resize-none rounded border border-gray-200 bg-white px-3 py-2 text-xs focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200"
                    rows="3"
                    @keydown.ctrl.enter="sendPrompt"
                ></textarea>
                <button
                    @click="sendPrompt"
                    :disabled="!promptText.trim() || isProcessing"
                    class="flex-shrink-0 rounded bg-blue-500 px-3 py-2 text-xs font-medium text-white transition-colors hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 dark:focus:ring-offset-gray-900"
                >
                    <span v-if="isProcessing">Processing...</span>
                    <span v-else>Send</span>
                </button>
            </div>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                Press Ctrl+Enter to send
            </p>
        </div>

        <!-- Processing Indicator -->
        <div v-if="isProcessing" class="border-t border-gray-200 pt-2 dark:border-gray-700">
            <div class="flex items-center gap-2 text-xs text-gray-600 dark:text-gray-400">
                    <div class="h-4 w-4 animate-spin rounded-full border-2 border-gray-300 border-t-blue-500"></div>
                <span>Processing your request...</span>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { useRealtimeAgentStore } from '@/stores/realtimeAgent';
import axios from 'axios';

// Store
const realtimeStore = useRealtimeAgentStore();

// Props
interface Props {
    conversationContext?: string;
    lastCustomerMessage?: string;
    selectedTemplate?: any;
}

const props = withDefaults(defineProps<Props>(), {
    conversationContext: '',
    lastCustomerMessage: '',
    selectedTemplate: null
});

// Emits
const emit = defineEmits<{
    'llm-response': [prompt: string, response: string, timestamp: Date];
}>();

// Local state
const promptText = ref('');
const isProcessing = ref(false);

// Methods
const sendPrompt = async () => {
    if (!promptText.value.trim() || isProcessing.value) return;

    const prompt = promptText.value.trim();
    isProcessing.value = true;

    try {
        // Build context for the LLM
        const context = buildLLMContext(prompt);
        
        // Call OpenAI API
        const response = await callOpenAI(context);

        // Emit event for parent component
        emit('llm-response', prompt, response, new Date());

        // Clear input
        promptText.value = '';
    } catch (error) {
        console.error('LLM Error:', error);
        // Don't emit error responses, just log them
    } finally {
        isProcessing.value = false;
    }
};

const buildLLMContext = (prompt: string) => {
    let context = `You are an AI assistant helping with a sales conversation. Please provide a helpful, concise response to the following question:\n\nQuestion: ${prompt}\n\n`;
    
    if (props.conversationContext) {
        context += `Conversation Context: ${props.conversationContext}\n\n`;
    }
    
    if (props.lastCustomerMessage) {
        context += `Last Customer Message: "${props.lastCustomerMessage}"\n\n`;
    }
    
    if (props.selectedTemplate?.prompt) {
        context += `Sales Template Context: ${props.selectedTemplate.prompt}\n\n`;
    }
    
    context += `Please provide a clear, actionable response that helps the salesperson understand or act on the information requested.`;
    
    return context;
};

const callOpenAI = async (context: string) => {
    try {
        const response = await axios.post('/api/openai/chat', {
            messages: [
                {
                    role: 'user',
                    content: context
                }
            ],
            model: 'gpt-4o-mini',
            max_tokens: 500,
            temperature: 0.7
        });

        if (response.data && response.data.choices && response.data.choices[0]) {
            return response.data.choices[0].message.content;
        } else {
            throw new Error('Invalid response format from OpenAI');
        }
    } catch (error) {
        console.error('OpenAI API Error:', error);
        throw new Error('Failed to get response from OpenAI API');
    }
};
</script>