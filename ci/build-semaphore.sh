#!/usr/bin/env bash

set -eu

# Optional: uncomment if you use .env.prod
# cp .env.prod .env

docker run \
--rm \
--volume "$(pwd):/app" \
--workdir /app \
composer:2 \
install --no-interaction --prefer-dist --optimize-autoloader

docker run \
--rm \
--volume "$(pwd):/app" \
--workdir /app \
composer:2 \
dump-autoload --optimize

docker run \
--rm \
--volume "$(pwd):/app" \
--workdir /app \
node:8-alpine \
sh -c 'npm install && npm run prod'
