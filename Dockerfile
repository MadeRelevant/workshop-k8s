FROM nginx:latest as nginx

COPY docker/nginx/vhost.conf /etc/nginx/conf.d/default.conf
COPY src /code

EXPOSE 80

FROM php:8-fpm-alpine as php

# Install our PHP extensions, for the workshop we only need mysql
# but in a real-world environment you would typically install other deps as well such as gd, xdebug etc
RUN apk add --no-cache --virtual .build-deps && \
        docker-php-ext-install pdo_mysql && \
        # Remove all dependencies we don't need anymore to keep our image as small as possible
        apk del .build-deps

EXPOSE 9000
COPY src /code

FROM mysql:8 as db
COPY docker/mysql/init.sql /docker-entrypoint-initdb.d/init.sql

# for the sake of simplicity we define the root password here. Normally this is injected at runtime via a secret provider
ENV MYSQL_ROOT_PASSWORD workshop
ENV MYSQL_DATABASE workshop
