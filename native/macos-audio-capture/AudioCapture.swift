#!/usr/bin/env swift

import Foundation
import ScreenCaptureKit
import CoreAudio
import AVFoundation
import AppKit

// MARK: - Process Lock Manager
class ProcessLockManager {
    private static let lockFilePath = "/tmp/macos-audio-capture.lock"
    private static let pidFilePath = "/tmp/macos-audio-capture.pid"
    
    static func acquireLock() -> Bool {
        let pid = ProcessInfo.processInfo.processIdentifier
        
        // Check if another process is running
        if let existingPid = readPidFile() {
            // Check if the process is actually running
            if isProcessRunning(pid: existingPid) {
                return false // Another instance is running
            } else {
                // Clean up stale lock
                removeLock()
            }
        }
        
        // Write our PID
        do {
            try "\(pid)".write(toFile: pidFilePath, atomically: true, encoding: .utf8)
            return true
        } catch {
            return false
        }
    }
    
    static func releaseLock() {
        removeLock()
    }
    
    private static func readPidFile() -> Int32? {
        guard let pidString = try? String(contentsOfFile: pidFilePath),
              let pid = Int32(pidString.trimmingCharacters(in: .whitespacesAndNewlines)) else {
            return nil
        }
        return pid
    }
    
    private static func isProcessRunning(pid: Int32) -> Bool {
        return kill(pid, 0) == 0
    }
    
    private static func removeLock() {
        try? FileManager.default.removeItem(atPath: pidFilePath)
        try? FileManager.default.removeItem(atPath: lockFilePath)
    }
}

// MARK: - Audio Capture Manager
@available(macOS 13.0, *)
class AudioCaptureManager: NSObject {
    private var stream: SCStream?
    private var audioOutput: AudioOutput?
    private let sampleRate: Double = 24000
    private let channelCount: Int = 1
    private var isCapturing = false
    private var retryCount = 0
    private let maxRetries = 3
    private let retryDelay: TimeInterval = 2.0
    private var heartbeatTimer: Timer?
    private var lastHeartbeat = Date()
    
    // MARK: - Initialize
    override init() {
        super.init()
        self.audioOutput = AudioOutput(sampleRate: sampleRate)
    }
    
    deinit {
        stopHeartbeat()
        ProcessLockManager.releaseLock()
    }
    
    // MARK: - Start Capture
    func startCapture() async throws {
        isCapturing = true
        retryCount = 0
        
        // Try to start capture with retries
        while retryCount < maxRetries {
            do {
                try await attemptStartCapture()
                return // Success!
            } catch {
                retryCount += 1
                
                if retryCount >= maxRetries {
                    isCapturing = false
                    sendError("Failed to start audio capture after \(maxRetries) attempts: \(error.localizedDescription)")
                    throw error
                }
                
                // Send retry status
                sendStatus("retrying")
                sendError("Audio capture failed, retrying in \(Int(retryDelay)) seconds... (attempt \(retryCount)/\(maxRetries))")
                
                // Wait before retry
                try await Task.sleep(nanoseconds: UInt64(retryDelay * 1_000_000_000))
                
                // Force cleanup before retry
                await forceCleanup()
            }
        }
    }
    
    private func attemptStartCapture() async throws {
        // First check if we have screen recording permission
        // Request permission by trying to get shareable content
        // This will trigger the permission dialog if not already granted
        do {
            _ = try await SCShareableContent.current
        } catch {
            // If we get an error here, it's likely due to missing permissions
            sendError("Screen recording permission required. Please grant permission in System Preferences > Privacy & Security > Screen Recording")
            
            // Open System Preferences to the Screen Recording section
            if let url = URL(string: "x-apple.systempreferences:com.apple.preference.security?Privacy_ScreenCapture") {
                NSWorkspace.shared.open(url)
            }
            
            throw CaptureError.permissionDenied
        }
        
        // Get shareable content
        let content = try await SCShareableContent.current
        
        // Create stream configuration for audio only
        let config = SCStreamConfiguration()
        config.capturesAudio = true
        config.sampleRate = Int(sampleRate)
        config.channelCount = channelCount
        
        // Create content filter to capture all system audio
        // We'll use a display filter to capture everything
        guard let display = content.displays.first else {
            throw CaptureError.captureError("No display found")
        }
        
        let filter = SCContentFilter(display: display, excludingApplications: [], exceptingWindows: [])
        
        // Create stream
        stream = SCStream(filter: filter, configuration: config, delegate: nil)
        
        // Add audio output
        if let audioOutput = audioOutput {
            try stream?.addStreamOutput(audioOutput, type: .audio, sampleHandlerQueue: .main)
        }
        
        // Start capture
        do {
            try await stream?.startCapture()
            sendStatus("capturing")
            startHeartbeat()
        } catch {
            // Check if error is due to resource conflict
            let errorDescription = error.localizedDescription.lowercased()
            if errorDescription.contains("resource") || errorDescription.contains("busy") || errorDescription.contains("in use") {
                throw CaptureError.resourceBusy
            }
            throw error
        }
    }
    
    // MARK: - Heartbeat Management
    private func startHeartbeat() {
        stopHeartbeat()
        heartbeatTimer = Timer.scheduledTimer(withTimeInterval: 5.0, repeats: true) { _ in
            self.sendHeartbeat()
        }
    }
    
    private func stopHeartbeat() {
        heartbeatTimer?.invalidate()
        heartbeatTimer = nil
    }
    
    private func sendHeartbeat() {
        lastHeartbeat = Date()
        let heartbeat = [
            "type": "heartbeat",
            "timestamp": ISO8601DateFormatter().string(from: lastHeartbeat),
            "capturing": isCapturing
        ] as [String : Any]
        
        if let data = try? JSONSerialization.data(withJSONObject: heartbeat),
           let json = String(data: data, encoding: .utf8) {
            print(json)
            fflush(stdout)
        }
    }
    
    // MARK: - Stop Capture
    func stopCapture() async throws {
        isCapturing = false
        stopHeartbeat()
        try await stream?.stopCapture()
        stream = nil
        sendStatus("stopped")
    }
    
    // MARK: - Force Cleanup
    private func forceCleanup() async {
        // Force cleanup of existing stream
        if let existingStream = stream {
            do {
                try await existingStream.stopCapture()
            } catch {
                // Ignore errors during cleanup
            }
        }
        stream = nil
        
        // Small delay to ensure resources are released
        try? await Task.sleep(nanoseconds: 500_000_000) // 0.5 seconds
    }
    
    // MARK: - Restart Capture
    func restartCapture() async throws {
        sendStatus("restarting")
        
        // Stop existing capture
        if isCapturing {
            try await stopCapture()
        }
        
        // Wait a bit to ensure resources are released
        try await Task.sleep(nanoseconds: 1_000_000_000) // 1 second
        
        // Start capture again
        try await startCapture()
    }
    
    // MARK: - Send Status
    private func sendStatus(_ state: String) {
        let status = ["type": "status", "state": state]
        if let data = try? JSONSerialization.data(withJSONObject: status),
           let json = String(data: data, encoding: .utf8) {
            print(json)
            fflush(stdout)
        }
    }
    
    // MARK: - Send Error
    private func sendError(_ message: String, code: Int = 0) {
        let error: [String: Any] = [
            "type": "error",
            "message": message,
            "code": code
        ]
        if let data = try? JSONSerialization.data(withJSONObject: error),
           let json = String(data: data, encoding: .utf8) {
            print(json)
            fflush(stdout)
        }
    }
}

// MARK: - Audio Output Handler
@available(macOS 13.0, *)
class AudioOutput: NSObject, SCStreamOutput {
    private let sampleRate: Double
    private var audioConverter: AVAudioConverter?
    private let outputFormat: AVAudioFormat
    
    init(sampleRate: Double) {
        self.sampleRate = sampleRate
        
        // Create output format (PCM16, mono, 24kHz)
        self.outputFormat = AVAudioFormat(
            commonFormat: .pcmFormatInt16,
            sampleRate: sampleRate,
            channels: 1,
            interleaved: false
        )!
        
        super.init()
    }
    
    func stream(_ stream: SCStream, didOutputSampleBuffer sampleBuffer: CMSampleBuffer, of type: SCStreamOutputType) {
        guard type == .audio else { return }
        
        // Process audio buffer
        processAudioBuffer(sampleBuffer)
    }
    
    private func processAudioBuffer(_ sampleBuffer: CMSampleBuffer) {
        guard let blockBuffer = CMSampleBufferGetDataBuffer(sampleBuffer) else { return }
        
        var length = 0
        var dataPointer: UnsafeMutablePointer<Int8>?
        CMBlockBufferGetDataPointer(blockBuffer, atOffset: 0, lengthAtOffsetOut: nil, totalLengthOut: &length, dataPointerOut: &dataPointer)
        
        guard let data = dataPointer else { return }
        
        // Convert to PCM16 format
        let samples = length / MemoryLayout<Float32>.size
        let floatPointer = UnsafeRawPointer(data).bindMemory(to: Float32.self, capacity: samples)
        
        var pcm16Data = Data(capacity: samples * 2)
        
        for i in 0..<samples {
            let sample = floatPointer[i]
            let clamped = max(-1.0, min(1.0, sample))
            let int16Value = Int16(clamped * 32767)
            withUnsafeBytes(of: int16Value) { bytes in
                pcm16Data.append(contentsOf: bytes)
            }
        }
        
        // Send audio data as base64
        let base64Audio = pcm16Data.base64EncodedString()
        let audioPacket = ["type": "audio", "data": base64Audio]
        
        if let jsonData = try? JSONSerialization.data(withJSONObject: audioPacket),
           let json = String(data: jsonData, encoding: .utf8) {
            print(json)
            fflush(stdout)
        }
    }
}

// MARK: - Error Types
enum CaptureError: Error, LocalizedError {
    case permissionDenied
    case captureError(String)
    case processLocked
    case resourceBusy
    
    var errorDescription: String? {
        switch self {
        case .permissionDenied:
            return "Screen recording permission denied. Please grant permission in System Preferences."
        case .captureError(let message):
            return "Capture error: \(message)"
        case .processLocked:
            return "Another audio capture process is already running"
        case .resourceBusy:
            return "Audio capture resource is busy. Another app may be using it."
        }
    }
    
    var errorCode: Int {
        switch self {
        case .permissionDenied: return 1001
        case .captureError: return 1002
        case .processLocked: return 1003
        case .resourceBusy: return 1004
        }
    }
}

// MARK: - Command Handler
@available(macOS 13.0, *)
class CommandHandler {
    private let captureManager = AudioCaptureManager()
    
    func start() {
        // Set up input handler
        DispatchQueue.global().async {
            while let line = readLine() {
                self.handleCommand(line)
            }
        }
        
        // Keep the run loop alive
        RunLoop.main.run()
    }
    
    private func handleCommand(_ line: String) {
        guard let data = line.data(using: .utf8),
              let json = try? JSONSerialization.jsonObject(with: data) as? [String: Any],
              let command = json["command"] as? String else {
            sendError("Invalid command format")
            return
        }
        
        Task {
            do {
                switch command {
                case "check_permission":
                    await checkPermission()
                case "start":
                    try await captureManager.startCapture()
                case "stop":
                    try await captureManager.stopCapture()
                case "restart":
                    try await captureManager.restartCapture()
                default:
                    sendError("Unknown command: \(command)")
                }
            } catch let captureError as CaptureError {
                sendError(captureError.localizedDescription, code: captureError.errorCode)
            } catch {
                sendError(error.localizedDescription)
            }
        }
    }
    
    private func checkPermission() async {
        do {
            // Try to get shareable content to check permission
            _ = try await SCShareableContent.current
            // If successful, we have permission
            let status: [String: Any] = ["type": "permission", "granted": true]
            if let data = try? JSONSerialization.data(withJSONObject: status),
               let json = String(data: data, encoding: .utf8) {
                print(json)
                fflush(stdout)
            }
        } catch {
            // No permission
            let status: [String: Any] = ["type": "permission", "granted": false]
            if let data = try? JSONSerialization.data(withJSONObject: status),
               let json = String(data: data, encoding: .utf8) {
                print(json)
                fflush(stdout)
            }
        }
    }
    
    private func sendError(_ message: String, code: Int = 0) {
        let error: [String: Any] = [
            "type": "error",
            "message": message,
            "code": code
        ]
        if let data = try? JSONSerialization.data(withJSONObject: error),
           let json = String(data: data, encoding: .utf8) {
            print(json)
            fflush(stdout)
        }
    }
}

// MARK: - Main
if #available(macOS 13.0, *) {
    // Check for process lock first
    if !ProcessLockManager.acquireLock() {
        let error: [String: Any] = [
            "type": "error",
            "message": "Another audio capture process is already running",
            "code": 1003
        ]
        if let data = try? JSONSerialization.data(withJSONObject: error),
           let json = String(data: data, encoding: .utf8) {
            print(json)
        }
        exit(1)
    }
    
    // Set up signal handlers for clean shutdown
    signal(SIGINT) { _ in
        ProcessLockManager.releaseLock()
        exit(0)
    }
    signal(SIGTERM) { _ in
        ProcessLockManager.releaseLock()
        exit(0)
    }
    
    let handler = CommandHandler()
    handler.start()
} else {
    let error: [String: Any] = [
        "type": "error",
        "message": "macOS 13.0 or later required",
        "code": 1000
    ]
    if let data = try? JSONSerialization.data(withJSONObject: error),
       let json = String(data: data, encoding: .utf8) {
        print(json)
    }
    exit(1)
}