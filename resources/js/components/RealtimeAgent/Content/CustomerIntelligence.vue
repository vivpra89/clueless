<template>
    <div
        class="rounded-lg border border-gray-200 bg-white p-4 transition-all duration-300 dark:border-gray-700 dark:bg-gray-900"
        :class="{ 'animate-fadeIn': intelligenceUpdating }"
    >
        <h3 class="mb-3 flex items-center gap-2 text-sm font-semibold text-gray-900 dark:text-gray-100">
            Tech Interview Rating
            <span v-if="intelligenceUpdating" class="animate-pulse text-xs text-gray-900 dark:text-gray-100">
                Updating...
            </span>
        </h3>

        <!-- Overall Interview Score -->
        <div class="mb-4">
            <div class="mb-2 flex items-center justify-between">
                <p class="text-xs text-gray-600 dark:text-gray-400">Overall Score</p>
                <p class="text-lg font-bold text-gray-900 dark:text-gray-100">
                    {{ interviewScore }}/10
                </p>
            </div>
            <div class="relative h-3 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                <div
                    class="h-full transition-all duration-500 ease-out"
                    :class="scoreColorClass"
                    :style="{ width: `${interviewScore * 10}%` }"
                ></div>
            </div>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                {{ scoreDescription }}
            </p>
        </div>

        <!-- Tech Interview Metrics -->
        <div class="mb-4 grid grid-cols-2 gap-3">
            <div class="rounded-lg bg-gray-100 p-3 dark:bg-gray-800">
                <p class="text-xs text-gray-600 dark:text-gray-400">Problem Solving</p>
                <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                    {{ problemSolvingScore }}%
                </p>
            </div>
            <div class="rounded-lg bg-gray-100 p-3 dark:bg-gray-800">
                <p class="text-xs text-gray-600 dark:text-gray-400">Communication</p>
                <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                    {{ communicationScore }}%
                </p>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useRealtimeAgentStore } from '@/stores/realtimeAgent';

// Store
const realtimeStore = useRealtimeAgentStore();

// Computed
const customerIntelligence = computed(() => realtimeStore.customerIntelligence);
const intelligenceUpdating = computed(() => realtimeStore.intelligenceUpdating);

// Calculate interview score based on tech interview factors
const interviewScore = computed(() => {
    const engagement = customerIntelligence.value.engagementLevel;
    const sentiment = customerIntelligence.value.sentiment;
    
    let score = 5; // Base score
    
    // Engagement contribution (0-3 points)
    if (engagement >= 80) score += 3;
    else if (engagement >= 60) score += 2;
    else if (engagement >= 40) score += 1;
    
    // Sentiment contribution (0-2 points)
    if (sentiment === 'positive') score += 2;
    else if (sentiment === 'neutral') score += 1;
    
    // Round to nearest whole number
    return Math.round(score);
});

// Score color and description for tech interviews
const scoreColorClass = computed(() => {
    if (interviewScore.value >= 8) return 'bg-green-500';
    if (interviewScore.value >= 6) return 'bg-yellow-500';
    if (interviewScore.value >= 4) return 'bg-orange-500';
    return 'bg-red-500';
});

const scoreDescription = computed(() => {
    if (interviewScore.value >= 8) return 'Excellent! Strong technical communication';
    if (interviewScore.value >= 6) return 'Good! Focus on problem clarity';
    if (interviewScore.value >= 4) return 'Fair. Improve code explanation';
    return 'Needs work. Practice problem breakdown';
});

// Tech interview specific scores
const problemSolvingScore = computed(() => {
    // Base on engagement and sentiment, but focus on problem-solving indicators
    let score = 50;
    
    if (customerIntelligence.value.engagementLevel >= 80) score += 30;
    else if (customerIntelligence.value.engagementLevel >= 60) score += 20;
    else if (customerIntelligence.value.engagementLevel >= 40) score += 10;
    
    if (customerIntelligence.value.sentiment === 'positive') score += 20;
    else if (customerIntelligence.value.sentiment === 'neutral') score += 10;
    
    return Math.min(100, score);
});

const communicationScore = computed(() => {
    // Base on engagement and sentiment
    let score = 50;
    
    if (customerIntelligence.value.engagementLevel >= 80) score += 30;
    else if (customerIntelligence.value.engagementLevel >= 60) score += 20;
    else if (customerIntelligence.value.engagementLevel >= 40) score += 10;
    
    if (customerIntelligence.value.sentiment === 'positive') score += 20;
    else if (customerIntelligence.value.sentiment === 'neutral') score += 10;
    
    return Math.min(100, score);
});
</script>

