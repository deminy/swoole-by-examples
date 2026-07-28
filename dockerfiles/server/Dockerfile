# This Dockerfile is to build server-side images, e.g., "deminy/swoole-by-examples:server-6.2".
# @see https://hub.docker.com/r/deminy/swoole-by-examples

ARG SWOOLE_VERSION=6.2
ARG PHP_VERSION=8.4

FROM phpswoole/swoole:${SWOOLE_VERSION}-php${PHP_VERSION}

COPY ./rootfilesystem /

# 1. The System V messages support is to run the example from script "./examples/pool/process-pool/client.php".
# 2. The APCu extension is to run the example from script "./examples/servers/apcu-caching.php".
RUN \
    set -ex && \
    docker-php-ext-install sysvmsg && \
    pecl channel-update pecl && \
    pecl install apcu-stable && \
    docker-php-ext-enable apcu && \
    echo "apc.enable_cli=1" >> $(php-config --ini-dir)/docker-php-ext-apcu.ini && \
    apt-get update && \
    apt-get install -y net-tools watch --no-install-recommends && \
    rm -rf /var/lib/apt/lists/*

# The repository root is mounted at /var/www, and the examples document their run commands relative to the
# examples folder (e.g., "./servers/http1.php"), so make that folder the working directory.
WORKDIR /var/www/examples
