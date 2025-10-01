# syntax=docker/dockerfile:1

FROM php:8.2-apache AS base
LABEL maintainer="jekkos"

# System deps
RUN apt-get update && apt-get install -y --no-install-recommends \
    git unzip curl ca-certificates libicu-dev libjpeg-dev libfreetype6-dev libzip-dev libgd-dev gnupg \
 && rm -rf /var/lib/apt/lists/*

# PHP extensions
RUN docker-php-ext-configure gd --with-jpeg --with-freetype \
 && docker-php-ext-install -j"$(nproc)" mysqli bcmath intl gd zip

# Apache mods & PHP config
RUN a2enmod rewrite headers \
 && echo 'date.timezone="${PHP_TIMEZONE}"' > /usr/local/etc/php/conf.d/timezone.ini

ENV APACHE_DOCUMENT_ROOT=/app/public
RUN set -eux; \
    sed -ri 's!DocumentRoot .*!DocumentRoot /app/public!g' /etc/apache2/sites-available/000-default.conf; \
    sed -ri 's!<Directory /var/www/>!<Directory /app/>!g' /etc/apache2/apache2.conf; \
    sed -ri 's!<Directory /var/www/html>!<Directory /app/public>!g' /etc/apache2/apache2.conf; \
    printf '<Directory "/app/public">\n  AllowOverride All\n</Directory>\n' > /etc/apache2/conf-available/app-override.conf; \
    a2enconf app-override

WORKDIR /app

# --- Optional: install Node in base (only if you truly need it during build)
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
 && apt-get update && apt-get install -y --no-install-recommends nodejs \
 && node -v && npm -v \
 && rm -rf /var/lib/apt/lists/*

# ---------- Composer deps stage (installs to /app/vendor) ----------
FROM base AS deps

ENV COMPOSER_ALLOW_SUPERUSER=1
# Install Composer binary
RUN curl -sS https://getcomposer.org/installer | php -- \
      --install-dir=/usr/local/bin --filename=composer \
 && composer --version

# Only copy composer files first to leverage cache
COPY composer.json composer.lock* /app/

# Install PHP deps to /app/vendor (no-dev for prod)
RUN composer install \
      --no-dev --prefer-dist --optimize-autoloader \
      --no-interaction --no-progress

# ---------- Final runtime image (brings in app code + vendor) ----------
FROM base AS ospos

WORKDIR /app

# Copy app code
COPY . /app

# Ensure vendor ends up inside the project regardless of .dockerignore or later copies
COPY --from=deps /app/vendor /app/vendor

# Build frontend if present (optional)
RUN if [ -f package-lock.json ] || [ -f npm-shrinkwrap.json ]; then \
      npm ci; \
    elif [ -f package.json ]; then \
      npm i --no-fund --no-audit; \
    fi && \
    if [ -f package.json ]; then \
      npm run build || true; \
    fi

# Ensure writable dirs
RUN mkdir -p /app/writable/{cache,logs,session,uploads} \
 && chown -R www-data:www-data /app/writable

EXPOSE 80

# ---------- Test stage ----------
FROM ospos AS ospos_test
RUN apt-get update && apt-get install -y --no-install-recommends wget \
 && wget -O /bin/wait-for-it.sh https://raw.githubusercontent.com/vishnubob/wait-for-it/master/wait-for-it.sh \
 && chmod +x /bin/wait-for-it.sh
WORKDIR /app/tests
CMD ["/app/vendor/phpunit/phpunit/phpunit"]

# ---------- Dev stage with Xdebug ----------
FROM ospos AS ospos_dev

ARG USERID=1000
ARG GROUPID=1000

RUN groupadd -g ${GROUPID} ospos || true \
 && useradd -m -u ${USERID} -g ${GROUPID} ospos || true

RUN yes | pecl install xdebug \
 && echo "zend_extension=$(find /usr/local/lib/php/extensions/ -name xdebug.so)" > /usr/local/etc/php/conf.d/xdebug.ini \
 && echo "xdebug.mode=debug" >> /usr/local/etc/php/conf.d/xdebug.ini \
 && echo "xdebug.remote_autostart=off" >> /usr/local/etc/php/conf.d/xdebug.ini
