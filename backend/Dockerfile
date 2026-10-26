# AegisThread backend — development-grade image (dev dependencies included).
#
# Runtime of record per ADR-0009: PHP 8.5 (php:8.5-cli). All final
# verification (phpunit, phpstan, php-cs-fixer, bin/console) runs inside
# this image, not on the host.

FROM php:8.5-cli

# unzip: composer dist downloads. libpq-dev: to build pdo_pgsql (ADR-0002).
RUN apt-get update \
    && apt-get install -y --no-install-recommends unzip libpq-dev \
    && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-install pdo_pgsql

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Install dependencies first so source changes do not bust this layer.
COPY composer.json composer.lock ./
RUN composer install --no-interaction --no-progress --no-scripts

COPY . .

# Symfony's bootEnv hard-requires /app/.env to exist (PathException
# otherwise), but .dockerignore rightly keeps the real .env out of the image.
# Bake the example file as fallback defaults — real configuration arrives via
# environment variables (compose env_file/environment), which always win
# over .env files. Dev-only placeholder values; never real secrets.
RUN cp .env.example .env

EXPOSE 8080

# Dev web server; the compose `backend` service runs this command.
CMD ["php", "-S", "0.0.0.0:8080", "-t", "public", "public/index.php"]
