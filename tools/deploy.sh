#!/usr/bin/env bash
# Upload files to the live site through the cPanel API, then prove they arrived.
#
#   CPANEL_USER=... CPANEL_TOKEN=... tools/deploy.sh index.php posts.php assets/photos/17/x.jpg
#
# Paths are relative to the repo root and land at the same path under public_html.
# Credentials come from the environment and are never written to the repo — ask
# the site owner for them each session.
#
# Three things about this host that cost time to discover:
#
#   * The cPanel hostname's certificate no longer covers cpanel.erikakpage.com and
#     ports 2083/2087 are blocked from here, so requests go to https://erikakpage.com
#     with "Host: cpanel.erikakpage.com" (SNI stays on the main domain).
#   * The host serves an anti-bot holding page at random, always with HTTP 200. A 200
#     from an upload therefore proves nothing; success is a JSON body with "uploads"
#     and no errors, and the file is then confirmed by size from a fresh directory
#     listing.
#   * cPanel returns sizes as strings, so they are compared as strings.
set -uo pipefail

: "${CPANEL_USER:?set CPANEL_USER}"
: "${CPANEL_TOKEN:?set CPANEL_TOKEN}"
WEB="${CPANEL_WEBROOT:-/home/$CPANEL_USER/public_html}"
CA="${CA_BUNDLE:-/root/.ccr/ca-bundle.crt}"
[ -f "$CA" ] || CA=""
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

H1="Host: cpanel.erikakpage.com"
H2="Authorization: cpanel $CPANEL_USER:$CPANEL_TOKEN"
CURL=(curl -sS --max-time 180 ${CA:+--cacert "$CA"} -H "$H1" -H "$H2")
TMP="$(mktemp)"; trap 'rm -f "$TMP"' EXIT

mkd () {   # create a remote directory (and its parents, one level at a time)
  local d="$1" parent=""
  IFS='/' read -ra parts <<< "$d"
  for part in "${parts[@]}"; do
    "${CURL[@]}" --get "https://erikakpage.com/json-api/cpanel" \
      --data-urlencode "cpanel_jsonapi_user=$CPANEL_USER" \
      --data-urlencode "cpanel_jsonapi_module=Fileman" \
      --data-urlencode "cpanel_jsonapi_func=mkdir" \
      --data-urlencode "cpanel_jsonapi_apiversion=2" \
      --data-urlencode "path=$WEB${parent:+/$parent}" \
      --data-urlencode "name=$part" -o /dev/null 2>/dev/null
    parent="${parent:+$parent/}$part"
  done
}

up () {
  local f="$1" d code
  d=$(dirname "$f"); [ "$d" = "." ] && d=""
  for i in 1 2 3 4; do
    code=$("${CURL[@]}" -F "dir=$WEB${d:+/$d}" -F "file-1=@$f" -F "overwrite=1" \
      "https://erikakpage.com/execute/Fileman/upload_files" -o "$TMP" -w "%{http_code}" 2>/dev/null) || code=000
    if [ "$code" = "200" ] && grep -q '"uploads"' "$TMP" && ! grep -q '"errors":\[.' "$TMP"; then
      return 0
    fi
    sleep $((i * 3))
  done
  echo "    upload failed (http $code): $(head -c 140 "$TMP" | tr -d '\n')"
  return 1
}

remote_size () {
  local f="$1" d
  d=$(dirname "$f"); [ "$d" = "." ] && d=""
  for i in 1 2 3 4; do
    "${CURL[@]}" --get "https://erikakpage.com/execute/Fileman/list_files" \
      --data-urlencode "dir=$WEB${d:+/$d}" --data-urlencode "types=file" -o "$TMP" 2>/dev/null
    local s
    s=$(python3 -c "
import json,sys,os
try: d=json.load(open('$TMP'))
except Exception: print('ERR'); sys.exit()
for r in d.get('data') or []:
    if r.get('file')==os.path.basename('$f'): print(r.get('size')); break
else: print('ABSENT')")
    [ "$s" != "ERR" ] && { echo "$s"; return; }
    sleep 3
  done
  echo "ERR"
}

[ $# -gt 0 ] || { echo "usage: tools/deploy.sh <file> [file...]"; exit 2; }

declare -A made
fail=0
for f in "$@"; do
  [ -f "$f" ] || { echo "  missing locally: $f"; fail=$((fail + 1)); continue; }
  d=$(dirname "$f")
  if [ "$d" != "." ] && [ -z "${made[$d]:-}" ]; then mkd "$d"; made[$d]=1; fi
  if up "$f"; then
    want=$(stat -c%s "$f"); got=$(remote_size "$f")
    if [ "$got" = "$want" ]; then printf "  ok        %-64s %s\n" "$f" "$want"
    else printf "  MISMATCH  %-64s local=%s remote=%s\n" "$f" "$want" "$got"; fail=$((fail + 1)); fi
  else
    printf "  FAILED    %s\n" "$f"; fail=$((fail + 1))
  fi
done
echo "FAILURES: $fail"
exit $((fail > 0))
