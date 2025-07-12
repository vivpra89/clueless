import { onMounted, ref, watch } from 'vue';
import { updateTheme } from './useAppearance';

declare global {
    interface Window {
        remote?: {
            getCurrentWindow: () => {
                setAlwaysOnTop: (flag: boolean, level?: string) => void;
                setIgnoreMouseEvents: (ignore: boolean, options?: { forward?: boolean }) => void;
                setOpacity: (opacity: number) => void;
                getOpacity: () => number;
                setTransparent?: (transparent: boolean) => void;
                setBackgroundColor?: (color: string) => void;
            };
        };
    }
}

export function useOverlayMode() {
    const isOverlayMode = ref(false);
    const isSupported = ref(false);
    let previousTheme: string | null = null;

    // Check if overlay mode is supported
    const checkSupport = () => {
        try {
            if (window.remote && typeof window.remote.getCurrentWindow === 'function') {
                const currentWindow = window.remote.getCurrentWindow();
                if (currentWindow && typeof currentWindow.setAlwaysOnTop === 'function' && typeof currentWindow.setIgnoreMouseEvents === 'function') {
                    isSupported.value = true;
                    return true;
                }
            }
        } catch {}

        isSupported.value = false;
        return false;
    };

    // Load saved preference
    const loadPreference = () => {
        const saved = localStorage.getItem('overlayModeEnabled');
        return saved === 'true';
    };

    // Save preference
    const savePreference = (enabled: boolean) => {
        localStorage.setItem('overlayModeEnabled', String(enabled));
    };

    // Toggle overlay mode
    const toggleOverlayMode = () => {
        if (!isSupported.value) {
            return false;
        }

        const newState = !isOverlayMode.value;

        try {
            const currentWindow = window.remote!.getCurrentWindow();

            if (newState) {
                // Enable overlay mode
                currentWindow.setAlwaysOnTop(true, 'floating');

                // Set transparent background for overlay mode
                if (typeof currentWindow.setBackgroundColor === 'function') {
                    currentWindow.setBackgroundColor('#00000000');
                }

                // Set window opacity for overlay mode visibility
                if (typeof currentWindow.setOpacity === 'function') {
                    currentWindow.setOpacity(0.8); // 80% opacity for more transparency
                }

                // Save current theme and force dark mode
                previousTheme = localStorage.getItem('appearance');
                updateTheme('dark');
            } else {
                // Disable overlay mode
                currentWindow.setAlwaysOnTop(false);

                // Restore opaque background
                if (typeof currentWindow.setBackgroundColor === 'function') {
                    // Set opaque background to match the app
                    currentWindow.setBackgroundColor('#f5f7fa');
                }

                // Restore full window opacity
                if (typeof currentWindow.setOpacity === 'function') {
                    currentWindow.setOpacity(1); // Full opacity for normal mode
                }

                // Restore previous theme
                if (previousTheme) {
                    updateTheme(previousTheme as 'light' | 'dark' | 'system');
                } else {
                    updateTheme('system');
                }
            }

            isOverlayMode.value = newState;
            savePreference(newState);

            // Emit event for other components
            window.dispatchEvent(
                new CustomEvent('overlayModeChanged', {
                    detail: { enabled: newState },
                }),
            );

            return true;
        } catch {
            return false;
        }
    };

    // Enable overlay mode
    const enableOverlayMode = () => {
        if (isOverlayMode.value) return true;
        return toggleOverlayMode();
    };

    // Disable overlay mode
    const disableOverlayMode = () => {
        if (!isOverlayMode.value) return true;
        return toggleOverlayMode();
    };

    // Set window opacity - deprecated in favor of CSS-based transparency
    const setWindowOpacity = () => {
        // We don't use window-level opacity anymore to allow different
        // opacity levels for different UI elements (title bar, cards, etc.)
        return false;
    };

    // Initialize
    onMounted(() => {
        setTimeout(() => {
            if (checkSupport()) {
                const savedPreference = loadPreference();
                if (savedPreference) {
                    enableOverlayMode();
                } else {
                    // Ensure normal mode has opaque background on startup
                    try {
                        const currentWindow = window.remote!.getCurrentWindow();
                        if (typeof currentWindow.setBackgroundColor === 'function') {
                            currentWindow.setBackgroundColor('#f5f7fa');
                        }
                    } catch {}
                }
            }
        }, 100);
    });

    // Watch for changes
    watch(isOverlayMode, () => {});

    return {
        isOverlayMode,
        isSupported,
        toggleOverlayMode,
        enableOverlayMode,
        disableOverlayMode,
        setWindowOpacity,
        checkSupport,
    };
}
