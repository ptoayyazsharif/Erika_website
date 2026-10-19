# BRIEF — "Ask Erika" Coaching Knowledge Base

Private web app for Erika Page (Atlanta real estate agent who mentors newer agents).
She uploads coaching-call transcripts; the system extracts her advice into standalone
Q&A cards; she reviews/approves them; mentees then ask questions and get answers in her
own words, with citations. Two roles: **owner** (Erika) and **mentee**.

## Hard environment constraints (confirmed — do not assume otherwise)
- GoDaddy shared hosting, cPanel. **No SSH, no Composer.**
- PHP **8.3.31**, `max_execution_time = 600`.
- **MySQL** (not Postgres — no pgvector).
- cURL enabled; outbound HTTPS to Google works.
- No Node, no build step, no framework, no package manager.
- **Vanilla PHP + PDO only.** Deploy by uploading files via FTP.
- cPanel cron available.

## Models (all confirmed on the key)
```
distill => ['gemini-3.5-flash',      'gemini-flash-latest']
router  => ['gemini-3.5-flash-lite', 'gemini-flash-lite-latest']
answer  => ['gemini-3.5-flash-lite', 'gemini-flash-lite-latest']
```
Endpoint: `https://generativelanguage.googleapis.com/v1beta/models/{MODEL}:generateContent?key={KEY}`

## Gemini 3.x rules (critical)
- Do NOT set `temperature`, `top_p`, or `top_k` — 3.x is tuned for defaults.
- Use `thinking_level` (not `thinking_budget`). Low for the router call.
- Force structured output with `responseMimeType: application/json` + `responseSchema`.
- Try primary model, fall back to the `-latest` alias on 404, **log** when fallback fires.

## Pipeline
paste transcript → **scrub (deterministic, BEFORE any AI call)** → chunk (~6000 tokens)
→ loop chunks server-side in ONE request (600s) → distill each chunk → items saved
`status='pending'` → Erika approves/edits/rejects → approved items become searchable.
Mentee queries only ever touch `status='approved'` items.

## Privacy — four layers, all required
1. **Name dictionary** (`name_map`, `str_ireplace`) before anything else. Erika seeds it.
2. **Regex** pass for phones, emails, street addresses (keep city/neighborhood).
3. Distillation prompt redacts + flags `sensitive`.
4. Erika's approval — nothing queryable until she reads it.

**Structural guarantee:** `transcripts` and `chunks` must NEVER be reachable from any
mentee-facing code path (`ask.php`, `router.php`, `answer.php`). Enforced + tested (tests 11, 12).

## Prompts
Live in code: distillation prompt in `lib/distill.php` (`DISTILL_PROMPT` + schema),
router prompt in `lib/router.php`, answer prompt in `lib/answer.php`. Keep them verbatim
to the brief. Router `thinking_level` = low. Answer refusal string is EXACT:
> "Erika hasn't covered this on a call yet. Bring it to her directly — and it'll probably end up in here after she does."

Fixed 12 topics: prospecting, objection-handling, listing-presentation, buyer-consultation,
negotiation, pricing, marketing, follow-up, contracts, mindset, time-management, business-building.

## Router (no embeddings)
Send every approved item's `id|question` as an index (~5k tokens at 300 items). Cache the
index string in a file, rebuild on approve/reject. Schema `{"ids":[...]}`, thinking_level low.

## Caching
Normalize question (lowercase, trim, strip punctuation/extra whitespace) → SHA-256 →
`answers_cache`. Hit = return stored, `hit_count++`, log `queries.from_cache=1`, zero API
calls. Miss = router + answer, then store. Invalidate (TRUNCATE) on any approve/reject.

## Pages (`/public_html/hub`)
index (login), ask (mentee), upload (owner), process (owner, streamed progress), review
(owner approve/edit/reject/bulk), names (owner name_map CRUD), gaps (owner unanswered
report), export (owner Markdown), logout.

## Out of scope for v1
File uploads (paste only), embeddings/vector search, dedup, conversation memory, queue
workers, password reset (owner creates mentee accounts), any JS framework.

## Security
PDO prepared statements; `htmlspecialchars()` on every output; CSRF token on every POST;
`password_hash`/`password_verify`; `require_owner()` on upload/process/review/names/gaps/export;
API key from config only (never client-visible, never logged); rate limit 30 questions/user/day.

## Build order
schema → gemini wrapper → scrub → chunk → distill → **[checkpoint: show distillation output
on synthetic transcript]** → review UI → router → answer → cache → gaps → auth hardening.

## Tests (14) — 11 (auth guards) and 12 (transcripts/chunks isolation) are the important ones.
1 models 200 · 2 fallback+log · 3 scrub · 4 chunk · 5 schema JSON + self-contained ·
6 topics from list · 7 router semantic · 8 router empty on unrelated · 9 exact refusal ·
10 cache zero-call + hit_count · 11 mentee blocked from owner pages · 12 isolation grep ·
13 CSRF rejected · 14 rate limit at 31.

## Deliverables
`sql/schema.sql`, `config.sample.php`, `README.md` (cPanel steps), `seed_owner.php`
(one-time), all tests runnable from CLI/browser.

## Known fix applied
`SENSITIVE` is a MySQL reserved word — the `items.sensitive` column is backticked in
`sql/schema.sql` and in every query that references it.
