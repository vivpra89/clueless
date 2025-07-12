<template>
    <div class="audio-recorder">
        <button
            @click="toggleRecording"
            class="record-button"
            :class="{ recording: isRecording }"
            :disabled="isProcessing"
            :title="isRecording ? 'Stop recording' : 'Start recording'"
        >
            <MicIcon v-if="!isRecording" :size="20" />
            <SquareIcon v-else :size="20" />
        </button>
        <div v-if="isRecording" class="recording-indicator">
            <span class="pulse"></span>
            <span class="time">{{ formatTime(recordingTime) }}</span>
        </div>
        <div v-if="isProcessing" class="processing">Transcribing...</div>
    </div>
</template>

<script setup lang="ts">
import axios from 'axios';
import { Mic as MicIcon, Square as SquareIcon } from 'lucide-vue-next';
import { onUnmounted, ref } from 'vue';

const emit = defineEmits<{
    transcribed: [text: string];
    error: [error: string];
}>();

const isRecording = ref(false);
const isProcessing = ref(false);
const recordingTime = ref(0);
const mediaRecorder = ref<MediaRecorder | null>(null);
const audioChunks = ref<Blob[]>([]);
const recordingInterval = ref<number | null>(null);

const formatTime = (seconds: number) => {
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;
    return `${mins}:${secs.toString().padStart(2, '0')}`;
};

const startRecording = async () => {
    try {
        console.log('Starting recording...');
        const stream = await navigator.mediaDevices.getUserMedia({ audio: true });

        // Check supported mime types - try to use formats Whisper supports
        let mimeType = 'audio/webm'; // default

        // Try different formats in order of preference for Whisper
        const formats = ['audio/mp4', 'audio/mpeg', 'audio/wav', 'audio/webm', 'audio/ogg'];

        for (const format of formats) {
            if (MediaRecorder.isTypeSupported(format)) {
                mimeType = format;
                break;
            }
        }

        console.log('Using mimeType:', mimeType);

        mediaRecorder.value = new MediaRecorder(stream, {
            mimeType: mimeType,
        });

        audioChunks.value = [];

        mediaRecorder.value.ondataavailable = (event) => {
            console.log('Data available:', event.data.size);
            if (event.data.size > 0) {
                audioChunks.value.push(event.data);
            }
        };

        mediaRecorder.value.onstop = async () => {
            console.log('Recording stopped, processing audio...');
            const audioBlob = new Blob(audioChunks.value, { type: mimeType });
            console.log('Audio blob size:', audioBlob.size);
            await processAudio(audioBlob);

            // Stop all tracks
            stream.getTracks().forEach((track) => track.stop());
        };

        mediaRecorder.value.start(1000); // Capture data every second
        isRecording.value = true;
        recordingTime.value = 0;

        // Start timer
        recordingInterval.value = window.setInterval(() => {
            recordingTime.value++;
        }, 1000);

        console.log('Recording started successfully');
    } catch (error) {
        console.error('Error starting recording:', error);
        emit('error', 'Failed to start recording. Please check your microphone permissions.');
    }
};

const stopRecording = () => {
    console.log('Stopping recording...');
    if (mediaRecorder.value && isRecording.value) {
        mediaRecorder.value.stop();
        isRecording.value = false;

        if (recordingInterval.value) {
            clearInterval(recordingInterval.value);
            recordingInterval.value = null;
        }
        console.log('Recording stop initiated');
    }
};

const toggleRecording = () => {
    if (isRecording.value) {
        stopRecording();
    } else {
        startRecording();
    }
};

const processAudio = async (audioBlob: Blob) => {
    isProcessing.value = true;
    console.log('Processing audio blob...');

    try {
        // Convert blob to base64
        const reader = new FileReader();
        reader.readAsDataURL(audioBlob);

        reader.onloadend = async () => {
            const base64Audio = reader.result as string;
            console.log('Base64 audio length:', base64Audio.length);

            try {
                // Send to backend for transcription
                console.log('Sending to backend for transcription...');
                const response = await axios.post(route('assistant.transcribe'), {
                    audio: base64Audio,
                });

                console.log('Transcription response:', response.data);

                if (response.data.success) {
                    emit('transcribed', response.data.text);
                } else {
                    throw new Error(response.data.error || 'Transcription failed');
                }
            } catch (error: any) {
                console.error('Transcription error:', error);
                if (error.response) {
                    console.error('Response data:', error.response.data);
                    console.error('Response status:', error.response.status);
                }
                emit('error', error.response?.data?.message || 'Failed to transcribe audio. Please try again.');
            } finally {
                isProcessing.value = false;
            }
        };

        reader.onerror = () => {
            console.error('FileReader error');
            emit('error', 'Failed to process audio file.');
            isProcessing.value = false;
        };
    } catch (error) {
        console.error('Error processing audio:', error);
        emit('error', 'Failed to transcribe audio. Please try again.');
        isProcessing.value = false;
    }
};

onUnmounted(() => {
    if (isRecording.value) {
        stopRecording();
    }
});
</script>

<style scoped>
.audio-recorder {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.record-button {
    width: 40px;
    height: 40px;
    border: none;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s;
    background: var(--bg-tertiary);
    color: var(--text-secondary);
}

.record-button:hover {
    background: var(--bg-hover);
    color: var(--text-primary);
}

.record-button.recording {
    background: #ef4444;
    color: white;
    animation: recording-pulse 1.5s ease-in-out infinite;
}

@keyframes recording-pulse {
    0% {
        box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.4);
    }
    70% {
        box-shadow: 0 0 0 10px rgba(239, 68, 68, 0);
    }
    100% {
        box-shadow: 0 0 0 0 rgba(239, 68, 68, 0);
    }
}

.record-button:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.recording-indicator {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.pulse {
    width: 8px;
    height: 8px;
    background: #ef4444;
    border-radius: 50%;
    animation: pulse 1s infinite;
}

@keyframes pulse {
    0% {
        opacity: 1;
        transform: scale(1);
    }
    50% {
        opacity: 0.5;
        transform: scale(1.1);
    }
    100% {
        opacity: 1;
        transform: scale(1);
    }
}

.time {
    font-size: 0.875rem;
    color: var(--text-secondary);
    font-variant-numeric: tabular-nums;
}

.processing {
    font-size: 0.875rem;
    color: var(--text-secondary);
}
</style>
