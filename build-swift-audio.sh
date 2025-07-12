#!/bin/bash

# Build script for macOS audio capture Swift executable

echo "Building macOS audio capture executable..."

# Create build directory if it doesn't exist
mkdir -p build/native

# Compile Swift code
swiftc native/macos-audio-capture/AudioCapture.swift \
    -o build/native/macos-audio-capture \
    -framework ScreenCaptureKit \
    -framework CoreAudio \
    -framework AVFoundation \
    -O \
    -target arm64-apple-macos13.0 \
    -swift-version 5

# Check if build succeeded
if [ $? -eq 0 ]; then
    echo "✅ Swift audio capture built successfully"
    echo "Executable location: build/native/macos-audio-capture"
    # Make sure it's executable
    chmod +x build/native/macos-audio-capture
else
    echo "❌ Failed to build Swift audio capture"
    exit 1
fi