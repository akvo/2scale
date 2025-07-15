#!/usr/bin/env bash

set -eu

#cp .env.prod .env

# Run Composer install
docker run \
--rm \
-v "$(pwd):/app" \
php:7.4-cli /bin/sh -c "\
    apt-get update && \
    apt-get install -y unzip git zlib1g-dev && \
    docker-php-ext-install zip && \
    curl -sS https://getcomposer.org/installer | php && \
php composer.phar install"

# Run Composer dump-autoload
docker run \
--rm \
-v "$(pwd):/app" \
php:7.4-cli /bin/sh -c "\
php composer.phar dump-autoload"

# Run npm
docker run \
--rm \
-v "$(pwd):/app" \
node:8-alpine /bin/sh -c "\
npm i && npm run prod"
