#!/bin/bash

# Script to verify certificate and password
echo "Certificate Verification Tool"
echo "============================"
echo ""
echo "This will help verify your certificate export and password."
echo ""

read -p "Enter the path to your .p12 file: " P12_PATH
read -p "Enter the password for the .p12 file: " -s P12_PASSWORD
echo ""

echo ""
echo "Verifying certificate..."

# Check if file exists
if [ ! -f "$P12_PATH" ]; then
    echo "❌ File not found: $P12_PATH"
    exit 1
fi

# Try to read certificate info
echo "Testing password..."
if security cms -D -i "$P12_PATH" -k "$P12_PASSWORD" 2>/dev/null; then
    echo "✅ Password is correct!"
else
    echo "❌ Password verification failed. Please check your password."
    echo ""
    echo "Common issues:"
    echo "1. Wrong password"
    echo "2. Special characters in password need escaping"
    echo "3. Certificate was exported with a different password"
    exit 1
fi

echo ""
echo "Extracting certificate information..."
openssl pkcs12 -in "$P12_PATH" -passin pass:"$P12_PASSWORD" -noout -info 2>&1 | grep -v "MAC verified OK"

echo ""
echo "Certificate details:"
security cms -D -i "$P12_PATH" -k "$P12_PASSWORD" | openssl x509 -noout -subject -issuer -dates

echo ""
echo "✅ Certificate verified successfully!"
echo ""
echo "Converting to base64..."
CERT_BASE64=$(base64 < "$P12_PATH")

echo ""
echo "Base64 length: ${#CERT_BASE64} characters"
echo ""
echo "If you need to update the GitHub secret, the base64 string has been copied to clipboard."

if command -v pbcopy &> /dev/null; then
    echo "$CERT_BASE64" | pbcopy
    echo "✅ Base64 certificate copied to clipboard!"
fi