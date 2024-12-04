# This Dockerfile is to build Docker image "deminy/swoole-by-examples:client-5.1".
# @see https://hub.docker.com/r/deminy/swoole-by-examples
FROM phpswoole/swoole:5.1-php8.3

RUN \
    set -ex && \
    docker-php-ext-install mysqli && \
    apt-get update && \
    apt-get install -y apache2-utils netcat-traditional xxd --no-install-recommends && \
    curl -sfL -o /usr/bin/websocat "https://github.com/vi/websocat/releases/download/v1.12.0/websocat.$(uname -m)-unknown-linux-musl" && \
    chmod 755 /usr/bin/websocat && \
    rm -rf /var/lib/apt/lists/*
