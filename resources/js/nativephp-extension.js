// NativePHP Extension Skeleton for Testing
// This is a minimal extension file to test the extensibility system

import { systemPreferences } from 'electron';

// Dynamic import for electron-audio-loopback (may not be installed)
let initAudioLoopback = null;

export default {
  // Hook into Electron lifecycle - called before app is ready
  beforeReady: async (app) => {
    console.log('[Extension Test] beforeReady hook called');
    
    // Try to load and initialize electron-audio-loopback
    try {
      const audioLoopbackModule = await import('electron-audio-loopback');
      initAudioLoopback = audioLoopbackModule.initMain;
      
      if (initAudioLoopback) {
        initAudioLoopback();
        console.log('[Extension Test] electron-audio-loopback initialized successfully');
      }
    } catch (error) {
      console.log('[Extension Test] electron-audio-loopback not available:', error.message);
      console.log('[Extension Test] Audio loopback features will not be available');
    }
  },
  
  // Hook into Electron lifecycle - called after app is ready
  afterReady: async (app, mainWindow) => {
    console.log('[Extension Test] afterReady hook called', {
      hasApp: !!app,
      hasMainWindow: !!mainWindow
    });
    // Add any setup that needs to happen after app is ready
  },
  
  // Hook into Electron lifecycle - called before app quits
  beforeQuit: async () => {
    console.log('[Extension Test] beforeQuit hook called');
    // Add any cleanup that needs to happen before app quits
  },
  
  // Custom IPC handlers for renderer communication
  ipcHandlers: {
    'test:ping': async (event, ...args) => {
      console.log('[Extension Test] IPC handler test:ping called', { args });
      return { 
        success: true, 
        message: 'Pong! Extension IPC handlers are working!',
        timestamp: new Date().toISOString(),
        receivedArgs: args
      };
    },
    
    'test:echo': async (event, message) => {
      console.log('[Extension Test] IPC handler test:echo called', { message });
      return { 
        success: true, 
        echo: message,
        processedAt: new Date().toISOString()
      };
    },
    
    'test:get-info': async (event) => {
      console.log('[Extension Test] IPC handler test:get-info called');
      return {
        success: true,
        info: {
          extensionVersion: '1.0.0',
          nodeVersion: process.version,
          platform: process.platform,
          arch: process.arch
        }
      };
    }
  },
  
  // Custom API endpoints accessible from Laravel
  apiRoutes: (router) => {
    // Test endpoint - GET
    router.get('/api/test/status', (req, res) => {
      console.log('[Extension Test] API route GET /api/test/status called');
      res.json({ 
        status: 'ok',
        message: 'Extension API routes are working!',
        timestamp: new Date().toISOString()
      });
    });
    
    // Test endpoint - POST
    router.post('/api/test/echo', (req, res) => {
      const { message } = req.body;
      console.log('[Extension Test] API route POST /api/test/echo called', { message });
      res.json({ 
        success: true,
        echo: message,
        processedAt: new Date().toISOString()
      });
    });
    
    // Test endpoint - GET with params
    router.get('/api/test/info/:param', (req, res) => {
      const { param } = req.params;
      console.log('[Extension Test] API route GET /api/test/info/:param called', { param });
      res.json({
        success: true,
        receivedParam: param,
        query: req.query,
        headers: req.headers
      });
    });
    
    // Media access status endpoint
    router.get('/api/system/media-access-status/:mediaType', (req, res) => {
      const { mediaType } = req.params;
      console.log('[Extension Test] API route GET /api/system/media-access-status/:mediaType called', { mediaType });
      
      if (process.platform === 'darwin') {
        const status = systemPreferences.getMediaAccessStatus(mediaType);
        res.json({ status });
      } else {
        res.json({ status: 'granted' }); // Non-macOS platforms don't have this API
      }
    });
    
    // Ask for media access endpoint
    router.post('/api/system/ask-for-media-access', async (req, res) => {
      const { mediaType } = req.body;
      console.log('[Extension Test] API route POST /api/system/ask-for-media-access called', { mediaType });
      
      if (process.platform === 'darwin') {
        try {
          const granted = await systemPreferences.askForMediaAccess(mediaType);
          res.json({ granted });
        } catch (e) {
          res.status(400).json({
            error: e.message,
          });
        }
      } else {
        res.json({ granted: true }); // Non-macOS platforms don't need this
      }
    });
  }
};