# This Dockerfile is to build Docker image "deminy/swoole-by-examples:client".
# @see https://hub.docker.com/r/deminy/swoole-by-examples
FROM phpswoole/swoole:4.7-php7.4

RUN \
    set -ex && \
    pecl update-channels && \
    pecl install redis-5.3.4 && \
    docker-php-ext-enable redis && \
    docker-php-ext-install mysqli pdo_mysql && \
    apt-get update && \
    apt-get install -y netcat xxd --no-install-recommends && \
    curl -sfL -o websocat.deb https://github.com/vi/websocat/releases/download/v1.5.0/websocat_1.5.0_ssl1.1_amd64.deb && \
    dpkg -i websocat.deb && \
    rm -rf websocat.deb /var/lib/apt/lists/* && \
    composer require -d ${HOME} -n predis/predis:~1.1.0 && \
    composer clearcache
