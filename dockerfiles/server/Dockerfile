# This Dockerfile is to build Docker image "deminy/swoole-by-examples:server".
# @see https://hub.docker.com/r/deminy/swoole-by-examples
FROM phpswoole/swoole:5.1-php8.2

COPY ./rootfilesystem /

# The System V messages support is to run some example in script "./examples/pool/process-pool/client.php".
RUN \
    set -ex && \
    docker-php-ext-install sysvmsg && \
    apt-get update && \
    apt-get install -y net-tools watch --no-install-recommends && \
    rm -rf /var/lib/apt/lists/*
