#!/bin/bash

echo "🚀 Preparing Clueless for production build..."

# Remove any development artifacts
echo "📧 Cleaning development artifacts..."
rm -f public/hot
rm -rf node_modules/.vite

# Install dependencies
echo "📦 Installing npm dependencies..."
npm install --omit=dev

# Build frontend assets
echo "🏗️ Building frontend assets..."
npm run build

# Verify build output
if [ -f "public/build/manifest.json" ]; then
    echo "✅ Frontend assets built successfully"
    echo "📁 Build manifest found at: public/build/manifest.json"
else
    echo "❌ Build failed - manifest.json not found"
    exit 1
fi

# Clear and optimize Laravel
echo "🔧 Optimizing Laravel..."
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

# Cache for production
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize

echo "✨ Build preparation complete!"