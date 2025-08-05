#!/usr/bin/env node

/**
 * Post-install script to handle platform-specific dependencies
 * This prevents macOS-specific modules from being installed on Windows
 */

import { platform } from 'os';
import { exec } from 'child_process';
import { promisify } from 'util';

const execAsync = promisify(exec);

async function installMacOSDependencies() {
    console.log('🍎 macOS detected - installing macOS-specific dependencies...');
    
    try {
        // Install node-mac-permissions only on macOS
        await execAsync('npm install --no-save node-mac-permissions@^2.5.0');
        console.log('✅ macOS dependencies installed successfully');
    } catch (error) {
        console.error('❌ Failed to install macOS dependencies:', error.message);
        // Don't fail the install process
    }
}

async function patchVendorPermissions() {
    // Check if we're in a NativePHP Electron environment
    try {
        const fs = await import('fs/promises');
        const path = await import('path');
        
        const vendorPermissionsPath = path.join(
            process.cwd(),
            'vendor/nativephp/electron/resources/js/src/main/permissions.js'
        );
        
        // Check if the file exists
        try {
            await fs.access(vendorPermissionsPath);
        } catch {
            // File doesn't exist, nothing to patch
            return;
        }
        
        // Read the file
        let content = await fs.readFile(vendorPermissionsPath, 'utf8');
        
        // Check if already patched
        if (content.includes('// Platform check for node-mac-permissions')) {
            console.log('✅ Vendor permissions file already patched');
            return;
        }
        
        // Create patched content
        const patchedContent = `import { ipcMain } from 'electron';

// Platform check for node-mac-permissions
let permissions = null;
if (process.platform === 'darwin') {
    try {
        permissions = (await import('node-mac-permissions')).default;
    } catch (error) {
        console.warn('node-mac-permissions not available:', error.message);
    }
}

// Stub for non-macOS platforms
if (!permissions) {
    permissions = {
        getAuthStatus: () => 'authorized',
        askForCameraAccess: async () => 'authorized',
        askForMicrophoneAccess: async () => 'authorized',
        askForScreenCaptureAccess: () => {},
        askForFoldersAccess: async () => 'authorized'
    };
}

${content.substring(content.indexOf('/**'))}`;
        
        // Write the patched file
        await fs.writeFile(vendorPermissionsPath, patchedContent);
        console.log('✅ Patched vendor permissions file for cross-platform compatibility');
        
    } catch (error) {
        console.warn('⚠️  Could not patch vendor permissions file:', error.message);
        // Don't fail the install process
    }
}

async function main() {
    console.log(`🔧 Running post-install script for ${platform()} platform...`);
    
    // Install macOS dependencies only on Darwin
    if (platform() === 'darwin') {
        await installMacOSDependencies();
    } else {
        console.log(`📦 Skipping macOS-specific dependencies on ${platform()}`);
    }
    
    // Patch vendor files for cross-platform compatibility
    await patchVendorPermissions();
    
    console.log('✨ Post-install script completed');
}

// Run the script
main().catch(error => {
    console.error('❌ Post-install script error:', error);
    // Don't exit with error code to not break npm install
    process.exit(0);
});