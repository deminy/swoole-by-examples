# This Dockerfile is to build client-side images, e.g., "deminy/swoole-by-examples:client-6.2".
# @see https://hub.docker.com/r/deminy/swoole-by-examples

ARG SWOOLE_VERSION=6.2
ARG PHP_VERSION=8.4

FROM phpswoole/swoole:${SWOOLE_VERSION}-php${PHP_VERSION}

# 1. The mysqli extension is to run the examples from scripts "./examples/hooks/mysqli.php" and
#    "./examples/pool/database-pool/mysqli.php".
# 2. Package mosquitto-clients (the MQTT clients mosquitto_sub and mosquitto_pub) is to run the example from script
#    "./examples/servers/mqtt.php".
# 3. Package netcat-traditional (command nc) and the websocat binary are to talk to the TCP, UDP, and WebSocket servers
#    in the server container by hand, as shown in the docblocks of the examples under "./examples/servers/".
RUN \
    set -ex && \
    docker-php-ext-install mysqli && \
    apt-get update && \
    apt-get install -y mosquitto-clients netcat-traditional --no-install-recommends && \
    curl -sfL -o /usr/bin/websocat "https://github.com/vi/websocat/releases/download/v1.12.0/websocat.$(uname -m)-unknown-linux-musl" && \
    chmod 755 /usr/bin/websocat && \
    rm -rf /var/lib/apt/lists/*

# The repository root is mounted at /var/www, and the examples document their run commands relative to the
# examples folder (e.g., "./clients/http1.php"), so make that folder the working directory.
WORKDIR /var/www/examples
