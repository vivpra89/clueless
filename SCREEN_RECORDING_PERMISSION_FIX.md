# Screen Recording Permission Loop Fix

## Problem
The app repeatedly asks for screen recording permission even when it's already granted in System Preferences.

## Root Causes

### 1. Missing NSScreenCaptureUsageDescription ✅ FIXED
- **Issue**: The Info.plist was missing the required usage description for screen capture
- **Fix**: Added to electron-builder.js:
  ```javascript
  NSScreenCaptureUsageDescription: "Application requests access to screen recording to capture system audio."
  ```

### 2. TCC Database Issues
The macOS TCC (Transparency, Consent, and Control) database might not be properly tracking your app due to:

#### A. Code Signing Issues
- Development builds are often ad-hoc signed or unsigned
- Each build might appear as a different app to macOS

#### B. Bundle Identifier Changes
- If the bundle ID changes between builds, permissions are reset
- Check that `appId` in electron-builder.js remains consistent

#### C. Hardened Runtime
- Screen recording requires hardened runtime with proper entitlements

## Solutions

### 1. Rebuild the App
After adding NSScreenCaptureUsageDescription:
```bash
composer native:build
```

### 2. Reset TCC Database (for testing)
```bash
# Reset screen recording permissions for your app
tccutil reset ScreenCapture com.clueless.app

# Or reset all permissions
tccutil reset All com.clueless.app
```

### 3. For Development Builds
Add hardened runtime and consistent signing:

1. Edit electron-builder.js and add:
```javascript
mac: {
    hardenedRuntime: true,
    gatekeeperAssess: false,
    entitlementsInherit: 'build/entitlements.mac.plist',
    // ... rest of config
}
```

2. For development, you can disable code signing checks:
```bash
# Before running the app
export CSC_IDENTITY_AUTO_DISCOVERY=false
```

### 4. Verify Entitlements
The app needs these entitlements in `build/entitlements.mac.plist`:
- ✅ `com.apple.security.cs.allow-jit`
- ✅ `com.apple.security.cs.allow-unsigned-executable-memory`
- ✅ `com.apple.security.cs.allow-dyld-environment-variables`
- ✅ `com.apple.security.device.audio-input`

### 5. Check Bundle ID Consistency
Ensure the bundle ID remains the same across builds:
- Check `appId` in electron-builder.js
- Should be: `com.clueless.app`

## Testing the Fix

1. **Clean Build**:
   ```bash
   rm -rf dist/
   composer native:build
   ```

2. **Install Fresh**:
   - Remove old app from Applications
   - Install new build
   - Grant permissions when prompted

3. **Verify Permissions**:
   - System Preferences → Security & Privacy → Screen Recording
   - Ensure Clueless is listed and checked

4. **Test Audio Capture**:
   - Start a call
   - Should not prompt again if permissions are already granted

## Additional Debugging

If the issue persists:

1. **Check Console Logs**:
   ```bash
   # Watch for TCC errors
   log stream --predicate 'subsystem == "com.apple.TCC"' | grep -i clueless
   ```

2. **Verify App Signature**:
   ```bash
   codesign -dv --verbose=4 /Applications/Clueless.app
   ```

3. **Check Entitlements**:
   ```bash
   codesign -d --entitlements :- /Applications/Clueless.app
   ```

## Preventing Future Issues

1. **Use Consistent Signing**:
   - For development: Use a development certificate
   - For production: Use proper distribution certificate

2. **Maintain Bundle ID**:
   - Never change `appId` between builds
   - Use same ID for development and production

3. **Include All Usage Descriptions**:
   - NSMicrophoneUsageDescription ✅
   - NSScreenCaptureUsageDescription ✅
   - NSCameraUsageDescription ✅

4. **Test Permission Flow**:
   - Always test on a clean macOS user account
   - Or reset TCC database before testing

## Code Changes Made

1. **electron-builder.js**: Added NSScreenCaptureUsageDescription
2. **entitlements.mac.plist**: Already has required entitlements
3. **system.ts**: Already has permission check APIs

The main fix was adding the missing NSScreenCaptureUsageDescription. This should resolve the permission loop issue after rebuilding the app.