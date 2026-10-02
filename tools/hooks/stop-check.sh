#!/usr/bin/env bash
# Stop hook: before a turn ends, check the work is recorded and shared.
#
# Blocks ending the turn — once — when any of these hold, and says which:
#   * uncommitted changes in the working tree;
#   * commits not pushed;
#   * this branch not contained in the default branch (all work ends up there);
#   * commits since SESSION-LOG.md last changed that touch more than the notes,
#     i.e. work happened and the log wasn't updated.
#
# Read-only, git only. If the hook already blocked this stop (stop_hook_active),
# it lets the turn end, so it can never loop — e.g. when a question to the owner
# has to wait with work half done.
input="$(cat)"
case "$input" in *'"stop_hook_active":true'*|*'"stop_hook_active": true'*) exit 0 ;; esac
cd "$(dirname "${BASH_SOURCE[0]}")/../.." 2>/dev/null || exit 0
git rev-parse --git-dir >/dev/null 2>&1 || exit 0

DEFAULT="$(git symbolic-ref -q --short refs/remotes/origin/HEAD 2>/dev/null | sed 's#^origin/##')"
DEFAULT="${DEFAULT:-claude/client-website-ux-ra0p3o}"
problems=()

[ -n "$(git status --porcelain 2>/dev/null)" ] && problems+=("uncommitted changes (git status) — commit them")

if git rev-parse -q --verify '@{u}' >/dev/null 2>&1; then
  n=$(git rev-list --count '@{u}..HEAD' 2>/dev/null || echo 0)
  [ "$n" -gt 0 ] && problems+=("$n commit(s) not pushed — git push -u origin $(git rev-parse --abbrev-ref HEAD)")
else
  problems+=("this branch has no upstream — push it")
fi

if git rev-parse -q --verify "origin/$DEFAULT" >/dev/null && ! git merge-base --is-ancestor HEAD "origin/$DEFAULT" 2>/dev/null; then
  problems+=("origin/$DEFAULT does not contain this work — merge into $DEFAULT and push it (the default branch is the single source of truth)")
fi

last=$(git log -1 --format=%H -- SESSION-LOG.md 2>/dev/null)
if [ -n "$last" ]; then
  changed=$(git log --format= --name-only "$last..HEAD" -- . ':!SESSION-LOG.md' ':!CLAUDE.md' ':!README.md' 2>/dev/null | sort -u | head -5)
  [ -n "$changed" ] && problems+=("commits since SESSION-LOG.md was last updated changed: $(echo $changed) — add a log entry (what was done, what passed, what failed and why) and fold any lesson into CLAUDE.md")
fi

if [ ${#problems[@]} -gt 0 ]; then
  {
    echo "Before finishing (see 'The loop' in CLAUDE.md):"
    for p in "${problems[@]}"; do echo "  - $p"; done
    echo "Also update ceo_erika's memory/business/erikakpage-blog.md if content was published. If you are stopping only to ask the owner something, say so and stop again."
  } >&2
  exit 2
fi
exit 0
