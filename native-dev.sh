#!/bin/bash

# Set correct environment variables
export NATIVEPHP_PHP_BINARY_PATH="vendor/nativephp/php-bin"
export NATIVEPHP_PHP_BINARY_VERSION="8.4"

# Run the native:serve command with verbose output
php artisan native:serve --no-interaction -v