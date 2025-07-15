FROM php:7.4-cli

# Install dependencies for Composer and PHP extensions
RUN apt-get update && apt-get install -y \
    unzip \
    git \
    zlib1g-dev \
    && docker-php-ext-install zip \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php && \
    mv composer.phar /usr/local/bin/composer
