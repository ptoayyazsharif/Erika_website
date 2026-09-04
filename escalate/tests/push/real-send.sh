#!/usr/bin/env bash
#
# Send a real notification to a stand-in push service and check what arrived.
#
# The one part of push that nothing else exercises: VAPID signing and payload
# encryption, against a listening socket rather than a faked client. See the
# long note at the top of send.php for why it lives here and what it does not
# prove.
#
#   cd escalate && bash tests/push/real-send.sh
#
# WARNING: it deletes every row in push_subscriptions, so point it at a
# throwaway database, never production.

set -euo pipefail

cd "$(dirname "$0")/../.."

PORT="${PUSH_PORT:-9411}"
CAPTURE="$(mktemp -t escalate-push-XXXXXX.json)"

export PUSH_PORT="$PORT"
export PUSH_CAPTURE="$CAPTURE"

node tests/push/listener.mjs "$PORT" "$CAPTURE" &
LISTENER=$!
trap 'kill "$LISTENER" 2>/dev/null || true; rm -f "$CAPTURE"' EXIT

# The listener has to be accepting before anything is sent, or the first
# request fails for a reason that has nothing to do with push.
for _ in $(seq 1 40); do
    if node -e "require('net').connect($PORT,'127.0.0.1').on('connect',()=>process.exit(0)).on('error',()=>process.exit(1))" 2>/dev/null; then
        break
    fi
    sleep 0.25
done

php artisan tinker --execute="require '$(pwd)/tests/push/send.php';"
