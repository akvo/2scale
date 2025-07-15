#!/usr/bin/env bash

set -eu

# Optional: copy env if needed
# cp .env.prod .env

# Define working directory
WORKDIR="$(pwd)"

# Step 1: Build custom PHP 7.4 + Composer Docker image
docker build -f ci/composer.Dockerfile -t php74-composer .

# Step 2: Run Composer install
docker run --rm \
-v "$WORKDIR:/app" \
-w /app \
php74-composer \
composer install

# Step 3: Run Composer dump-autoload
docker run --rm \
-v "$WORKDIR:/app" \
-w /app \
php74-composer \
composer dump-autoload

# Step 4: Run npm install & build (via Node 8)
docker run --rm \
-v "$WORKDIR:/app" \
-w /app \
node:8-alpine \
sh -c "npm install && npm run prod"
