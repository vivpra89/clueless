// Electron-specific audio capture utilities
export function useElectronAudio() {
    // Check if we're in Electron environment
    const isElectron = () => {
        // @ts-expect-error - Electron APIs not available in type definitions
        return window.electron !== undefined || window.require !== undefined;
    };

    // Get system audio using Electron APIs
    const getSystemAudioStream = async (): Promise<MediaStream | null> => {
        if (!isElectron()) {
            return null;
        }

        try {
            let sources = null;

            // Try using the exposed electronAPI first
            // @ts-expect-error - ElectronAPI is injected at runtime by preload script
            if (window.electronAPI?.desktopCapturer) {
                // @ts-expect-error - ElectronAPI is injected at runtime by preload script
                sources = await window.electronAPI.desktopCapturer.getSources({
                    types: ['window', 'screen'],
                    fetchWindowIcons: false,
                });
                // Fallback to remote module
                // @ts-expect-error - Remote module is injected at runtime by Electron
            } else if (window.remote?.desktopCapturer) {
                // @ts-expect-error - Remote module is injected at runtime by Electron
                sources = await window.remote.desktopCapturer.getSources({
                    types: ['window', 'screen'],
                    fetchWindowIcons: false,
                });
            }

            if (sources) {
                // Try to find the main screen source first
                let audioSource = sources.find(
                    (source: any) =>
                        source.id === 'screen:0:0' ||
                        source.name.toLowerCase().includes('entire screen') ||
                        source.name.toLowerCase().includes('screen 0'),
                );

                // Fallback to any screen source
                if (!audioSource) {
                    audioSource = sources.find((source: any) => source.id.includes('screen'));
                }

                if (audioSource) {
                    // For system audio, we need special constraints
                    const constraints = {
                        audio: {
                            mandatory: {
                                chromeMediaSource: 'desktop',
                                chromeMediaSourceId: audioSource.id,
                            },
                        },
                        video: {
                            mandatory: {
                                chromeMediaSource: 'desktop',
                                chromeMediaSourceId: audioSource.id,
                                maxWidth: 1,
                                maxHeight: 1,
                            },
                        },
                    };

                    const stream = await navigator.mediaDevices.getUserMedia(constraints);

                    // Remove video tracks as we only need audio
                    stream.getVideoTracks().forEach((track) => {
                        stream.removeTrack(track);
                        track.stop();
                    });

                    const audioTracks = stream.getAudioTracks();

                    if (audioTracks.length > 0) {
                        return stream;
                    } else {
                        return null;
                    }
                } else {
                }
            } else {
            }
        } catch {
            // Try alternative approach using display media
            try {
                // @ts-expect-error - getDisplayMedia audio option not in standard types
                const stream = await navigator.mediaDevices.getDisplayMedia({
                    audio: true,
                    video: false,
                });
                return stream;
            } catch {}
        }

        return null;
    };

    return {
        isElectron,
        getSystemAudioStream,
    };
}
