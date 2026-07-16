# ErikaKPage.com — Lite CMS

The full website for Erika Page (Atlanta Metro Real Estate Expert) with a very light,
WordPress-style admin: log in, edit any text with a WYSIWYG editor, replace any photo
or video, save. Plain PHP + MySQL — no framework, no plugins, no build step.

## What's in the box

| File | Purpose |
|---|---|
| `index.php` | The public website (all 23 pages in one file, same design as the static mockup) |
| `admin.php` | Admin dashboard: pages → sections → fields, WYSIWYG, image/video upload |
| `setup.php` | One-time installer (creates tables + the admin account, then locks itself) |
| `cms.php` | Core helpers (~200 lines: DB, content, auth, CSRF, uploads, sanitizing) |
| `fields.php` | The widget manifest: every editable field with its original content as default |
| `config.php` | MySQL credentials (edit on your host) |
| `assets/quill/` | Self-hosted WYSIWYG editor (no CDN, no external calls) |
| `uploads/` | Uploaded images/videos (PHP execution blocked via .htaccess) |

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
php -S localhost:8000
# then open /setup.php once, and /admin.php to edit
```

`config.local.php` can also set `'driver' => 'sqlite'` for a zero-setup local run.

## Branches

- `claude/lite-cms` — this CMS version
- `claude/client-website-ux-ra0p3o` — the original static single-file mockup (`index.html`)
