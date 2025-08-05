# NativePHP Build Fix Guide

## Problem Summary
When building the NativePHP/Electron app, frontend assets weren't being properly included, causing "Vite not found" errors on fresh installations.

## Root Causes Identified

1. **Hot Reload File**: The `public/hot` file was being included in builds, causing Laravel to look for Vite dev server
2. **Missing npm install**: Build process wasn't installing npm dependencies before building
3. **Build artifacts**: Development artifacts were interfering with production builds

## Fixes Applied

### 1. Updated `config/nativephp.php`

- Added `public/hot` to `cleanup_exclude_files` to prevent it from being included in builds
- Added `npm install --omit=dev` to prebuild commands before `npm run build`

### 2. Created Build Preparation Script

Created `build-prepare.sh` to ensure clean builds:
- Removes development artifacts (hot file, .vite cache)
- Installs production npm dependencies
- Builds frontend assets
- Verifies manifest.json exists
- Optimizes Laravel for production

## Build Process

### For Development
```bash
# Start development environment
composer dev

# Or for NativePHP development
composer native:dev
```

### For Production Build

1. **Prepare the build** (run this before building):
   ```bash
   ./build-prepare.sh
   ```

2. **Build the application**:
   ```bash
   php artisan native:build mac arm64
   ```

3. **Test the build** on a fresh machine:
   - Copy the built app from `dist/` directory
   - Install and run - it should work without any Vite errors

## Important Notes

1. **Always run build-prepare.sh** before building for production
2. **Never commit the `public/hot` file** to version control
3. **Ensure `public/build/manifest.json` exists** after building
4. The prebuild commands in `config/nativephp.php` will automatically:
   - Install npm dependencies
   - Build frontend assets
   - Optimize Laravel

## Troubleshooting

If you still see "Vite not found" errors:

1. Check if `public/hot` file exists in the built app - it shouldn't
2. Verify `public/build/manifest.json` exists in the built app
3. Ensure the app is running in production mode (`APP_ENV=production`)
4. Check Laravel logs for any asset-related errors

## Testing on Fresh Machine

When testing on a new machine:
1. The app should work immediately after installation
2. No need to run `npm install` or `npm run dev`
3. All assets should be pre-built and included