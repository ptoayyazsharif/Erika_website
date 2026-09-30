# ErikaKPage.com — Lite CMS

The full website for Erika Page (Atlanta Metro Real Estate Expert) with a very light,
WordPress-style admin: log in, edit any text with a WYSIWYG editor, replace any photo
or video, save. Plain PHP + MySQL — no framework, no plugins, no build step.

## What's in the box

| File | Purpose |
|---|---|
| `index.php` | The public website (every page in one file, same design as the static mockup; an article is the one page rendered on demand) |
| `admin.php` | Admin dashboard: pages → sections → fields, WYSIWYG, image/video upload |
| `setup.php` | One-time installer (creates tables + the admin account, then locks itself) |
| `cms.php` | Core helpers (~200 lines: DB, content, auth, CSRF, uploads, sanitizing) |
| `fields.php` | The widget manifest: every editable field with its original content as default |
| `config.php` | MySQL credentials (edit on your host) |
| `assets/quill/` | Self-hosted WYSIWYG editor (no CDN, no external calls) |
| `lib/phpmailer/` | Self-hosted PHPMailer (SMTP sending for forms) |
| `photos.php` | Curated photo library: which shots a slot can use, and which one it ships with |
| `assets/photos/` | The photo files themselves, grouped by kind of slot (`01` hero, `03` stage, …) |
| `routes.php` | Clean-URL ↔ page map (real per-page URLs), plus the `/blog/<slug>` matcher |
| `posts.php` | Blog articles that ship with the site (the admin's copy overrides them) |
| `products.php` | Digital products that ship with the site (same arrangement as `posts.php`) |
| `sitemap.php` | The XML sitemap, served at `/sitemap.xml`, built from the routes and the posts |
| `robots.txt` | Crawler rules and the sitemap address |
| `assets/audio/` | Article narrations (MP3) |
| `assets/guides/` | The finished PDFs |
| `tools/guides/` | The PDF sources: one HTML file per document, plus the shared `guide.css` |
| `tools/build-guide.mjs` | Renders and verifies those PDFs |
| `router.php` | Front controller for `php -S` only, so local development matches the live rewrite |
| `submit.php` | Public form handler (emails submissions to admin) |
| `.htaccess` / `.user.ini` | Clean-URL rewriting + raised upload limits |
| `uploads/` | Uploaded images/videos (PHP execution blocked via .htaccess) |

## Real page URLs & navigation

Every page has its own shareable URL (`/`, `/sell`, `/home-value`, `/property-management`,
`/escaluxe-living`, …) served by the `.htaccess` front controller, and the browser
back/forward buttons work correctly. Home is always the root `/`. The `.htaccess`
rewrite is required — on a host without mod_rewrite, ask support to enable it.

## The blog

`/blog` lists the articles; each one lives at `/blog/<slug>`. Posts are the second
thing on the site with no fixed number of slots (the gallery was the first), so they
are stored the same way: one JSON value in the `content` table, edited on its own
admin screen — **Blog posts** — rather than as manifest fields.

The one addition is that a post can also ship in a file. `posts.php` is the default
list exactly as `fields.php` is the default for page copy, which means an article can
be published by uploading files with no database write at all. The two are merged by
slug in `blog_posts()`: the admin's copy of a post wins, a post that exists only in
the file is appended, and a post removed in the admin is remembered in `blog.removed`
so it stays removed.

Three things are worth knowing before editing the code:

- **An article is rendered only when its URL is asked for.** Every other page is
  inlined into the one document; articles are not, or every visitor would download
  every article ever written. That is also why links from the blog list are plain
  `<a href>` with no `onclick`: they are meant to be a full page load.
- **Article bodies never go through Quill.** `unwrap_quill()` flattens `<p>` blocks
  into `<br><br>` on save, which would destroy an article the first time anyone
  opened it. The body is a plain textarea, sanitized with `strip_bad()` only.
- **Body pictures use a token, not an `<img>` tag.** Writing
  `[[img:assets/photos/17/name.jpg|alt text|credit]]` gets the file the same
  responsive `srcset`, lazy loading and intrinsic sizing as every other picture on
  the site; a hand-written `<img>` would get none of it.

**Downloadable guides.** A post can carry a `guide` — a PDF offered after the
reader fills in the form under the article, so the lead still reaches the inbox
and Lofty. It should also carry `product`, the slug of the matching digital
product, and that one field is what joins the two records: the offer box above
the article takes its cover, name, description and page count from the product,
and the product's own page shows a card linking back to the article. Everything
that box displays used to be literal text naming Closing Costs 101, so the second
article to offer a guide would have advertised the first one's. One field means
the two sides cannot disagree; leave it empty and the article still offers the
PDF, just without a cover or a page count, and never under another guide's name.

The PDF itself is built from HTML rather than hand-made, which keeps it editable
and consistent with the site:

```
node tools/build-guide.mjs            # tools/guides/<name>.html -> assets/guides/<name>.pdf
```

Every document is one HTML file in `tools/guides/`, and they all share
`tools/guides/guide.css` — so the page furniture, type scale and worksheet rules
are defined once and a new guide starts as content only. Each `<div class="pg">`
carries a `data-must` attribute naming a phrase that has to survive into that
page of the finished PDF; the build reads those out of the document rather than
holding a list of its own, so a new guide brings its own content checks with it.

Two things about that build are worth knowing before touching it. Chromium's
print path only sees fonts through **fontconfig** — web fonts that load fine on
screen are silently dropped from the PDF and replaced with a system serif. And
the brand fonts ship from Google as *variable* fonts, which collapse to a single
instance once installed, so weights 400 and 700 come out identical. The script
therefore cuts real static weight files out of the variable originals with
fontTools and instals those. It also refuses to write the PDF unless every check
passes: correct page geometry, no system-font fallback (checked per character,
because grepping the file for a font name gives a false positive), no clipped
content, and each page's own `data-must` text.

Each article carries its own `<title>`, meta description, canonical link, Open Graph
and Twitter tags, and `BlogPosting` structured data — including an `AudioObject` for
the narration, so the audio version is machine-readable. `sitemap.php` picks up new
posts automatically.

## Digital products

`/digital-products` lists the guides; each one has its own page at
`/digital-products/<slug>` and a short link (`/hidden-value`, `/landlord-guide`,
`/closing-costs-guide`) for saying out loud in a video. They are stored exactly like
posts: one JSON value in `content`, one admin screen — **Digital products** — and
`products.php` as the shipped default, merged by slug with the admin's copy winning.

Two rules are enforced in code rather than left to whoever edits the page next:

- **Nothing is advertised unless it exists.** `products_all()` drops any product whose
  `published` flag is off *or* whose `file` is empty, so a half-written entry cannot go
  live behind a dead button. The unwritten products sit in `products.php` as drafts,
  visible in the admin and nowhere else, each with a note on what it still needs.
- **No prices.** Erika's material states a price for nothing, so a price here would be
  invented. The guides are free in exchange for an email.

The download is gated the same way the article's guide is: the form posts to
`submit.php`, the lead reaches the inbox and Lofty, and the download link replaces the
form on return. A product with no file shows no form and no link at all.

The category chips are generated from `PRODUCT_CATS` and only appear for categories
that actually have a published product, so a chip can never lead to an empty grid.

## Pictures: the photo library

65 picture slots ship filled with photos from Erika's collection — the hero headshot, the
no-background cut-out, stage and keynote shots, coaching, sold signs, client moments, media
stills, Atlanta, transportation and lifestyle. The files live in `assets/photos/` and the
picks are declared in `photos.php`; the chosen shot for each slot is that field's default in
`fields.php`, so a fresh install renders the complete site with no database rows at all.

Each of those slots shows a **photo library** strip in the admin: the alternates for that
kind of slot (A1, A2, A3…), where A1 is the recommended shot. Click a different one and hit
Save to swap it — no upload needed. Uploading your own picture still wins over the library,
and "remove" still returns the slot to its designed placeholder. Swapping a picture clears
the saved crop, since the old crop was framed for the old photo.

Slots with no suitable photo in the collection (property interiors and exteriors, guide
covers, product mock-ups, location shots) stay as placeholders with the plain upload box —
`photos.php` simply doesn't list them.

Every page of the site lives in one document, so pictures load lazily: the browser only
fetches the pages a visitor actually opens. The landing-page hero is the one eager image.

## Pictures: replace, reposition & zoom (photo or video)

Each image slot in the admin has an **Adjust** panel: after uploading, drag the picture
to choose which part shows and use the zoom slider — a non-destructive focal-point crop
that also works for videos (MP4/WEBM). "Reset" recenters. Uploads accept files up to
**300 MB** (JPG/PNG/WEBP/GIF/MP4/WEBM). If large uploads are rejected, raise
`upload_max_filesize`/`post_max_size` in cPanel → MultiPHP INI Editor (the included
`.user.ini` sets these, but some hosts require the panel). Very large uploads can still
time out depending on the host.

## Forms → Lofty CRM

Website form submissions can also create leads in **Lofty** automatically (in addition
to the email). Set it up under **CRM / Lofty** in the admin:

1. In Lofty: **Settings → Integrations → API → API Key Management** → generate a key.
2. In the website admin (**CRM / Lofty**): tick **Send website leads to Lofty**, paste
   the API key, and **Save**.
3. Click **Save & send test lead**, then confirm the test lead appears in Lofty.

Each form has an editable **Source** and **Tags** (per-form table), and can be toggled
off to stay email-only. Every lead includes the person's name/email/phone plus a **note**
containing the full submission (address, timeline, message, etc.) and which form/page it
came from. Leads post directly to `https://api.lofty.com/v1.0/leads` with the header
`Authorization: token <key>` (the scheme for a user-managed API key; automatically falls
back to `Bearer` for an OAuth token) — no Zapier or middleman. The payload matches Lofty's Create Lead schema: `emails`/`phones`
arrays, `source`, `tags`, `leadTypes` (Seller/Buyer/Investor/Agent/Landlord inferred from
the form), and the full submission attached inline as the lead's note via `content` +
`isPin`. `firstName` is required by Lofty, so email-only forms fall back to the email name.
A **Recent CRM syncs** log at the bottom of the page shows what synced (including the
created lead id). The API key is stored in the database (same trust
model as the SMTP password). If Lofty is ever unreachable, the email still sends and the
visitor still sees the thank-you page — a CRM outage never loses or blocks a lead.

## Forms → email (SMTP)

All website forms (seller, home value, speaking, mentorship, contact, transportation,
etc.) email their submissions to one inbox. Set it up under **Email / Forms** in the
admin: the recipient address plus your SMTP host/port/security/username/password and a
"from" address. Use **Save & send test** to confirm delivery. Each email's subject and
body identify which form and page it came from, and Reply-To is set to the submitter so
Erika can reply directly. Spam is filtered with a hidden honeypot field and a timing
check. The SMTP password is stored in the database (required to authenticate); keep the
admin account secure.

**907 editable fields** across 24 page groups — headlines, paragraphs, testimonials,
FAQs, cards, buttons, badges, ticker items, captions, SEO title/description, and all
97 photo/video slots. Content lives in one `content` table; anything never edited
falls back to the built-in default, so an empty database renders the complete site.

## Install on shared hosting (cPanel)

1. Upload all files to `public_html` (or a subfolder).
2. cPanel → **MySQL Databases**: create a database and a user, grant the user
   ALL PRIVILEGES on that database.
3. Edit `config.php` — fill in the database name, user, and password.
4. Visit `https://yourdomain.com/setup.php` — create the admin username and password.
5. Log in at `https://yourdomain.com/admin.php` and start editing.

That's the whole install. Backups = export the database + copy the `uploads/` folder.

## Using the admin

- Left sidebar lists every page (plus **Global** for the phone number, footer, and SEO).
- Each page is split into collapsible **sections** matching the sections on the site.
- Short texts are plain inputs; paragraphs get a **WYSIWYG editor** (bold, italic,
  underline, links); every photo slot shows a thumbnail with an **upload** button
  (JPG/PNG/WEBP/GIF, or MP4/WEBM for video slots, max 8 MB).
- "Remove" on a picture returns the slot to its designed placeholder.
- One **Save** button per page saves all its sections at once.
- **Blog posts** and **Digital products** are their own screens, below the page list —
  they have no fixed number of slots, so they are lists rather than manifest fields.
- **Settings** → change password.

## Security (kept simple, done properly)

Hashed passwords (`password_hash`), session cookies (httponly/samesite), CSRF tokens on
every form, prepared statements everywhere, upload whitelist by extension **and** MIME,
random filenames, no PHP execution in `/uploads`, WYSIWYG HTML sanitized on save
(scripts, event handlers, and `javascript:` URLs stripped), 1-second delay on failed
logins, installer self-locks after first run.

## Local development

```bash
# with a local MySQL:
cp config.php config.local.php   # edit with local credentials (git-ignored)
php -S localhost:8000 router.php
# then open /setup.php once, and /admin.php to edit
```

Pass `router.php` to `php -S` or the clean URLs 404 — the built-in server has no
mod_rewrite, and `router.php` is the same one-line front controller `.htaccess`
provides on the host.

`config.local.php` can also set `'driver' => 'sqlite'` for a zero-setup local run.

## Branches

- `claude/lite-cms` — this CMS version
- `claude/client-website-ux-ra0p3o` — the original static single-file mockup (`index.html`)
