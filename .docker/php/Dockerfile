FROM php:8.4-fpm-alpine AS build

ARG user
ARG uid

RUN apk add --no-cache \
    autoconf g++ make libpng-dev oniguruma-dev libxml2-dev \
    unzip krb5-dev git curl \
    bash tzdata postgresql-dev libzip-dev gnupg libjpeg-turbo-dev libwebp-dev $PHPIZE_DEPS \
    && docker-php-ext-install pdo_pgsql mbstring exif pcntl bcmath zip gd \
    && docker-php-ext-configure gd --with-webp --with-jpeg \
    && pecl update-channels \
    && pecl install -o -f redis \
    && pecl install -o -f raphf \
    && docker-php-ext-enable redis raphf \
    && apk del $PHPIZE_DEPS \
    && rm -rf /tmp/pear /var/cache/apk/* /root/.pearrc

COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/bin/

COPY --from=composer:2.8.6 /usr/bin/composer /usr/bin/composer

RUN adduser -D -u $uid -h /home/$user $user \
    && addgroup $user www-data \
    && addgroup $user root

RUN mkdir -p /home/$user/.composer && \
    chown -R $user:$user /home/$user

COPY php.ini /usr/local/etc/php/php.ini

WORKDIR /var/www/api

USER $user

RUN git config --global --add safe.directory /var/www/api
