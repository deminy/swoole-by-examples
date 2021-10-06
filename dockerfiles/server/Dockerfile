# This Dockerfile is to build Docker image "deminy/swoole-by-examples:server".
# @see https://hub.docker.com/r/deminy/swoole-by-examples
FROM phpswoole/swoole:4.7-php7.4

COPY ./rootfilesystem /

RUN \
    set -ex && \
    apt-get update && \
    apt-get install -y net-tools --no-install-recommends && \
    rm -rf /var/lib/apt/lists/*
