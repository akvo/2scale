FROM php:7.4-cli-buster

# Install system dependencies
RUN apt-get update && apt-get install -y \
    unzip \
    git \
    zlib1g-dev \
    && docker-php-ext-install zip \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php && \
    mv composer.phar /usr/local/bin/composer
