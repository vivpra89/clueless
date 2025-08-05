#!/bin/bash

# Simplified certificate export script
echo "Simple Certificate Export for GitHub Actions"
echo "==========================================="
echo ""
echo "This script will export your Developer ID Application certificate."
echo ""

# List certificates
echo "Available Developer ID certificates:"
echo "-----------------------------------"
security find-identity -v -p codesigning | grep "Developer ID Application"

echo ""
echo "Please follow these steps:"
echo ""
echo "1. Open Keychain Access"
echo "2. Find your 'Developer ID Application' certificate" 
echo "3. Right-click and select 'Export...'"
echo "4. Save as a .p12 file with a simple password (no special characters)"
echo "5. Note the exact password you used"
echo ""
read -p "Press Enter after you've exported the certificate..."

echo ""
read -p "Enter the path to your exported .p12 file: " P12_PATH

if [ ! -f "$P12_PATH" ]; then
    echo "❌ File not found: $P12_PATH"
    exit 1
fi

echo ""
echo "Converting to base64..."
CERT_BASE64=$(base64 < "$P12_PATH")

echo ""
echo "Certificate prepared!"
echo ""
echo "Now update these GitHub secrets:"
echo "================================"
echo ""
echo "1. Go to: https://github.com/vijaythecoder/clueless/settings/secrets/actions"
echo ""
echo "2. Update NATIVEPHP_CERTIFICATE_BASE64 with the base64 string"
echo "   (It has been copied to your clipboard)"
echo ""
echo "3. Update NATIVEPHP_CERTIFICATE_PASSWORD with your password"
echo "   ⚠️  Make sure to enter the exact password with no extra spaces"
echo ""

if command -v pbcopy &> /dev/null; then
    echo "$CERT_BASE64" | pbcopy
    echo "✅ Base64 certificate copied to clipboard!"
else
    echo "Base64 certificate:"
    echo "=================="
    echo "$CERT_BASE64"
    echo "=================="
fi

echo ""
echo "After updating both secrets, push any change to trigger a new build."