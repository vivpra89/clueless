# Release Workflow Documentation

## Overview
This GitHub Actions workflow automatically builds and releases macOS versions of Clueless when changes are pushed to the main branch.

## Workflow Triggers
- **Automatic**: Pushes to the `main` branch
- **Ignored**: Changes to markdown files or other GitHub workflows
- **Manual**: Can be triggered from Actions tab if needed

## Build Process

### 1. Environment Setup
- macOS latest runner
- PHP 8.2 with required extensions
- Node.js 22 with npm caching
- Composer dependency caching

### 2. Build Steps
1. Install all dependencies (Composer & npm)
2. Configure Laravel environment
3. Build frontend assets with Vite
4. Generate Ziggy routes for JavaScript
5. Build Swift audio capture executable
6. Build Electron app with NativePHP

### 3. Architecture Support
- **x64**: Intel-based Macs
- **arm64**: Apple Silicon Macs (M1/M2/M3)

## Version Management
Version is read from `config/nativephp.php`:
```php
'version' => env('NATIVEPHP_APP_VERSION', '1.0.0'),
```

To release a new version:
1. Update version in `config/nativephp.php`
2. Commit and push to main
3. Workflow will create a release with that version

## Code Signing & Notarization

### Current Status
- ⚠️ **Not Active**: Requires Apple Developer account
- Workflow is prepared but will skip signing until credentials are added

### Required GitHub Secrets (When Ready)
Add these secrets to your repository settings:

1. **NATIVEPHP_APPLE_ID**: Your Apple ID email
2. **NATIVEPHP_APPLE_ID_PASS**: App-specific password from appleid.apple.com
3. **NATIVEPHP_APPLE_TEAM_ID**: Your Apple Developer Team ID
4. **NATIVEPHP_CERTIFICATE_BASE64**: Base64 encoded Developer ID certificate
5. **NATIVEPHP_CERTIFICATE_PASSWORD**: Certificate password

### How to Export Certificate
```bash
# Export certificate from Keychain
security export -t identities -f pkcs12 -o cert.p12

# Convert to base64
base64 -i cert.p12 -o cert.txt

# Copy contents of cert.txt to NATIVEPHP_CERTIFICATE_BASE64 secret
```

## Release Creation

### Automatic Process
1. Builds complete for both architectures
2. Draft release created with version tag
3. DMG files uploaded as release assets
4. Release notes generated

### Manual Steps
1. Go to [Releases](https://github.com/vijaythecoder/clueless/releases)
2. Find the draft release
3. Edit release notes if needed
4. Click "Publish release"

## Artifacts

### Build Artifacts
- `Clueless-{version}-x64.dmg`: Intel Mac installer
- `Clueless-{version}-arm64.dmg`: Apple Silicon installer
- Artifacts retained for 5 days

### Installation
Users should:
1. Download appropriate DMG for their Mac
2. Open DMG file
3. Drag Clueless to Applications folder
4. Launch from Applications

## Troubleshooting

### Build Failures
1. Check Actions tab for error logs
2. Common issues:
   - Missing dependencies
   - Version conflicts
   - Build script permissions

### Local Testing
Test the build process locally:
```bash
# Intel Mac
php artisan native:build mac --x64

# Apple Silicon Mac
php artisan native:build mac --arm64
```

### Workflow Modifications
- Edit `.github/workflows/release.yml`
- Test changes in a feature branch first
- Workflow runs are visible in Actions tab

## Future Enhancements
- [ ] Windows build support
- [ ] Linux build support
- [ ] Automatic version bumping
- [ ] Release changelog generation
- [ ] Update notifications in app