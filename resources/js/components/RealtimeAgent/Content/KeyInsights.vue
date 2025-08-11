<template>
    <div
        class="flex h-full flex-col overflow-hidden rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900"
    >
        <h3 class="mb-3 text-sm font-semibold text-gray-900 dark:text-gray-100">Key Insights</h3>

        <div v-if="insights.length === 0" class="py-4 text-center">
            <p class="text-xs text-gray-600 dark:text-gray-400">Listening for insights...</p>
        </div>

        <div
            v-else
            class="scrollbar-thin scrollbar-thumb-gray-300 dark:scrollbar-thumb-gray-600 scrollbar-track-transparent flex-1 min-h-0 space-y-3 overflow-y-auto"
        >
            <div v-for="insight in recentInsights" :key="insight.id" class="animate-fadeIn">
                <!-- Question with Answer -->
                <div v-if="insight.type === 'question_with_answer'" class="space-y-2">
                    <div class="flex items-start gap-2">
                        <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400">
                            Q&A
                        </span>
                        <div class="flex-1 space-y-1">
                            <p class="text-xs font-medium text-gray-900 dark:text-gray-100">
                                {{ insight.question }}
                            </p>
                            <p class="text-xs text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-800 p-2 rounded">
                                {{ insight.answer }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Regular Insights -->
                <div v-else class="flex items-start gap-2">
                    <span
                        :class="[
                            'inline-flex items-center rounded-full px-2 py-1 text-xs font-medium',
                            insightTypeClass(insight.type),
                        ]"
                    >
                        {{ formatInsightType(insight.type) }}
                    </span>
                    <p class="flex-1 text-xs text-gray-900 dark:text-gray-100">{{ insight.text }}</p>
                </div>
            </div>
        </div>

        <!-- Question Detection Status -->
        <div v-if="isProcessingQuestion" class="mt-3 p-2 bg-blue-50 dark:bg-blue-900/20 rounded text-xs text-blue-700 dark:text-blue-300">
            <div class="flex items-center gap-2">
                <div class="animate-spin rounded-full h-3 w-3 border-b-2 border-blue-600"></div>
                AI is analyzing conversation for questions and generating answers...
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, watch, ref } from 'vue';
import { useRealtimeAgentStore } from '@/stores/realtimeAgent';
import axios from 'axios';

// Store
const realtimeStore = useRealtimeAgentStore();

// State
const isProcessingQuestion = ref(false);

// Computed
const insights = computed(() => realtimeStore.insights);
const recentInsights = computed(() => realtimeStore.recentInsights);
const conversationContext = computed(() => realtimeStore.conversationContext);
const lastCustomerMessage = computed(() => realtimeStore.lastCustomerMessage);

// Methods
const formatInsightType = (type: string) => {
    return type.replace('_', ' ');
};

const insightTypeClass = (type: string) => {
    const classMap: Record<string, string> = {
        'pain_point': 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400',
        'objection': 'bg-yellow-100 text-yellow-800 dark:bg-red-900/20 dark:text-yellow-400',
        'positive_signal': 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400',
        'concern': 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400',
        'question': 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400',
        'question_with_answer': 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400',
    };
    
    return classMap[type] || 'bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-400';
};

// Question detection logic - now using OpenAI
const detectQuestionsWithAI = async (text: string): Promise<string[]> => {
    try {
        const response = await axios.post('/api/openai/chat', {
            prompt: `Analyze this text and identify if it contains any questions or areas where the person needs help/clarification. 
                     
                     Text: "${text}"
                     
                     Return ONLY a JSON array of questions found. If no questions, return empty array [].
                     
                     Examples of what to detect:
                     - Direct questions: "What is the time complexity?"
                     - Implicit questions: "I'm confused about recursion"
                     - Help requests: "Can you explain this?"
                     - Problem statements: "I'm having trouble with this algorithm"
                     
                     Format: ["question1", "question2"]`,
            conversation_context: conversationContext.value,
            last_customer_message: lastCustomerMessage.value,
        });
        
        try {
            // Parse the AI response as JSON
            const questions = JSON.parse(response.data.response || '[]');
            return Array.isArray(questions) ? questions : [];
        } catch (parseError) {
            console.error('Failed to parse AI response as JSON:', parseError);
            return [];
        }
    } catch (error) {
        console.error('Failed to detect questions with AI:', error);
        return [];
    }
};

// Get AI answer for a question
const getAIAnswer = async (question: string): Promise<string> => {
    try {
        const response = await axios.post('/api/openai/chat', {
            prompt: `Please provide a clear, helpful answer to this question: "${question}". 
                     
                     Context: This is a tech interview (coding and ML interviews).
                     
                     Requirements:
                     - Keep the answer concise but comprehensive
                     - If it's a coding question, provide practical examples
                     - If it's a conceptual question, explain clearly with examples
                     - If it's a problem statement, provide guidance and solutions
                     - Make it interview-appropriate (not too long, but thorough)
                     
                     Answer:`,
            conversation_context: conversationContext.value,
            last_customer_message: lastCustomerMessage.value,
        });
        
        return response.data.response || 'Unable to generate answer at this time.';
    } catch (error) {
        console.error('Failed to get AI answer:', error);
        return 'Unable to generate answer due to technical issues.';
    }
};

// Process new messages for questions using AI
const processForQuestions = async (text: string) => {
    if (!text || text.length < 20) return; // Skip very short messages
    
    isProcessingQuestion.value = true;
    
    try {
        // Use AI to detect questions
        const questions = await detectQuestionsWithAI(text);
        
        if (questions.length === 0) return;
        
        // Process each detected question
        for (const question of questions) {
            // Check if we already have an answer for this question
            const existingQuestion = insights.value.find(
                insight => insight.type === 'question_with_answer' && 
                          insight.question?.toLowerCase().includes(question.toLowerCase().substring(0, 30))
            );
            
            if (existingQuestion) continue; // Skip if already answered
            
            // Get AI answer
            const answer = await getAIAnswer(question);
            
            // Add to insights
            realtimeStore.addQuestionWithAnswer(question, answer, 'high');
        }
    } catch (error) {
        console.error('Error processing questions:', error);
    } finally {
        isProcessingQuestion.value = false;
    }
};

// Watch for new customer messages to detect questions
watch(lastCustomerMessage, (newMessage) => {
    if (newMessage && realtimeStore.isActive) {
        processForQuestions(newMessage);
    }
});

// Watch for new transcript groups to detect questions
watch(() => realtimeStore.transcriptGroups, (newGroups, oldGroups) => {
    if (!realtimeStore.isActive || !oldGroups) return;
    
    // Check if new groups were added
    if (newGroups.length > oldGroups.length) {
        const newGroupsAdded = newGroups.slice(oldGroups.length);
        
        newGroupsAdded.forEach(group => {
            if (group.role === 'customer') {
                group.messages.forEach(message => {
                    processForQuestions(message.text);
                });
            }
        });
    }
}, { deep: true });
</script>