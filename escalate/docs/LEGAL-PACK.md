# Escalate — what a lawyer needs from you

**This is not legal advice. Nobody who wrote it is a lawyer.**

It is the *factual* half of the job: a precise description of what this software
actually does with personal data, written from the code rather than from
memory. Hand it to a solicitor and you skip the discovery they would otherwise
bill you several hours to do — usually badly, because they have to ask you and
you have to guess.

Everything in Part 1 is verifiable against the source. File references are given
so your lawyer, or a future engineer, can check rather than trust.

---

## Part 0 — The question that changes everything: where are your users?

The answer determines which body of law you are working under, and the two
answers lead to genuinely different documents.

| If | Then |
|---|---|
| **US users only** | No GDPR. You are in US state privacy law (California, Virginia, Colorado, Connecticut, Texas and others — thresholds vary and many won't apply at small scale), **COPPA** if anyone under 13 gets in, and **FTC Act §5** on deceptive practices. Georgia has no comprehensive state privacy law of its own. |
| **Any UK or EU users at all** | UK/EU GDPR applies extraterritorially. The processor contract described in Part 2 becomes *legally mandatory*, not merely sensible, and its absence is itself a breach by both parties. |

An app on the open web with no geo-blocking should assume the second. If you
intend the first, say so in the terms and actually enforce it.

**FTC §5 deserves its own line even in a US-only world.** It prohibits deceptive
statements to consumers. A privacy claim that is technically true but leaves a
reasonable person with the wrong impression is the textbook case — which is
precisely why the login screen's old wording was changed (see Part 1 §6).

---

## Part 1 — What the software does

### 1. Roles

- **Erika Page** (or her company) decides why the app exists and what it does
  with people's data. That makes her the **controller** — the party with the
  primary obligations to users.
- **The agency** builds, deploys and holds server access. That is a
  **processor** relationship.
- **Anthropic** and **ElevenLabs** are **sub-processors**.

⚠️ One nuance to raise with the lawyer: if the agency also decides *what* data is
collected and *how* it is used — rather than implementing Erika's decisions —
that can make it a **joint controller**, which carries much heavier obligations
than processor status. Which one is true depends on how you actually work
together, not on what the contract says you are.

### 2. Personal data collected

| Category | Fields | Stored |
|---|---|---|
| Identity | name, email, password hash | Plaintext (name, email) |
| Profile | preferred name, city, life context (free text, 1,200 chars), values, "anchor" | **Encrypted** |
| **Other people** | "My Circle": name, relationship, free-text note, per person | **Encrypted** |
| Desires | title, description, why it matters, non-negotiables, desired feelings, people involved | **Encrypted** |
| Generated content | AI-written readings, edits, narration audio | Encrypted (text) / files on private disk (audio) |
| Gratitude | entry bodies | **Encrypted** |
| Tags | gratitude tag labels | **Plaintext** (`gratitude_entries.tag_index`) |
| Beliefs | faith language (`none`/`universe`/`god`/`spirit`/`higher`) | **Encrypted** |
| Category metadata | desire category — includes `health`, `love`, `family`, `money` | **Plaintext** |
| Technical | session cookie, CSRF cookie, last login IP + timestamp | Plaintext |
| Consent | timestamp of disclosure acceptance and 16+ confirmation | Plaintext (`profiles.consented_at`) |

### 3. Special-category / sensitive data

Two items need explicit attention:

1. **Religious or philosophical belief** — `profiles.faith_language`. Under GDPR
   Art. 9 this is special category, and processing needs *explicit* consent
   (a higher bar than ordinary consent). It is **encrypted at rest** as of the
   latest build, but is still **transmitted to Anthropic on every story
   generation** as a derived instruction. Note that choosing "secular" is
   equally revealing. Encryption does not remove the need for a lawful basis —
   consent for this specific field is still the open item.
2. **Health-adjacent inference** — `desires.category` is plaintext and includes
   `health`. A row reading *"user 7, category health, status unfolding"* is
   inference without decrypting anything.

**Done:** `faith_language` is encrypted at rest.
**Still open:** capture its consent separately from the general one.

### 4. Data about people who never signed up

This is the item most likely to be overlooked and hardest to fix.

"My Circle" invites users to record other people's **names, relationships and a
free-text note**. Those people have not consented to anything and mostly do not
know the app exists. Their data is transmitted to Anthropic verbatim, because
the prompt instructs the model to use the names exactly as written
(`app/Services/StoryWriter.php`).

Under GDPR this engages Art. 14 (informing data subjects whose data you did not
collect from them), which has no clean answer at this scale. It needs to be a
**documented, deliberate decision** rather than an oversight. The app now warns
users about it on the privacy page; that is mitigation, not compliance.

### 5. Where the data goes

| Recipient | What it receives | Where |
|---|---|---|
| **Anthropic** | preferred name, city, life context, values, anchor, **entire My Circle**, the desire and all its fields, derived faith instruction | United States |
| **ElevenLabs** | the complete finished reading — which by design contains the names, places and numbers the user supplied | United States |
| Hosting provider | everything, at rest | Wherever the server is |

Nothing else leaves the server. There is no analytics, no advertising, no
tracking pixel, no CDN, no external fonts — verified: the Content-Security-Policy
permits `'self'` only (`app/Http/Middleware/SecurityHeaders.php`).

**Your lawyer will need, in writing:** each provider's DPA, their current data
retention terms, and the international transfer mechanism (SCCs / UK IDTA /
Data Privacy Framework, as applicable). Do not take these from memory or from a
blog post — get the current signed terms.

### 6. Security measures actually implemented

This is what goes in the "technical and organisational measures" annex. All of
it is real and testable; there are 58 automated tests covering the security
behaviour.

- **Encryption at rest** — AES-256-CBC with HMAC (Laravel `encrypted` casts) on
  every field holding user-written prose.
- **Encryption in transit** — HTTPS enforced; HSTS when served over TLS.
- **Access control** — every record is ownership-checked on every request;
  cross-account access returns 404, not 403, so existence is not confirmed.
- **Private media** — narration audio and images are never web-reachable. No
  public storage symlink exists. Files are streamed by a controller that checks
  ownership first (`app/Http/Controllers/MediaController.php`).
- **Nothing cached on the device** — no story, entry or audio is written to the
  browser's cache or to Cache Storage. Sign-out sends `Clear-Site-Data`.
- **Authentication** — bcrypt (12 rounds), 12-character minimum, throttled
  login with a lockout that cannot be reset by changing IP address, no user
  enumeration via error message or response timing.
- **Admin** — separate second password door, expires after 2 hours idle,
  invisible (404) to non-admins, grantable only from a shell.
- **CSP** — `script-src 'self'`, no inline script anywhere.
- **Rights** — users can export everything they have written, and delete their
  account and every associated file, from inside the app.

### 7. The limit of the encryption — state this plainly, do not oversell it

`APP_KEY` lives on the same server as the database, because the app must read
the words to send them for writing and narration. Therefore:

- It is **not** end-to-end encrypted.
- **Anyone with server shell access can read any journal.** That includes the
  agency, the hosting provider, and anyone who compromises the box.
- Because admin rights are granted from a shell, **the set of people who can be
  admins is exactly the set who can already read everything.** Do not describe
  admins as unable to read journals.

The in-app disclosure now says this. Any sales or marketing copy must not
contradict it — that contradiction is the FTC §5 exposure.

### 8. Retention and deletion

- **Deletion is immediate and complete**: rows, narration audio, images and
  session records (`app/Services/AccountEraser.php`).
- **The AI spend ledger survives** deliberately, with the user id nulled. It
  holds counts, costs and timestamps — **no user content**.
- **What we cannot delete**: anything the AI providers already hold, which is
  governed by their retention terms, not ours. Users are told this.
- **No automatic retention limit exists yet.** Data is kept until the user
  deletes it. If you want a dormancy policy, that is a decision to make and then
  implement.

### 9. Known gaps, stated honestly

- **No email verification.** Anyone can register with an address they do not
  own. Password reset exists, so a user can recover their own account and
  exercise their own rights without the agency touching the data.
- **Registration reveals whether an email already has an account.** On this kind
  of app that is itself a privacy harm: it lets anyone test whether a specific
  person uses a manifestation journal.
- **No audit log of admin activity.** Nobody would know if an admin read
  something. Breach notification would be guesswork.
- **Gratitude tag labels are plaintext.** They are user-written free text; "chemo"
  or "divorce" are entirely plausible tags.

---

## Part 2 — The processor agreement: what it must contain

If GDPR applies, Art. 28(3) requires these in writing. Even if it does not, this
is the document that allocates liability between the agency and Erika — which is
the thing you actually want.

**Nothing here is optional if GDPR applies.**

1. **Subject matter, duration, nature and purpose** of the processing — Part 1
   §2 gives you this.
2. **Types of personal data and categories of data subject** — Part 1 §2 and §4.
   Do not omit the non-users in My Circle.
3. **Process only on documented instructions** from the controller, including on
   international transfers.
4. **Confidentiality** — everyone with access is bound by it.
5. **Security measures** under Art. 32 — Part 1 §6 is your annex.
6. **Sub-processors** — Anthropic and ElevenLabs named and authorised, with a
   process for notifying changes.
7. **Assist with data subject rights** — access, deletion, portability. The app
   already implements export and deletion, which makes this cheap to promise.
8. **Assist with breach notification, DPIAs and regulator consultation** — and
   say *who* notifies within the 72-hour window.
9. **Delete or return all data** at the end of the engagement, and **certify it**.
10. **Allow audits** and provide information demonstrating compliance.

**Add these, which are not statutory but are what actually protects the agency:**

- A dated record of **when the agency's server access ends** — or an explicit
  statement that it continues, and why.
- **Handover of `APP_KEY` and credentials**, with a record of who holds them.
- **A liability cap**, and clarity on which party carries which risk.
- **A signed acknowledgement from Erika** of the "known gaps" list in Part 1 §9
  if she chooses to launch before they are closed. This is the single most
  valuable paragraph in the document for you.

> ⚠️ On "we don't want to be held responsible": under GDPR you cannot get there
> by staying quiet. Art. 28 *requires* the written contract — its absence is a
> breach by **both** parties — and Art. 82(2) makes a processor **directly
> liable** where it breached processor obligations or acted outside the
> controller's instructions. Operating with no contract is the position of
> maximum exposure. The contract is the thing that limits you.

---

## Part 3 — Terms of service: what this app specifically needs

Beyond the standard boilerplate, these clauses exist because of what Escalate
*is*:

- **AI output disclaimer.** Readings are generated. They can be wrong, odd, or
  land badly. They are not predictions and not statements of fact.
- **Not therapy, not advice.** Not medical, psychological, financial or legal
  advice. This matters more here than in most apps: the prompt deliberately asks
  the model to reason about the user's *fear and scarcity behaviours*, and users
  write about grief, money, health and family.
- **Crisis signposting.** A line directing anyone in distress to a doctor or a
  crisis line, not to the app.
- **Minimum age**, matching what registration enforces (currently 16+).
- **Content ownership** — the user owns what they write; you need a narrow
  licence to process it in order to provide the service, and no broader.
- **Acceptable use** — including not writing about other people in ways that
  would harm them.
- **Availability** — no uptime promise, generation may fail, third-party
  providers may be unavailable.
- **Termination** — yours and theirs; what happens to data on each.
- **Limitation of liability and warranty disclaimer.**
- **Governing law and venue** — Georgia, or wherever the contracting entity sits.
- **How changes to the terms are notified.**

If you ever charge for this, add payment, refunds and auto-renewal terms —
US auto-renewal disclosure rules are strict and several states enforce them
aggressively.

---

## Part 4 — Decisions only you and Erika can make

A lawyer will ask these first. Have answers ready; it is the difference between
one billed session and three.

1. **Which legal entity is the controller?** Erika personally, or a company?
2. **Which countries will you accept users from?** (Part 0.)
3. **How long after the build does the agency keep server access?** Indefinitely,
   for a support period, or not at all?
4. **Who is on call for a breach**, and who notifies whom, within 72 hours?
5. **Is there a retention limit**, or is data kept until the user deletes it?
6. **Do you want email verification?** Password reset now exists; verification
   at signup is the remaining question, and it also closes the "anyone can
   register with someone else's address" gap.
7. **Do you want minors under 16 blocked, or supported with parental consent?**
   Supporting them is substantially more work.
8. **Who pays the AI bills**, and does that change who the controller is?

---

## Realistic shape of the work

| Item | Who | Rough effort |
|---|---|---|
| This factual pack | Done | — |
| Processor agreement | Solicitor, from a standard template + Part 1 as annex | 1–2 hours if you bring this |
| Privacy policy (public) | Solicitor reviewing the in-app disclosure already written | 1 hour |
| Terms of service | Solicitor, from template + Part 3 | 1–2 hours |
| Provider DPAs | You — sign Anthropic's and ElevenLabs' commercial terms | An afternoon |
| Erika's sign-off on §9 | You | One email, and keep it |

Find someone who has done **consumer app / SaaS privacy** work, not a general
commercial solicitor. If any users are UK/EU, they need GDPR experience
specifically.

---

## What is already done, so you don't pay twice for it

- In-app disclosure at `/privacy`, naming both AI providers and stating plainly
  that the operator holds the key.
- Consent and 16+ confirmation captured at registration, **timestamped** so it
  can be evidenced rather than asserted.
- Data export and full account deletion, both working and tested.
- The security measures annex (Part 1 §6), all of it verifiable and covered by
  automated tests.
- An honest list of the gaps (Part 1 §9) — which is what Erika signs off on.
