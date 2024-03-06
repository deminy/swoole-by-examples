# This Dockerfile is to build Docker image "deminy/swoole-by-examples:server-5.1".
# @see https://hub.docker.com/r/deminy/swoole-by-examples
FROM phpswoole/swoole:5.1-php8.2

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
