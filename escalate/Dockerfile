# syntax=docker/dockerfile:1
#
# Escalate — one self-contained image: nginx, php-fpm and the queue worker.
#
# This replaces the Nixpacks build, which died in `apt-get update` while
# fetching Ubuntu package lists. Alpine's own repositories are the only network
# dependency here besides Packagist, and everything that build installed with
# apt is either already in the base image or two lines of apk.

# ── Stage 1: dependencies ────────────────────────────────────────────────────
#
# Composer runs on the same PHP as the runtime stage, rather than inside the
# `composer:2` image. That matters: composer resolves ext-* requirements
# against whatever PHP it is running on, so a mismatched builder is how
# projects end up needing --ignore-platform-reqs — which then silently installs
# packages the runtime cannot actually load. Here the platform check is real.
FROM php:8.4-cli-alpine AS vendor

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# composer needs one of ext-zip or the unzip binary to unpack a dist archive.
RUN apk add --no-cache unzip git

WORKDIR /app

# Manifests first, on their own layer. A change to a Blade template then costs
# nothing — Docker reuses the resolved dependency tree.
COPY composer.json composer.lock ./

# --no-scripts on this pass: the post-autoload-dump hook runs `artisan
# package:discover`, and artisan has not been copied yet.
RUN composer install \
        --no-dev \
        --no-scripts \
        --no-autoloader \
        --prefer-dist \
        --no-interaction \
        --no-progress

COPY . .

# --no-dev is a security control, not a size optimisation. filp/whoops is a dev
# dependency and its error page dumps $_ENV — both API keys and APP_KEY. With
# it absent, even an accidental APP_DEBUG=true cannot render them.
RUN composer install \
        --no-dev \
        --optimize-autoloader \
        --no-interaction \
        --no-progress \
    && composer clear-cache

# ── Stage 2: runtime ─────────────────────────────────────────────────────────
FROM php:8.4-fpm-alpine AS app

# su-exec drops privileges in the entrypoint; the rest is the web server and
# the process supervisor. $PHPIZE_DEPS is the compiler toolchain the base image
# defines for building extensions — installed as a virtual package and removed
# in the same layer so it never reaches the final image.
RUN set -eux; \
    apk add --no-cache \
        nginx \
        supervisor \
        su-exec \
        tzdata; \
    apk add --no-cache --virtual .build-deps $PHPIZE_DEPS linux-headers; \
    docker-php-ext-install -j"$(nproc)" \
        pcntl \
        opcache \
        pdo_mysql; \
    apk del .build-deps; \
    rm -rf /var/cache/apk/* /tmp/*

# pcntl is what lets `queue:work` handle SIGTERM: without it a redeploy kills a
# worker mid-narration instead of letting it finish the job in hand.
# pdo_mysql is here so moving off SQLite later is an environment change rather
# than an image rebuild. pdo_sqlite and curl are already in the base image.

# nginx workers run as www-data, so the directories they write to belong to
# www-data rather than to the `nginx` user the Alpine package creates.
# /var/lib/nginx/logs is the compiled-in default error-log path, which nginx
# opens before it has read the config; without it every start logs an alert.
RUN set -eux; \
    mkdir -p /tmp/nginx-client /var/lib/nginx/logs /var/log/nginx; \
    chown -R www-data:www-data /tmp/nginx-client /var/lib/nginx /var/log/nginx; \
    rm -f /etc/nginx/http.d/default.conf

WORKDIR /var/www/html

COPY --from=vendor --chown=www-data:www-data /app /var/www/html

COPY docker/nginx.conf       /etc/nginx/nginx.conf
COPY docker/php.ini          /usr/local/etc/php/conf.d/zz-escalate.ini
COPY docker/fpm-pool.conf    /usr/local/etc/php-fpm.d/zz-escalate.conf
COPY docker/supervisord.conf /etc/supervisord.conf
COPY docker/entrypoint.sh    /usr/local/bin/entrypoint

# storage/ and bootstrap/cache are excluded from the build context (they hold
# user data and stale caches), so they have to be created rather than assumed.
# The entrypoint rebuilds the full tree at boot, once the volume is mounted.
RUN set -eux; \
    chmod +x /usr/local/bin/entrypoint; \
    mkdir -p storage bootstrap/cache; \
    chown -R www-data:www-data storage bootstrap/cache; \
    nginx -t -c /etc/nginx/nginx.conf

# Not that it should ever be there — .dockerignore excludes it — but an image
# that ships a .env ships the API keys to anyone who can pull it, so make the
# build fail rather than trust one file to hold the line.
RUN test ! -e /var/www/html/.env || \
    (echo 'FATAL: .env reached the image. Check .dockerignore.' >&2; exit 1)

# Unprivileged port: nothing in this container needs to bind below 1024.
# Set Coolify → Configuration → Network → Ports Exposes to 8080.
EXPOSE 8080

# /up is Laravel's own health route (bootstrap/app.php). Coolify polls it too,
# but having it here means `docker ps` tells the truth on its own.
HEALTHCHECK --interval=30s --timeout=5s --start-period=40s --retries=3 \
    CMD wget --quiet --tries=1 --spider http://127.0.0.1:8080/up || exit 1

ENTRYPOINT ["/usr/local/bin/entrypoint"]
