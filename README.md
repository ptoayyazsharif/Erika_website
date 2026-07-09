# ErikaKPage.com — Website Mockup

Design mockup for Erika Page, Atlanta Metro Real Estate Expert, Speaker & Mentor.

## What's here

- `index.html` — the complete single-file site mockup (v4 Refined Edition). All pages (Home, About, Sell, Home Value, Buy, Speaking, Collaborations, Lifestyle, Media, Reviews, Resources, Digital Products, Erika Explains, Mentorship, Investing, Property Management, location pages, Gallery, Contact) live in this one file with a lightweight client-side router. Photos and content are placeholders.

## How to preview

Open `index.html` in any browser — no build step or server required.

## v5 — July 7 meeting revisions

- Homepage reordered: intro → moving ticker tape (with extra factoids) → selling → booking Erika → personal welcome video → ecosystem → insights → thumbnail gallery → testimonials → FAQ → final sell CTA
- Ticker tape replaces the static stat band (pauses on hover, static under reduced motion)
- Third hero button "Everything Else" scrolls to the Erika Page Ecosystem section
- Ecosystem gains a "Brands & Partners" card (links to Collaborations)
- Full-body no-background photo placeholder in the "Book Erika" section
- Barbara Corcoran-style thumbnail gallery strip on the homepage, linking to the full Gallery
- Sell page: "Book a Seller Strategy Call" form moved above the fold into the hero
- Every `.ph` placeholder box (hero boxes included, on every page) now accepts a real `<img>` or `<video>` drop-in — see the comment next to the homepage headshot box
- "Reviews" renamed to "Testimonials" site-wide; testimonials page relabeled "Google · Zillow · Video Testimonials"
- Buy, Speaking, Lifestyle & Media, and Resources pages unchanged per meeting

## Design notes (v4 Refined Edition)

Built on the v3 Blush brand (Fraunces + Archivo, cream / blush / merlot / gold) and refined using UI/UX best practices for luxury real estate personal brands:

- Glassmorphism sticky nav with scroll shadow, keyboard-accessible dropdowns
- Animated stat counters, scroll-reveal sections, hero trust badges
- SVG icons throughout (stars, burger, checkmarks, back-to-top) — no text glyphs
- WCAG-minded contrast (darkened gold/merlot tones for small text), visible focus states, skip link, `<main>` landmark, ARIA labels
- `prefers-reduced-motion` respected for all animation
- Hash-based deep links (`#/sell`, `#/homevalue`, …) so every page is shareable
- Responsive: opaque mobile menu, full-width CTAs on small screens
