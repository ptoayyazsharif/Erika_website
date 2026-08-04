#!/bin/sh
#
# Everything that has to happen after the environment exists and before the
# first request is served. Runs as root, hands off to supervisord as PID 1's
# child, and drops to www-data for anything that writes a file the app will
# later read.

set -eu

cd /var/www/html

say()  { printf '\033[36m[escalate]\033[0m %s\n' "$*" >&2; }
die()  { printf '\033[31m[escalate] %s\033[0m\n' "$*" >&2; exit 1; }
as_app() { su-exec www-data "$@"; }

# ── 1. Refuse to start without a key ─────────────────────────────────────────
#
# Not a convenience check. APP_KEY is the key every encrypted field in this app
# is sealed with — journal entries, gratitude, faith language, failure reasons.
#
# The tempting thing to do here is `php artisan key:generate` when the variable
# is empty. That would be the worst bug in the whole deployment: the container
# would start fine, and on the next restart it would generate a *different*
# key, at which point every entry every user has ever written becomes
# permanently unreadable. There is no recovery from that, so this fails loudly
# instead.
if [ -z "${APP_KEY:-}" ]; then
    die "APP_KEY is not set.

  Generate one once:      php artisan key:generate --show
  Then paste it into Coolify → Environment Variables as APP_KEY.

  Never change it after real data exists — every encrypted entry would be lost."
fi

# A key that is present but malformed fails later, at the first decrypt, with
# an error that reads like data corruption rather than a typo in a setting.
case "${APP_KEY}" in
    base64:?*) : ;;
    *) [ "${#APP_KEY}" -eq 32 ] || die "APP_KEY is set but malformed — expected the 'base64:…' string that 'php artisan key:generate --show' prints. Check for a truncated paste." ;;
esac

if [ "${APP_DEBUG:-false}" = "true" ] && [ "${APP_ENV:-production}" = "production" ]; then
    die "APP_DEBUG=true in production. Laravel's error page dumps the environment, including both API keys. Set APP_DEBUG=false."
fi

# ── 2. The storage tree ──────────────────────────────────────────────────────
#
# The persistent volume mounts over storage/ and is empty on first boot, so the
# skeleton cannot be baked into the image — it has to be recreated here, every
# time, or Laravel dies on a missing framework/views directory.
for dir in \
    storage/app/escalate \
    storage/app/private \
    storage/app/public \
    storage/database \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache
do
    mkdir -p "$dir"
done

chown -R www-data:www-data storage bootstrap/cache

# Narration audio and vision photos: owner only, no group, no world. Nothing
# but PHP (running as www-data) ever reads them, and it does so through
# MediaController, which checks ownership first.
chmod 700 storage/app storage/app/escalate storage/database
find storage/app/escalate -type d -exec chmod 700 {} + 2>/dev/null || true
find storage/app/escalate -type f -exec chmod 600 {} + 2>/dev/null || true

# ── 3. SQLite ────────────────────────────────────────────────────────────────
#
# Deliberately inside the volume rather than the image, and outside public/ —
# a database file reachable over HTTP is the whole journal in one download.
if [ "${DB_CONNECTION:-sqlite}" = "sqlite" ]; then
    DB_DATABASE="${DB_DATABASE:-/var/www/html/storage/database/escalate.sqlite}"
    export DB_DATABASE

    if [ ! -f "$DB_DATABASE" ]; then
        say "creating a new SQLite database at $DB_DATABASE"
        install -o www-data -g www-data -m 600 /dev/null "$DB_DATABASE"
    else
        chown www-data:www-data "$DB_DATABASE"
        chmod 600 "$DB_DATABASE"
    fi
fi

# ── 4. Schema ────────────────────────────────────────────────────────────────
#
# --force because production migrations are otherwise interactive, and there is
# no terminal here. If this fails the container must not start: a half-migrated
# schema serving traffic writes rows that the next deploy cannot read.
say "running migrations"
as_app php artisan migrate --force --no-interaction

# ── 5. Caches ────────────────────────────────────────────────────────────────
#
# Built at boot, not at build. config:cache freezes the environment into a PHP
# file, and the environment does not exist until the container runs — caching
# it during `docker build` would bake in an empty APP_KEY and a placeholder
# APP_URL.
say "warming caches"
as_app php artisan config:cache
as_app php artisan route:cache
as_app php artisan view:cache

# Any worker left over from the previous container should not pick up work with
# the old cached config. Harmless when there is nothing listening.
as_app php artisan queue:restart >/dev/null 2>&1 || true

say "ready — nginx on :8080, php-fpm, and one queue worker"

exec /usr/bin/supervisord -c /etc/supervisord.conf
