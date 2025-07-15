#!/usr/bin/env sh
set -eu

# Install dependencies
composer install --no-interaction --prefer-dist --optimize-autoloader

# Regenerate the autoloader
composer dump-autoload
