# This Dockerfile is to build client-side images, e.g., "deminy/swoole-by-examples:client-6.2".
# @see https://hub.docker.com/r/deminy/swoole-by-examples

ARG SWOOLE_VERSION=6.2
ARG PHP_VERSION=8.4

FROM phpswoole/swoole:${SWOOLE_VERSION}-php${PHP_VERSION}

RUN \
    set -ex && \
    docker-php-ext-install mysqli && \
    apt-get update && \
    apt-get install -y apache2-utils mosquitto-clients netcat-traditional xxd --no-install-recommends && \
    curl -sfL -o /usr/bin/websocat "https://github.com/vi/websocat/releases/download/v1.12.0/websocat.$(uname -m)-unknown-linux-musl" && \
    chmod 755 /usr/bin/websocat && \
    rm -rf /var/lib/apt/lists/*

# The repository root is mounted at /var/www, and the examples document their run commands relative to the
# examples folder (e.g., "./clients/http1.php"), so make that folder the working directory.
WORKDIR /var/www/examples
