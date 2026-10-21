#!/usr/bin/env bash
# End-to-end check of landing pages and leads, against the LOCAL site only.
#
#   QA_USER=<local admin> QA_PASS=<password> tools/check-landing/run.sh
#
# What it does, in a real browser (Playwright + Chromium):
#   e2e1  the admin list and editor; publishing without a file is refused; a picture
#         and a PDF are uploaded and the Mariners page published; the public page,
#         server-side validation, the thank-you page and a byte-identical download;
#         no sideways scroll on a phone.
#   e2e2  Leads: list, detail, CSV; a Lofty failure flagged and retried; tampered and
#         forged links refused; uploads/private refused; a new page built with the
#         question builder (reorder, an empty dropdown refused, typed work kept);
#         a link page; unpublish, duplicate, delete; script in a question not run.
#   e2e3  the Stripe paywall: the setup screen, a paid page from the editor, a sale end
#         to end, cancel, a replayed return link, the closed-tab case finished by the
#         webhook (once), bad and replayed signatures, forged return links, a monthly
#         subscription and its cancellation, and paid pages hiding when keys go.
#
# Emails go to a local SMTP catcher (port 8025), Lofty to a fake endpoint (8799) and
# Stripe to a fake API (8798, stripe_mock.py), so nothing leaves the machine. The local SQLite database is backed up first and
# put back afterwards, along with the Email and Lofty settings it had.
# Needs: the local server (php -S 127.0.0.1:8000 router.php), ffmpeg, network for
# one pip install. Never point it at the live site.
set -euo pipefail
SITE="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
HERE="$SITE/tools/check-landing"
: "${QA_USER:?set QA_USER (a local admin user)}"; : "${QA_PASS:?set QA_PASS}"
grep -q "sqlite" "$SITE/config.local.php" 2>/dev/null || { echo "config.local.php must use the sqlite driver: this test rewrites the local database"; exit 2; }
curl -s -o /dev/null http://127.0.0.1:8000/ || { echo "start the local server first: php -S 127.0.0.1:8000 router.php"; exit 2; }

QA_DIR="$(mktemp -d)"
export QA_DIR QA_USER QA_PASS SITE
cp "$SITE/cms.sqlite" "$QA_DIR/cms.sqlite.bak"
ls "$SITE/uploads/private" > "$QA_DIR/private-before.txt" 2>/dev/null || true
ls "$SITE/uploads" > "$QA_DIR/uploads-before.txt" 2>/dev/null || true
PIDS=()
cleanup() {
  for p in "${PIDS[@]:-}"; do [ -n "$p" ] && kill "$p" 2>/dev/null || true; done
  cp "$QA_DIR/cms.sqlite.bak" "$SITE/cms.sqlite"
  for f in "$SITE"/uploads/private/*; do
    [ -e "$f" ] || continue
    b="$(basename "$f")"; grep -qx "$b" "$QA_DIR/private-before.txt" || rm -f "$f"
  done
  # the test pictures, and the resized copies handle_upload() made of them
  for f in "$SITE"/uploads/*.*; do
    [ -f "$f" ] || continue
    b="$(basename "$f")"; grep -qx "$b" "$QA_DIR/uploads-before.txt" && continue
    rm -f "$f" "$SITE"/assets/rimg/*/uploads/"$b".webp
  done
  rmdir "$SITE"/assets/rimg/*/uploads 2>/dev/null || true
  echo "(local database, uploads and uploads/private restored)"
}
trap cleanup EXIT

python3 -m venv "$QA_DIR/venv" >/dev/null && "$QA_DIR/venv/bin/pip" -q install aiosmtpd >/dev/null 2>&1
"$QA_DIR/venv/bin/python" -I "$HERE/smtp_sink.py" "$QA_DIR/mails.jsonl" > "$QA_DIR/smtp.log" 2>&1 & PIDS+=($!)
python3 -I "$HERE/lofty_mock.py" "$QA_DIR/lofty.jsonl" > "$QA_DIR/lofty.log" 2>&1 & PIDS+=($!)
python3 -I "$HERE/stripe_mock.py" "$QA_DIR/stripe.jsonl" > "$QA_DIR/stripe.log" 2>&1 & PIDS+=($!)
sleep 1.5

php -r '
require getenv("SITE") . "/cms.php";
foreach (["smtp.host"=>"127.0.0.1","smtp.port"=>"8025","smtp.secure"=>"none","smtp.user"=>"","smtp.to"=>"owner@example.test",
          "smtp.from"=>"site@example.test","lofty.enabled"=>"1","lofty.key"=>"qa-key","lofty.endpoint"=>"http://127.0.0.1:8799/v1.0/leads",
          "landing.pages"=>"","landing.removed"=>"","stripe.secret"=>"","stripe.whsec"=>"",
          "stripe.api_base"=>"http://127.0.0.1:8798","stripe.last_event"=>""] as $k=>$v) set_setting($k,$v);
content_set("global.site-url", "http://127.0.0.1:8000");   // Stripe sends the buyer back to the site address
foreach (leads_list([], 10000) as $l) lead_delete((int) $l["id"]);'

ffmpeg -v error -y -f lavfi -i "testsrc2=size=1920x1080" -frames:v 1 -q:v 3 "$QA_DIR/house.jpg"
cp "$SITE/assets/guides/closing-costs-101.pdf" "$QA_DIR/1039 Mariners Merged.pdf"
mkdir -p "$QA_DIR/shots"

export NODE_PATH=/opt/node22/lib/node_modules
rc=0
node "$HERE/e2e1.mjs" || rc=1
node "$HERE/e2e2.mjs" || rc=1
node "$HERE/e2e3.mjs" || rc=1
echo "--- emails caught: $(wc -l < "$QA_DIR/mails.jsonl")   Lofty calls: $(wc -l < "$QA_DIR/lofty.jsonl")   screenshots: $QA_DIR/shots"
exit $rc
