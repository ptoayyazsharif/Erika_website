#!/usr/bin/env bash
#
# One-shot Coolify configuration for Escalate.
#
# Run this ON THE COOLIFY SERVER. It talks to the API over localhost, which is
# the only place port 8000 is reachable — that is a good thing and worth
# keeping: a control panel that can deploy arbitrary containers should not be
# answering the open internet.
#
#   export ANTHROPIC_API_KEY=sk-ant-...
#   export ELEVENLABS_API_KEY=sk_...
#   bash coolify-setup.sh
#
# It is safe to run twice. Existing variables are updated rather than
# duplicated, and an existing volume is left alone.

set -uo pipefail

API="http://localhost:8000/api/v1"
TOKEN="1|BwScEHGs8KjcTQxLBD0sa0HBgwdIqslj7O6JGnAM4390295c"
UUID="um4fjdz4zww4aiv7xmj37czt"
APP_URL="http://um4fjdz4zww4aiv7xmj37czt.2.25.93.114.sslip.io"

bold() { printf '\033[1m%s\033[0m\n' "$*"; }
ok()   { printf '  \033[32m✓\033[0m %s\n' "$*"; }
warn() { printf '  \033[33m!\033[0m %s\n' "$*"; }
die()  { printf '\033[31m%s\033[0m\n' "$*" >&2; exit 1; }

api() { # method path [json]
    curl -sS --max-time 30 -X "$1" "$API$2" \
        -H "Authorization: Bearer $TOKEN" \
        -H "Content-Type: application/json" \
        -H "Accept: application/json" \
        ${3:+-d "$3"}
}

command -v curl >/dev/null || die "curl is not installed."
command -v openssl >/dev/null || die "openssl is not installed."

bold "0. Checking the API"
me=$(api GET "/applications/$UUID")
case "$me" in
    *'"uuid"'*) ok "reached Coolify, application found" ;;
    *Unauthenticated*) die "The API token was rejected. Generate a new one at Keys & Tokens → API tokens, with write permission." ;;
    *) die "Unexpected reply from the API: $me" ;;
esac

# ── 1. APP_KEY ───────────────────────────────────────────────────────────────
#
# Generated here, on your machine, so it never travels through a chat log or a
# repository. It is the key every private field in this app is encrypted with:
# journal entries, gratitude, faith language.
#
# Reused if one is already set. Regenerating a key on a database that already
# holds entries makes every one of them permanently unreadable, so this script
# will not do that behind your back.
bold "1. APP_KEY"
existing=$(api GET "/applications/$UUID/envs")
APP_KEY=$(printf '%s' "$existing" | grep -o '"key":"APP_KEY","value":"[^"]*"' | sed 's/.*"value":"//;s/"$//')

if [ -n "$APP_KEY" ]; then
    ok "an APP_KEY is already set — keeping it"
    NEW_KEY=no
else
    APP_KEY="base64:$(openssl rand -base64 32)"
    NEW_KEY=yes
    ok "generated a new one"
fi

# ── 2. Environment ───────────────────────────────────────────────────────────
bold "2. Environment variables"

[ -n "${ANTHROPIC_API_KEY:-}" ]  || warn "ANTHROPIC_API_KEY not exported — readings will fail until you add it"
[ -n "${ELEVENLABS_API_KEY:-}" ] || warn "ELEVENLABS_API_KEY not exported — narration will fail until you add it"

set_env() { # key value
    local key="$1" value="$2" body resp
    [ -n "$value" ] || return 0
    body=$(printf '{"key":"%s","value":"%s","is_preview":false,"is_literal":true,"is_multiline":false,"is_shown_once":false}' "$key" "$value")

    resp=$(api POST "/applications/$UUID/envs" "$body")
    case "$resp" in
        *uuid*) ok "$key set" ; return 0 ;;
    esac
    # Already there — update it in place rather than duplicating.
    resp=$(api PATCH "/applications/$UUID/envs" "$body")
    case "$resp" in
        *updated*|*uuid*) ok "$key updated" ;;
        *) warn "$key may not have been saved: $resp" ;;
    esac
}

set_env APP_NAME  "Escalate"
set_env APP_ENV   "production"
set_env APP_DEBUG "false"
set_env APP_KEY   "$APP_KEY"
set_env APP_URL   "$APP_URL"

set_env LOG_CHANNEL "stderr"
set_env LOG_LEVEL   "error"

set_env DB_CONNECTION "sqlite"

# false, deliberately: the address above is plain HTTP. With true, the browser
# refuses to store the session cookie and login silently never completes. Flip
# it the moment a real domain with TLS is in front.
set_env SESSION_SECURE_COOKIE "false"
set_env SESSION_ENCRYPT       "true"
set_env SESSION_SAME_SITE     "strict"

# The Docker network ranges only. With '*', anyone can forge X-Forwarded-For,
# and every rate limit in the app — the login throttle included — is keyed on
# the client IP.
set_env TRUSTED_PROXIES "10.0.0.0/8,172.16.0.0/12,192.168.0.0/16"

# Above NarrateStory's 240s timeout, or the queue hands a still-running job to
# a second worker and the narration is paid for twice.
set_env DB_QUEUE_RETRY_AFTER "300"

set_env ANTHROPIC_API_KEY  "${ANTHROPIC_API_KEY:-}"
set_env ELEVENLABS_API_KEY "${ELEVENLABS_API_KEY:-}"

# ── 3. Persistent storage ────────────────────────────────────────────────────
#
# The database, every narration, every photo, the sessions and the queue all
# live under this one path. Without the volume each redeploy silently starts a
# brand new empty app: the deploy succeeds and every account is gone.
bold "3. Persistent volume"
vols=$(api GET "/applications/$UUID/storages")
if printf '%s' "$vols" | grep -q '/var/www/html/storage'; then
    ok "already mounted at /var/www/html/storage"
else
    resp=$(api POST "/applications/$UUID/storages" \
        '{"type":"persistent","name":"escalate-storage","mount_path":"/var/www/html/storage"}')
    case "$resp" in
        *uuid*) ok "created escalate-storage → /var/www/html/storage" ;;
        *)      warn "could not create the volume: $resp" ;;
    esac
fi

# ── 4. Port and health check ─────────────────────────────────────────────────
#
# The container listens on 8080; Coolify defaults to 3000. A mismatch shows up
# as a container that is healthy but that the proxy calls unreachable.
bold "4. Port and health check"
resp=$(api PATCH "/applications/$UUID" '{
    "ports_exposes": "8080",
    "health_check_enabled": true,
    "health_check_path": "/up",
    "health_check_port": "8080"
}')
case "$resp" in
    *uuid*|*success*) ok "port 8080, health check on /up" ;;
    *) warn "could not update: $resp" ;;
esac

# ── 5. Deploy ────────────────────────────────────────────────────────────────
bold "5. Deploying"
resp=$(api POST "/deploy?uuid=$UUID&force=false")
printf '  %s\n' "$resp"

echo
bold "Done."
if [ "$NEW_KEY" = yes ]; then
    cat <<BANNER

  ┌────────────────────────────────────────────────────────────────────┐
  │  SAVE THIS. It is not recoverable and not stored anywhere else.     │
  └────────────────────────────────────────────────────────────────────┘

    APP_KEY=$APP_KEY

  Put it in a password manager, and keep it OUT of any backup that also
  contains the database — the two together are the plaintext journal of
  every user. Kept apart, a stolen backup is noise.

BANNER
fi

echo "  Watch the deployment in Coolify. A healthy boot ends with:"
echo "    [escalate] ready — nginx on :8080, php-fpm, and one queue worker"
echo "    INFO success: php-fpm / nginx / queue entered RUNNING state"
echo
echo "  Then: $APP_URL"
