#!/bin/bash

# Build script for macOS Audio Capture tool

echo "Building macOS Audio Capture tool..."

# Set build directory
BUILD_DIR="."
mkdir -p "$BUILD_DIR"

# Compile Swift executable
swiftc AudioCapture.swift \
    -o "$BUILD_DIR/macos-audio-capture" \
    -framework ScreenCaptureKit \
    -framework CoreAudio \
    -framework AVFoundation \
    -O \
    -target arm64-apple-macos13.0

# Check if build succeeded
if [ $? -eq 0 ]; then
    echo "✅ Build successful!"
    echo "Executable location: $BUILD_DIR/macos-audio-capture"

    # Make executable
    chmod +x "$BUILD_DIR/macos-audio-capture"
else
    echo "❌ Build failed!"
    exit 1
fi
