# syntax=docker/dockerfile:1.4
FROM php:8.3-cli as php
WORKDIR /app
ENV PATH /app/bin:/app/vendor/bin::/home/dev/.composer/vendor/bin/:$PATH

RUN <<-EOF
  groupadd --gid 1000 dev;
  useradd --system --create-home --uid 1000 --gid 1000 --shell /bin/bash dev;
  apt-get update;
  apt-get install -y git libzip-dev unzip;
  apt-get clean;
  MAKEFLAGS="-j $(nproc)" pecl install xdebug;
  docker-php-ext-enable xdebug;
  mkdir -p "/home/dev/.composer";
  chown -R "dev:dev" "/home/dev/.composer";
  cat <<-EOF > /usr/local/etc/php/conf.d/settings.ini
      memory_limit=1G
      assert.exception=1
      error_reporting=E_ALL
      display_errors=1
      log_errors=on
      xdebug.log_level=0
      xdebug.mode=off
  EOF
EOF

COPY --from=composer/composer:latest-bin /composer /usr/bin/composer

USER dev
