# Realtime Architecture: Dual Model Implementation

This document explains the dual model architecture used in Clueless for optimal cost and performance in real-time conversations.

## Overview

Clueless uses **two specialized OpenAI models** within a single Realtime API session:

1. **`gpt-4o-mini-realtime-preview-2024-12-17`** - Main conversation AI agent
2. **`gpt-4o-mini-transcribe`** - Specialized speech-to-text conversion

## Architecture Flow

```
User Audio Input
    ↓ (WebSocket Connection)
OpenAI Realtime API Session
    ↓ (Audio Processing)
gpt-4o-mini-transcribe (Speech → Text)
    ↓ (Text Analysis)
gpt-4o-mini-realtime-preview (Conversation Logic)
    ↓ (Response Generation)
AI Response Output
```

## Model Responsibilities

### 1. `gpt-4o-mini-realtime-preview-2024-12-17` (WebSocket Connection)

**Purpose**: Main conversation AI agent

**What it does**:
- Responds to user conversations
- Provides coaching suggestions  
- Analyzes conversation context
- Generates real-time responses
- Handles function calls and tools

**Where it's used**: WebSocket URL for establishing the session
```javascript
const wsUrl = `wss://api.openai.com/v1/realtime?model=gpt-4o-mini-realtime-preview-2024-12-17`;
```

### 2. `gpt-4o-mini-transcribe` (Input Audio Transcription)

**Purpose**: Speech-to-text conversion only

**What it does**:
- Converts audio streams to text transcripts
- Optimized for better transcription accuracy
- More cost-effective for speech recognition
- Handles multiple languages and accents

**Where it's used**: Session configuration for audio transcription
```javascript
const sessionConfig = {
    type: 'session.update',
    session: {
        input_audio_transcription: {
            model: 'gpt-4o-mini-transcribe',
            language: 'en',
        },
    },
};
```

## Real-World Example

```
User speaks: "Hey, can you help me with pricing?"
    ↓
gpt-4o-mini-transcribe: Converts audio → "Hey, can you help me with pricing?"
    ↓ 
gpt-4o-mini-realtime-preview: Analyzes text → "I'd suggest starting with value-based questions..."
```

## Implementation Details

### Single Realtime API Session, Two Models

**One WebSocket Connection** → **Two Model Functions**:

1. **Audio Processing**: `gpt-4o-mini-transcribe` converts speech to text
2. **Conversation Logic**: `gpt-4o-mini-realtime-preview-2024-12-17` handles responses

### Code Implementation

```javascript
// 1. WebSocket connection uses the main realtime model
const wsUrl = `wss://api.openai.com/v1/realtime?model=gpt-4o-mini-realtime-preview-2024-12-17`;

// 2. Session configuration specifies transcription model
const sessionConfig = {
    type: 'session.update',
    session: {
        modalities: ['text'],
        input_audio_transcription: {
            model: 'gpt-4o-mini-transcribe',  // ← Transcription model
            language: 'en',
        },
        // Other session settings...
    },
};
```

## Benefits of This Hybrid Approach

### 1. **Cost Optimization**
- Transcription model is significantly cheaper for audio processing
- Main model handles complex conversation logic efficiently

### 2. **Specialized Performance** 
- Each model is optimized for its specific task
- Better transcription accuracy with dedicated speech model
- Better conversation flow with dedicated chat model

### 3. **Scalability**
- Transcription load separated from conversation processing
- Can scale each component independently

### 4. **Flexibility**
- Can switch transcription models without affecting conversation logic
- Can upgrade conversation model without changing transcription setup

## Current Use Cases in Clueless

### Salesperson Transcription
- **Purpose**: Convert microphone audio to text
- **Model**: `gpt-4o-mini-transcribe` for cost-effective transcription
- **Output**: Real-time transcript of salesperson's speech

### Customer Coach Analysis  
- **Purpose**: Analyze conversation and provide coaching
- **Models**: Both models working together
  - `gpt-4o-mini-transcribe`: Convert system audio to text
  - `gpt-4o-mini-realtime-preview`: Analyze and provide coaching suggestions

## Cost Comparison

| Model | Use Case | Cost per Minute* | Benefit |
|-------|----------|------------------|---------|
| `gpt-4o-mini-transcribe` | Transcription | ~$0.003 | Specialized, accurate |
| `gpt-4o-mini-realtime-preview` | Conversation | ~$0.01 | Full realtime capabilities |
| `gpt-4o-realtime-preview` (old) | Both | ~$0.24 | Expensive, not specialized |

*Approximate costs based on token usage

## Technical Files

### Frontend Implementation
- **File**: `/resources/js/pages/RealtimeAgent/Main.vue`
- **Lines**: 1141 (WebSocket URL), 1167 (Transcription config)

### Backend Implementation  
- **File**: `/app/Http/Controllers/RealtimeController.php`
- **Purpose**: Generate ephemeral keys for secure frontend authentication

### Configuration Flow
```
Frontend → Backend (ephemeral key) → Frontend → OpenAI (direct WebSocket)
```

## Key Insights

1. **Both models use the Realtime API** - They work within the same WebSocket session
2. **Different roles** - One transcribes, one converses  
3. **Cost-effective** - Specialized models provide better pricing
4. **Performance** - Direct WebSocket connections with optimized models
5. **Recommended pattern** - This is OpenAI's suggested approach for production apps

---

*This architecture provides the optimal balance of cost, performance, and functionality for real-time conversational applications.*