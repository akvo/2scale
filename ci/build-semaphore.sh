#!/usr/bin/env bash

set -eu

#cp .env.prod .env

docker run \
--rm \
--volume "$(pwd):/app" \
--workdir /app \
--entrypoint /bin/sh \
php:7.4-cli -c 'curl -sS https://getcomposer.org/installer | php && php composer.phar install'

docker run \
--rm \
--volume "$(pwd):/app" \
--workdir /app \
--entrypoint /bin/sh \
php:7.4-cli -c 'php composer.phar dump-autoload'

docker run \
--rm \
--volume "$(pwd):/app" \
--workdir "/app" \
--entrypoint /bin/sh \
node:8-alpine -c 'npm i && npm run prod'
