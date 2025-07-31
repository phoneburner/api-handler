# syntax=docker/dockerfile:1
##------------------------------------------------------------------------------
# PHP Build Stages
##------------------------------------------------------------------------------

FROM php:8.4-cli AS php
ARG USER_UID=1000
ARG USER_GID=1000
WORKDIR /app
SHELL ["/bin/bash", "-c"]
ENV PATH="/app/bin:/app/vendor/bin:/app/build/composer/bin:/home/dev/.composer/bin:$PATH"
ENV XDEBUG_MODE="off"

# Create a non-root user to run the application
RUN groupadd --gid $USER_GID dev \
    && useradd --uid $USER_UID --gid $USER_GID --groups www-data --create-home --shell /bin/bash dev

# Update the package list and install the latest version of the packages
RUN --mount=type=cache,target=/var/lib/apt,sharing=locked apt-get update && apt-get dist-upgrade --yes

RUN --mount=type=cache,target=/var/lib/apt apt-get install --yes --quiet --no-install-recommends \
    libzip-dev \
    unzip \
  && cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

RUN <<-EOF
  set -eux
  export MAKEFLAGS="-j $(nproc)"
  pecl install xdebug
  docker-php-ext-enable xdebug
EOF

RUN <<-EOF
  set -eux
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

COPY --link --from=composer/composer /usr/bin/composer /usr/local/bin/composer
COPY --link --chown=$USER_UID:$USER_GID --from=composer/composer /tmp/* /home/dev/.composer/

USER dev

##------------------------------------------------------------------------------
# Utility Build Stages
##------------------------------------------------------------------------------

# Prettier Image for Code Formatting
FROM node:alpine AS prettier
ENV NPM_CONFIG_PREFIX=/home/node/.npm-global
ENV PATH=$PATH:/home/node/.npm-global/bin
WORKDIR /app
RUN npm install --global --save-dev --save-exact npm@latest prettier
ENTRYPOINT ["prettier"]
