FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-scripts \
    --optimize-autoloader \
    --prefer-dist

FROM php:8.4-cli-alpine AS base
WORKDIR /app

RUN apk add --no-cache \
        icu-libs \
        libzip \
        rabbitmq-c \
    && apk add --no-cache --virtual .build-deps \
        icu-dev \
        libzip-dev \
        rabbitmq-c-dev \
        autoconf \
        g++ \
        make \
    && docker-php-ext-install \
        intl \
        opcache \
        zip \
    && pecl install amqp \
    && docker-php-ext-enable amqp \
    && apk del .build-deps

CMD ["php", "bin/console", "messenger:consume", "async", "--time-limit=3600", "-vv"]

FROM base AS dev
RUN apk add --no-cache git unzip
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1

FROM base AS prod
ENV APP_ENV=prod
COPY --from=vendor /app/vendor ./vendor
COPY . .
