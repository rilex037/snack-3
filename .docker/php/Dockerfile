FROM registry.gitlab.com/rilex/docker-dev/php:8.1.0-fpm-alpine

RUN pecl install xdebug \
    && docker-php-ext-enable xdebug;