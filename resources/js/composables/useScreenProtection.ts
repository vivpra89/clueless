import { onMounted, onUnmounted, ref } from 'vue';

// Type definitions for Electron APIs
interface ElectronAPI {
    screenProtection?: {
        setContentProtection: (enabled: boolean) => boolean;
        isContentProtectionSupported: () => boolean;
    };
}

interface Remote {
    getCurrentWindow: () => {
        setContentProtection: (enable: boolean) => void;
    };
}

declare global {
    interface Window {
        electronAPI?: ElectronAPI;
        remote?: Remote;
        electron?: any;
    }
}

export function useScreenProtection() {
    const isProtectionEnabled = ref(false);
    const isProtectionSupported = ref(false);
    const protectionStatus = ref<'active' | 'inactive' | 'unsupported'>('inactive');

    // Load saved preference
    const loadPreference = () => {
        const saved = localStorage.getItem('screenProtectionEnabled');
        return saved === 'true';
    };

    // Save preference
    const savePreference = (enabled: boolean) => {
        localStorage.setItem('screenProtectionEnabled', String(enabled));
    };

    // Check if protection is supported
    const checkSupport = () => {
        // Try different methods to access Electron APIs
        try {
            console.log('🔍 Checking screen protection support...');
            console.log('window.remote:', window.remote);
            console.log('window.electronAPI:', window.electronAPI);
            console.log('window.electron:', window.electron);
            console.log('process.versions:', (window as any).process?.versions);
            
            // Method 1: Check if window.remote is available (NativePHP exposes this)
            if (window.remote && typeof window.remote.getCurrentWindow === 'function') {
                const currentWindow = window.remote.getCurrentWindow();
                if (currentWindow && typeof currentWindow.setContentProtection === 'function') {
                    console.log('✅ Screen protection supported via window.remote');
                    isProtectionSupported.value = true;
                    return true;
                }
            }

            // Method 2: Check custom electronAPI (for custom preload)
            if (window.electronAPI?.screenProtection?.isContentProtectionSupported) {
                isProtectionSupported.value = window.electronAPI.screenProtection.isContentProtectionSupported();
                console.log('✅ Screen protection supported via electronAPI:', isProtectionSupported.value);
                return isProtectionSupported.value;
            }

            // Method 3: Check if we're in Electron environment
            if (window.electron || (window as any).process?.versions?.electron) {
                console.log('⚠️ Electron detected but no screen protection API found');
            }
        } catch (error) {
            console.error('Error checking screen protection support:', error);
        }

        console.log('❌ Screen protection not supported');
        isProtectionSupported.value = false;
        protectionStatus.value = 'unsupported';
        return false;
    };

    // Toggle screen protection
    const toggleProtection = () => {
        if (!isProtectionSupported.value) {
            return false;
        }

        const newState = !isProtectionEnabled.value;
        let success = false;

        try {
            // Method 1: Try window.remote first (NativePHP)
            if (window.remote && typeof window.remote.getCurrentWindow === 'function') {
                const currentWindow = window.remote.getCurrentWindow();
                console.log('🔐 Attempting to set content protection:', newState);
                console.log('Current window:', currentWindow);
                console.log('setContentProtection available:', typeof currentWindow.setContentProtection);
                
                if (currentWindow && typeof currentWindow.setContentProtection === 'function') {
                    try {
                        const result = currentWindow.setContentProtection(newState);
                        console.log('✅ setContentProtection called with:', newState, 'Result:', result);
                        
                        // Additional protection methods for macOS
                        if (newState && (window as any).process?.platform === 'darwin') {
                            console.log('🍎 Applying additional macOS protection...');
                            // Try to set additional privacy settings
                            try {
                                // Set window level to be excluded from screen capture
                                if (typeof currentWindow.setVisibleOnAllWorkspaces === 'function') {
                                    currentWindow.setVisibleOnAllWorkspaces(true, { visibleOnFullScreen: true });
                                    currentWindow.setVisibleOnAllWorkspaces(false);
                                    console.log('✅ setVisibleOnAllWorkspaces applied');
                                }

                                // Set window to be excluded from mission control
                                if (typeof currentWindow.setExcludedFromShownWindowsMenu === 'function') {
                                    currentWindow.setExcludedFromShownWindowsMenu(true);
                                    console.log('✅ setExcludedFromShownWindowsMenu applied');
                                }
                                
                                // Try sharingType method (newer Electron versions)
                                if (typeof currentWindow.setSharingType === 'function') {
                                    currentWindow.setSharingType('none');
                                    console.log('✅ setSharingType(none) applied');
                                }
                            } catch (e) {
                                console.error('Error applying additional macOS protection:', e);
                            }
                        }
                        
                        success = true;
                    } catch (error) {
                        console.error('❌ Error calling setContentProtection:', error);
                    }
                } else {
                    console.log('❌ setContentProtection method not found on window');
                }
            }

            // Method 2: Try custom electronAPI
            else if (window.electronAPI?.screenProtection?.setContentProtection) {
                success = window.electronAPI.screenProtection.setContentProtection(newState);
            }

            if (success) {
                isProtectionEnabled.value = newState;
                protectionStatus.value = newState ? 'active' : 'inactive';
                savePreference(newState);

                // Emit custom event for other components to listen
                window.dispatchEvent(
                    new CustomEvent('screenProtectionChanged', {
                        detail: { enabled: newState },
                    }),
                );

                return true;
            }
        } catch {}

        return false;
    };

    // Enable protection
    const enableProtection = () => {
        if (isProtectionEnabled.value) return true;
        return toggleProtection();
    };

    // Disable protection
    const disableProtection = () => {
        if (!isProtectionEnabled.value) return true;
        return toggleProtection();
    };

    // Auto-enable during calls
    const enableForCall = () => {
        if (!isProtectionEnabled.value) {
            enableProtection();
            return true;
        }
        return false;
    };

    // Check if protection is actually active
    const verifyProtection = () => {
        if (!isProtectionSupported.value) return false;
        
        try {
            if (window.remote && typeof window.remote.getCurrentWindow === 'function') {
                const currentWindow = window.remote.getCurrentWindow();
                
                // Try to check if content protection is active
                if (typeof currentWindow.isContentProtectionEnabled === 'function') {
                    const isEnabled = currentWindow.isContentProtectionEnabled();
                    console.log('🔍 Content protection verification:', isEnabled);
                    return isEnabled;
                }
                
                // For newer Electron versions, check sharingType
                if (typeof currentWindow.getSharingType === 'function') {
                    const sharingType = currentWindow.getSharingType();
                    console.log('🔍 Window sharing type:', sharingType);
                    return sharingType === 'none';
                }
            }
        } catch (error) {
            console.error('Error verifying protection:', error);
        }
        
        return isProtectionEnabled.value;
    };

    // Initialize on mount
    onMounted(() => {
        // Add a small delay to ensure Electron APIs are loaded
        setTimeout(() => {
            if (checkSupport()) {
                // Load saved preference
                const savedPreference = loadPreference();
                if (savedPreference) {
                    enableProtection();
                    // Verify after enabling
                    setTimeout(() => {
                        verifyProtection();
                    }, 500);
                }
            }
        }, 100);

        // Listen for protection change events from other sources
        const handleProtectionChange = (event: Event) => {
            const customEvent = event as CustomEvent;
            if (customEvent.detail?.enabled !== undefined) {
                isProtectionEnabled.value = customEvent.detail.enabled;
                protectionStatus.value = customEvent.detail.enabled ? 'active' : 'inactive';
            }
        };

        window.addEventListener('screenProtectionChanged', handleProtectionChange);

        // Cleanup
        onUnmounted(() => {
            window.removeEventListener('screenProtectionChanged', handleProtectionChange);
        });
    });

    return {
        isProtectionEnabled,
        isProtectionSupported,
        protectionStatus,
        toggleProtection,
        enableProtection,
        disableProtection,
        enableForCall,
        checkSupport,
        verifyProtection,
    };
}
