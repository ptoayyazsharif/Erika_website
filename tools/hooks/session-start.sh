#!/usr/bin/env bash
# SessionStart hook: put the latest state of this repo in front of a new session.
# What it prints becomes part of the session's context. Read-only; never fails.
#
#   * which branch this is, and whether it is behind the default branch;
#   * the newest entry of SESSION-LOG.md (what the last session did, what failed);
#   * the "Open items" list from CLAUDE.md.
cd "$(dirname "${BASH_SOURCE[0]}")/../.." 2>/dev/null || exit 0
DEFAULT="$(git symbolic-ref -q --short refs/remotes/origin/HEAD 2>/dev/null | sed 's#^origin/##')"
DEFAULT="${DEFAULT:-claude/client-website-ux-ra0p3o}"
timeout 20 git fetch -q origin "$DEFAULT" 2>/dev/null
BR="$(git rev-parse --abbrev-ref HEAD 2>/dev/null)"

echo "== Erika_website: start-of-session state =="
echo "Branch: $BR   (default branch, where all work must end up: $DEFAULT)"
if git rev-parse -q --verify "origin/$DEFAULT" >/dev/null; then
  behind=$(git rev-list --count "HEAD..origin/$DEFAULT" 2>/dev/null || echo 0)
  if [ "$behind" -gt 0 ]; then
    echo "!! This branch is $behind commit(s) behind origin/$DEFAULT. Merge it first: git merge origin/$DEFAULT"
  else
    echo "Up to date with origin/$DEFAULT."
  fi
fi
echo
echo "Read CLAUDE.md (runbook) before working. Follow 'The loop' in it after every change."
if [ -f SESSION-LOG.md ]; then
  echo
  echo "-- Newest SESSION-LOG.md entry --"
  awk '/^## /{n++} n==1{print} n>1{exit}' SESSION-LOG.md
fi
if [ -f CLAUDE.md ]; then
  echo
  echo "-- Open items (CLAUDE.md) --"
  awk '/^## Open items/{f=1;next} /^## /{f=0} f' CLAUDE.md
fi
exit 0
