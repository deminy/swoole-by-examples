# This Dockerfile is to start a client-side container where only client-side scripts are to be executed.
# Please check file ./docker-compose.yml to see how it's used under this repository.
FROM phpswoole/swoole

RUN \
    pecl update-channels        && \
    pecl install redis-5.0.2    && \
    docker-php-ext-enable redis && \
    composer require -d ${HOME} -n predis/predis:~1.1.0
