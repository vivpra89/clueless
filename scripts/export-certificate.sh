#!/bin/bash

# Script to export Apple Developer certificate for GitHub Actions
# Usage: ./scripts/export-certificate.sh

echo "Apple Developer Certificate Export Helper"
echo "========================================"
echo ""
echo "This script will help you prepare your Developer ID Application certificate for GitHub Actions."
echo ""
echo "Prerequisites:"
echo "1. Your 'Developer ID Application' certificate must be installed in Keychain Access"
echo "2. You need to know the exact name of your certificate"
echo ""
echo "Steps this script will guide you through:"
echo "1. List available Developer ID certificates"
echo "2. Export the certificate as a .p12 file"
echo "3. Convert it to base64 format"
echo "4. Provide instructions for adding to GitHub Secrets"
echo ""

read -p "Press Enter to continue..."

echo ""
echo "Available Developer ID certificates in your keychain:"
echo "----------------------------------------------------"
security find-identity -v -p codesigning | grep "Developer ID Application"

echo ""
echo "Copy the exact certificate name from above (everything after the quotes)."
read -p "Enter certificate name: " CERT_NAME

echo ""
read -p "Enter a password for the .p12 file (you'll need this for GitHub secrets): " -s P12_PASSWORD
echo ""

echo ""
echo "Exporting certificate..."
TEMP_P12="/tmp/developer-cert.p12"

# Export the certificate
security export -k ~/Library/Keychains/login.keychain-db -t identities -f pkcs12 -P "$P12_PASSWORD" -o "$TEMP_P12" <<< "$CERT_NAME"

if [ $? -ne 0 ]; then
    echo "Failed to export certificate. Please check the certificate name and try again."
    exit 1
fi

echo "Certificate exported successfully!"
echo ""
echo "Converting to base64..."

# Convert to base64
CERT_BASE64=$(base64 < "$TEMP_P12")

# Clean up
rm "$TEMP_P12"

echo ""
echo "Certificate prepared successfully!"
echo ""
echo "GitHub Secrets Setup Instructions:"
echo "=================================="
echo ""
echo "1. Go to your GitHub repository: https://github.com/vijaythecoder/clueless"
echo "2. Navigate to Settings > Secrets and variables > Actions"
echo "3. Click 'New repository secret' and add the following secrets:"
echo ""
echo "   NATIVEPHP_CERTIFICATE_BASE64"
echo "   Value: (The base64 string has been copied to your clipboard)"
echo ""
echo "   NATIVEPHP_CERTIFICATE_PASSWORD"
echo "   Value: $P12_PASSWORD"
echo ""
echo "   NATIVEPHP_APPLE_ID"
echo "   Value: contact@vijaykumar.me"
echo ""
echo "   NATIVEPHP_APPLE_ID_PASS"
echo "   Value: uyqq-dvig-nwxf-rdeu"
echo ""
echo "   NATIVEPHP_APPLE_TEAM_ID"
echo "   Value: 9D7F3MX3L3"
echo ""

# Copy to clipboard if possible
if command -v pbcopy &> /dev/null; then
    echo "$CERT_BASE64" | pbcopy
    echo "✅ The base64 certificate has been copied to your clipboard!"
else
    echo "Base64 certificate (copy this for NATIVEPHP_CERTIFICATE_BASE64):"
    echo "================================================================"
    echo "$CERT_BASE64"
    echo "================================================================"
fi

echo ""
echo "After adding all secrets, the next build will be properly signed!"