<?php
/**
 * The VA services design for a landing page (layout 'va'): a long page with one job,
 * the free VA Wish List, and a three-step form. Built 29 Oct 2026 after the owner
 * rejected the standard two-column page for this offer ("do some research then design it").
 *
 * What the research said, and where it went:
 *  - one outcome headline and one call to action, repeated, never competing (hero, form, close);
 *  - proof beside the promise: Erika's photo and her own line in the hero;
 *  - services shown as things the visitor recognises (a grid by area of their life);
 *  - a short, real sequence for "how it works";
 *  - long forms convert better in steps (progressive commitment): the easy taps first,
 *    contact details last; without JavaScript every step simply shows at once;
 *  - a sticky call to action on phones.
 *
 * Words: lines in quotation marks are Erika's (her "You need a VA" video, 23–24 Oct 2026,
 * and her meeting of 27 Oct 2026, ceo_erika intake/transcripts). The services, their
 * one-liners and the tools are from the team's VA services flyer (ceo_erika
 * memory/business/va-services-flyer). No prices. The flyer's brand is not named until
 * Erika says how it relates to her; NSOL BPO is never named.
 *
 * The page's title, description, questions, button and consent still come from the
 * admin (landing-pages.php / Landing pages); the sections below are this design's own.
 */

/** Small inline icons, stroked in currentColor. */
function va_icon(string $name, int $size = 22): string {
    $p = [
        'check'    => '<path d="M20 6 9 17l-5-5"/>',
        'arrow'    => '<path d="M5 12h14M13 6l6 6-6 6"/>',
        'phone'    => '<path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 1.9.7 2.8a2 2 0 0 1-.5 2.1L8 9.9a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.8.7a2 2 0 0 1 1.7 2z"/>',
        'briefcase'=> '<rect x="3" y="7" width="18" height="13" rx="2"/><path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M3 13h18"/>',
        'film'     => '<rect x="3" y="3" width="18" height="18" rx="2"/><path d="M7 3v18M17 3v18M3 8h4M3 16h4M17 8h4M17 16h4"/>',
        'headset'  => '<path d="M4 15v-3a8 8 0 0 1 16 0v3"/><rect x="2" y="14" width="5" height="7" rx="2"/><rect x="17" y="14" width="5" height="7" rx="2"/>',
        'home'     => '<path d="M3 11 12 4l9 7"/><path d="M5 10v10h14V10"/>',
        'spark'    => '<path d="M12 3v4M12 17v4M3 12h4M17 12h4M6 6l2.5 2.5M15.5 15.5 18 18M6 18l2.5-2.5M15.5 8.5 18 6"/>',
        'plus'     => '<path d="M12 5v14M5 12h14"/>',
    ][$name] ?? '';
    return '<svg class="va-ic" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $p . '</svg>';
}

/** The phone number as display text and as a tel: link. */
function va_phone(): array {
    $ph = cms('global.phone') ?: '(678) 404-1562';
    return [$ph, 'tel:' . preg_replace('/[^0-9+]/', '', $ph)];
}

/** A select or checkbox question as tappable chips (a select becomes radio chips: same name, same value). */
function va_chips(array $f, $val, ?string $err): string {
    $id = 'lp-' . $f['id'];
    $multi = $f['type'] === 'checkbox';
    $name = 'a[' . $f['id'] . ']' . ($multi ? '[]' : '');
    $picked = $multi ? (array) $val : [(string) $val];
    $star = $f['required'] ? '<span class="lp-req" aria-hidden="true">*</span>' : '';
    $hint = $multi ? '<span class="va-hint">Tick all that apply</span>' : '';
    $h = '<fieldset class="lp-field va-q' . ($err ? ' lp-bad' : '') . '" id="' . $id . '"' . ($f['required'] ? ' data-req="1"' : '') . '>'
       . '<legend class="lp-label">' . esc($f['label']) . $star . $hint . '</legend><div class="va-chips">';
    foreach ($f['options'] as $i => $o) {
        $on = in_array($o, $picked, true);
        $h .= '<label class="va-chip"><input type="' . ($multi ? 'checkbox' : 'radio') . '" name="' . $name . '" value="' . esc($o) . '"'
            . ($on ? ' checked' : '') . (!$multi && $f['required'] && $i === 0 ? ' required' : '')
            . ($err && $i === 0 ? ' aria-describedby="' . $id . '-err"' : '') . '><span>' . ($multi ? va_icon('check', 15) : '') . esc($o) . '</span></label>';
    }
    $h .= '</div>' . ($err ? '<span class="lp-msg" id="' . $id . '-err">' . esc($err) . '</span>' : '') . '</fieldset>';
    return $h;
}

/** Splits the page's questions into the form's steps: taps first, contact details last. */
function va_steps(array $page): array {
    $choice = array_values(array_filter($page['fields'], fn($f) => in_array($f['type'], ['select', 'checkbox'], true)));
    $rest   = array_values(array_filter($page['fields'], fn($f) => !in_array($f['type'], ['select', 'checkbox'], true)));
    $firstSelect = null;
    foreach ($choice as $f) if ($f['type'] === 'select') { $firstSelect = $f['id']; break; }
    $one = array_values(array_filter($choice, fn($f) => $f['type'] === 'checkbox' || $f['id'] === $firstSelect));
    $two = array_values(array_filter($choice, fn($f) => !($f['type'] === 'checkbox' || $f['id'] === $firstSelect)));
    $steps = [];
    if ($one) $steps[] = ['title' => 'What would you hand off?', 'fields' => $one];
    if ($two) $steps[] = ['title' => 'How much help?', 'fields' => $two];
    $steps[] = ['title' => 'Where should we send it?', 'fields' => $rest, 'contact' => true];
    return $steps;
}

/** The page. */
function va_page(array $page, array $vals, array $errs, bool $isDraft): void {
    [$ph, $tel] = va_phone();
    $steps = va_steps($page);
    $cover = 'assets/guides/va-wish-list-cover.webp';
    $hasCover = is_file(dirname(__DIR__) . '/' . $cover);
    ?>
<section class="va-hero">
  <div class="va-in va-hero-grid">
    <div class="va-hero-copy">
      <p class="va-kicker">Need a VA? <span><?= esc($page['brand']) ?></span></p>
      <h1 class="va-h1">Turn your <span class="va-strike">to-do</span> list into a <em>to-done</em> list.</h1>
      <p class="va-lede">CPA, medical or dental office, salon, designer, agent, or just busy? A virtual assistant takes the calendar, the follow-up, the social media and the phones off your plate. Start with the free VA Wish List: tick what you would hand off.</p>
      <div class="va-actions">
        <a class="va-btn" href="#form"><?= esc($page['cta']) ?> <?= va_icon('arrow', 18) ?></a>
        <a class="va-call" href="<?= esc($tel) ?>"><?= va_icon('phone', 17) ?> or call <b><?= esc($ph) ?></b></a>
      </div>
      <ul class="va-mini" aria-label="About the list">
        <li><?= va_icon('check', 15) ?> Free PDF</li><li><?= va_icon('check', 15) ?> Yours straight away</li><li><?= va_icon('check', 15) ?> For any busy business</li>
      </ul>
    </div>
    <div class="va-hero-art">
      <?php // A professional VA in front, not Erika (her ask, 30 Oct 2026: "this looks like I am the VA").
            // Photo: MART PRODUCTION on Pexels (pexels.com/photo/7709287), Pexels licence. ?>
      <figure class="va-photo"><img src="<?= esc(asset_url('assets/photos/va/va-at-work-1000.webp')) ?>" srcset="<?= esc(asset_url('assets/photos/va/va-at-work-560.webp')) ?> 560w, <?= esc(asset_url('assets/photos/va/va-at-work-1000.webp')) ?> 1000w" sizes="(max-width: 900px) 70vw, 400px" alt="A virtual assistant at work with a headset and laptop" width="1000" height="1500" fetchpriority="high"></figure>
      <div class="va-board" aria-label="An example day with a VA">
        <div class="va-board-top"><span>Today</span><b>To-done</b></div>
        <ol>
          <li style="--i:0"><i><?= va_icon('check', 13) ?></i>Inbox and calendar sorted</li>
          <li style="--i:1"><i><?= va_icon('check', 13) ?></i>New leads followed up</li>
          <li style="--i:2"><i><?= va_icon('check', 13) ?></i>Reports and forms done</li>
          <li style="--i:3"><i><?= va_icon('check', 13) ?></i>Reel edited and posted</li>
          <li style="--i:4"><i><?= va_icon('check', 13) ?></i>Calls answered, appointments set</li>
        </ol>
        <div class="va-board-bar"><span></span></div>
      </div>
      <blockquote class="va-bubble">&ldquo;One 10-minute conversation with my VA and you can have 50 things done.&rdquo;<cite>Erika K. Page, founder</cite></blockquote>
    </div>
  </div>
</section>

<section class="va-who">
  <div class="va-in">
    <p class="va-who-line">For <b>CPAs</b>, <b>medical and dental offices</b>, <b>salon owners</b>, <b>fashion designers</b>, <b>law offices</b>, <b>insurance and mortgage pros</b>, <b>online shops</b>, <b>real estate agents and teams</b>, and every <b>busy business owner</b>.</p>
  </div>
</section>

<section class="va-sec" id="services">
  <div class="va-in">
    <div class="va-head">
      <p class="va-eyebrow">What you can hand off</p>
      <h2 class="va-h2">Everything behind the scenes, so you can stay in front of clients.</h2>
    </div>
    <div class="va-bento">
      <article class="va-tile va-tile-big">
        <div class="va-tile-top"><?= va_icon('briefcase') ?><h3>Your business</h3></div>
        <ul class="va-list">
          <li><b>Calendar and inbox</b><span>Never miss an important date or lead.</span></li>
          <li><b>Lead follow-up</b><span>Nurturing leads so you can close more deals.</span></li>
          <li><b>Client communication</b><span>Quick replies, happy clients.</span></li>
          <li><b>Research and data entry</b><span>Research, data and records, kept up to date.</span></li>
          <li><b>Marketing support</b><span>Content, email campaigns, flyers.</span></li>
          <li><b>Admin tasks</b><span>Documents, forms, reports, invoicing.</span></li>
          <li><b>Project management</b><span>Deadlines tracked, projects kept moving.</span></li>
          <li><b>CRM and database</b><span>Your pipeline clean and updated.</span></li>
          <li><b>For real estate teams</b><span>Listing and transaction coordination, from contract to close.</span></li>
        </ul>
      </article>
      <article class="va-tile">
        <div class="va-tile-top"><?= va_icon('film') ?><h3>Your content</h3></div>
        <ul class="va-ticks">
          <li>Social media posting</li>
          <li>Reels, TikToks and YouTube Shorts</li>
          <li>Client testimonials, tips and advice</li>
          <li>Captions, transitions, music, branding</li>
        </ul>
      </article>
      <article class="va-tile">
        <div class="va-tile-top"><?= va_icon('headset') ?><h3>Your phones and office</h3></div>
        <ul class="va-ticks">
          <li>Answering calls and setting appointments</li>
          <li>Customer support by phone, email and chat</li>
          <li>Cold calling</li>
          <li>CRM, profiles, websites and automations</li>
          <li>Medical billing for medical and dental offices</li>
        </ul>
      </article>
      <article class="va-tile va-tile-dark">
        <div class="va-tile-top"><?= va_icon('spark') ?><h3>Your tools</h3></div>
        <p class="va-tile-quote">&ldquo;AI is amazing, but AI needs an AI operator.&rdquo;</p>
        <ul class="va-tools"><li>HubSpot</li><li>Salesforce</li><li>QuickBooks</li><li>Google Workspace</li><li>Microsoft 365</li><li>Asana</li><li>Shopify</li><li>ChatGPT</li></ul>
        <p class="va-tile-more"><a href="#tools">And 40 more →</a></p>
      </article>
      <article class="va-tile va-tile-rose">
        <div class="va-tile-top"><?= va_icon('home') ?><h3>Your home, too</h3></div>
        <ul class="va-ticks">
          <li>Household things</li>
          <li>Personal tasks</li>
          <li>Your day planned and kept</li>
        </ul>
      </article>
      <?php // A service since 30 Oct 2026: Erika, seeing the team's command center, "all of that needs to
            // be part of the VA services". ?>
      <article class="va-tile va-tile-wide">
        <div class="va-tile-top"><?= va_icon('spark') ?><h3>Your own command center</h3></div>
        <p>Every meeting turned into tasks, questions and dates on one private dashboard, with a switch that hides the private details when you share your screen.</p>
      </article>
    </div>
  </div>
</section>

<?php // "Why I founded this company" (Erika, 30 Oct 2026: "so there is a US person connected to it").
      // Every line in it is hers: the "You need a VA" video (23-24 Oct) and her 27 Oct meeting. ?>
<?php // The tools, grouped (the owner, 30 Oct: "add some more tools"; Erika, 30 Oct: the common ones,
      // not real estate software first). Real estate tools stay, last. ?>
<section class="va-sec va-tools-sec" id="tools">
  <div class="va-in">
    <div class="va-head">
      <p class="va-eyebrow">The tools we work in</p>
      <h2 class="va-h2">Your tools, already open on our screens.</h2>
    </div>
    <div class="va-toolgrid">
      <div class="va-toolgroup"><h3>CRM and sales</h3><ul class="va-chiplist"><li>HubSpot</li><li>Salesforce</li><li>Zoho CRM</li><li>Pipedrive</li><li>GoHighLevel</li><li>Monday CRM</li></ul></div>
      <div class="va-toolgroup"><h3>Office and files</h3><ul class="va-chiplist"><li>Google Workspace</li><li>Microsoft 365</li><li>Dropbox</li><li>DocuSign</li><li>Adobe Acrobat</li><li>Notion</li></ul></div>
      <div class="va-toolgroup"><h3>Projects</h3><ul class="va-chiplist"><li>Asana</li><li>Trello</li><li>ClickUp</li><li>Monday.com</li><li>Airtable</li></ul></div>
      <div class="va-toolgroup"><h3>Calls and meetings</h3><ul class="va-chiplist"><li>Zoom</li><li>Microsoft Teams</li><li>Slack</li><li>WhatsApp</li><li>Calendly</li><li>Acuity Scheduling</li></ul></div>
      <div class="va-toolgroup"><h3>Books and payments</h3><ul class="va-chiplist"><li>QuickBooks</li><li>Xero</li><li>FreshBooks</li><li>Stripe</li><li>PayPal</li><li>Square</li></ul></div>
      <div class="va-toolgroup"><h3>Marketing and social</h3><ul class="va-chiplist"><li>Canva</li><li>Mailchimp</li><li>Constant Contact</li><li>Meta Business Suite</li><li>Hootsuite</li><li>CapCut</li></ul></div>
      <div class="va-toolgroup"><h3>Websites and stores</h3><ul class="va-chiplist"><li>WordPress</li><li>Shopify</li><li>Wix</li><li>Squarespace</li><li>Etsy</li></ul></div>
      <div class="va-toolgroup"><h3>Customer support</h3><ul class="va-chiplist"><li>Zendesk</li><li>Freshdesk</li><li>Gorgias</li><li>Intercom</li></ul></div>
      <div class="va-toolgroup va-toolgroup-ai"><h3>AI, with an operator</h3><ul class="va-chiplist"><li>ChatGPT</li><li>Claude</li><li>Gemini</li><li>Zapier</li><li>Make</li></ul></div>
    </div>
    <p class="va-toolnote">Real estate teams too: Lofty, kvCORE, dotloop, SkySlope, Follow Up Boss and the MLS. Don&rsquo;t see yours? Ask on the call.</p>
  </div>
</section>

<section class="va-founder">
  <div class="va-in va-founder-grid">
    <figure class="va-founder-photo"><img src="<?= esc(asset_url('assets/photos/01/a7-pink-suit-cutout-520.webp')) ?>" alt="Erika K. Page" width="520" height="782" loading="lazy"></figure>
    <div>
      <p class="va-eyebrow">Why I founded this company</p>
      <blockquote>
        <p>&ldquo;I myself needed a call center, and after using a call center, I decided to create one.&rdquo;</p>
        <p>&ldquo;My amazing virtual assistant is taking my eventually list, my checklist, my to-do list, and turning it into a <em>to-done list.</em>&rdquo;</p>
        <p>&ldquo;Having a VA manage household things, personal things and business things for me is a game changer.&rdquo;</p>
        <p>&ldquo;I&rsquo;m right here in the United States. I&rsquo;m just a regular everyday business owner.&rdquo;</p>
      </blockquote>
      <p class="va-founder-name"><b>Erika K. Page</b>Founder · Metro Atlanta</p>
    </div>
  </div>
</section>

<section class="va-sec" id="how">
  <div class="va-in">
    <div class="va-head">
      <p class="va-eyebrow">How it works</p>
      <h2 class="va-h2">Three steps from list to done.</h2>
    </div>
    <ol class="va-steps">
      <li><span class="va-step-n">1</span><h3>Tick your list</h3><p>Get the free VA Wish List and tick everything you would hand off. Most people never start because they don't know what to hand over. As Erika says, &ldquo;a confused mind does nothing.&rdquo;</p></li>
      <li><span class="va-step-n">2</span><h3>Have one short conversation</h3><p>Call <a href="<?= esc($tel) ?>"><?= esc($ph) ?></a>, or we call you. We go through your list together: what to hand off first, and how many hours a week it takes.</p></li>
      <li><span class="va-step-n">3</span><h3>Watch it get done</h3><p>Your VA takes it from there. In Erika's words: &ldquo;It is cost efficient, and it is amazing to just know that things are being done.&rdquo;</p></li>
    </ol>
  </div>
</section>

<section class="va-get" id="form">
  <div class="va-in va-get-grid">
    <div class="va-get-copy">
      <p class="va-eyebrow">Free download</p>
      <h2 class="va-h2"><?= esc($page['bottom_title'] !== '' && $page['bottom_title'] !== 'VA Services' ? $page['bottom_title'] : 'Get the VA Wish List') ?></h2>
      <?php if ($hasCover): ?><figure class="va-cover"><img src="<?= esc(asset_url($cover)) ?>" alt="The cover of the VA Wish List" width="640" height="828" loading="lazy"></figure><?php endif; ?>
      <ul class="va-ticks va-inside">
        <li>Every task a VA can take off your plate, as a checklist</li>
        <li>A column for the hours each one costs you now</li>
        <li>A page to pick your first five to hand off</li>
        <li>How to start, with one short conversation</li>
      </ul>
    </div>
    <div class="va-formcard">
      <?php if (isset($_GET['expired'])): ?><div class="lp-errors">That download link has expired or isn't valid. Fill in the form again for a fresh one.</div><?php endif; ?>
      <?php if (isset($_GET['sent'])): ?><div class="lp-errors va-ok">Thank you, your request has been received.</div><?php endif; ?>
      <?php if ($errs): ?><div class="lp-errors" role="alert"><?= isset($errs['_']) ? esc($errs['_']) : 'Please check the ' . (count($errs) === 1 ? 'answer' : count($errs) . ' answers') . ' marked below.' ?></div><?php endif; ?>
      <form method="post" action="<?= esc(landing_path($page['slug'])) ?>" id="lp-form" class="va-form" novalidate>
        <div class="va-progress" aria-hidden="true"><span class="va-progress-txt">Step <b class="va-n">1</b> of <?= count($steps) ?></span><span class="va-progress-bar"><i></i></span></div>
        <?php foreach ($steps as $si => $st): ?>
        <div class="va-step" data-step="<?= $si ?>">
          <h3 class="va-step-title"><span class="va-step-of">Step <?= $si + 1 ?> of <?= count($steps) ?></span><?= esc($st['title']) ?></h3>
          <?php if (!empty($st['contact'])): ?>
            <div class="va-two">
              <?= lp_text_field('name', 'Name', 'text', $vals['name'] ?? '', $errs['name'] ?? null, 'name', true) ?>
              <?= lp_text_field('email', 'Email', 'email', $vals['email'] ?? '', $errs['email'] ?? null, 'email', true) ?>
            </div>
          <?php endif; ?>
          <?php foreach ($st['fields'] as $f) {
              $v = $vals[$f['id']] ?? ($f['type'] === 'checkbox' ? [] : '');
              echo in_array($f['type'], ['select', 'checkbox'], true) ? va_chips($f, $v, $errs[$f['id']] ?? null) : lp_question($f, $v, $errs[$f['id']] ?? null);
          } ?>
          <div class="va-nav">
            <?php if ($si > 0): ?><button type="button" class="va-back" data-go="<?= $si - 1 ?>">Back</button><?php endif; ?>
            <?php if ($si < count($steps) - 1): ?>
              <button type="button" class="va-btn va-next" data-go="<?= $si + 1 ?>">Next <?= va_icon('arrow', 18) ?></button>
            <?php else: ?>
              <button class="va-btn va-send" type="submit" id="lp-send"><?= esc($page['cta']) ?> <?= va_icon('arrow', 18) ?></button>
            <?php endif; ?>
          </div>
        </div>
        <?php endforeach; ?>
        <div class="lp-hp" aria-hidden="true"><label>Leave this empty <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
        <input type="hidden" name="_t" value="<?= time() ?>">
        <?php if ($page['consent'] !== ''): ?><p class="lp-consent"><?= esc($page['consent']) ?></p><?php endif; ?>
      </form>
    </div>
  </div>
</section>

<section class="va-sec va-faq-sec" id="questions">
  <div class="va-in va-faq-grid">
    <div class="va-head">
      <p class="va-eyebrow">Questions</p>
      <h2 class="va-h2">Before you send it.</h2>
      <p class="va-faq-call">Rather talk it through? <a href="<?= esc($tel) ?>"><?= esc($ph) ?></a></p>
    </div>
    <div class="va-faq">
      <details open><summary>What is the VA Wish List?</summary><p>A free checklist, as a PDF, of everything a virtual assistant can take off your plate: your business, your content, your phones and your home. Each task has room to note how many hours a week it costs you now, and the last page helps you pick your first five to hand off.</p></details>
      <details><summary>What happens after I send the form?</summary><p>You get the list straight away, on the next screen and by email. Then we reach out to talk through what you ticked. Can't wait? Call <?= esc($ph) ?>.</p></details>
      <?php // Her price, 30 Oct 2026: "an average of four to eight … or it can be project based … significantly less expensive than … a US-based assistant". ?>
      <details><summary>What does a VA cost?</summary><p>On average $4 to $8 an hour, or a price for the project. That is significantly less than an assistant based in the US. We go through it on the call, once we know what is on your list.</p></details>
      <details><summary>Which tools do you work in?</summary><p>The ones you already use: HubSpot, Salesforce, QuickBooks, Google Workspace, Microsoft 365, Asana, Shopify, Canva, Slack, Zoom and more. <a href="#tools">See the full list</a>.</p></details>
      <details><summary>Can you build us a command center?</summary><p>Yes. Your meetings become tasks, questions and dates on one private dashboard, with a switch that hides private details when you share your screen.</p></details>
      <details><summary>I don't know where to start.</summary><p>That is what the list is for. Tick everything you would hand off, then start with the task that costs you the most hours.</p></details>
    </div>
  </div>
</section>

<section class="va-close">
  <div class="va-in">
    <h2 class="va-h2">Your <em>to-done</em> list starts with one conversation.</h2>
    <div class="va-actions">
      <a class="va-btn va-btn-light" href="#form"><?= esc($page['cta']) ?> <?= va_icon('arrow', 18) ?></a>
      <a class="va-call" href="<?= esc($tel) ?>"><?= va_icon('phone', 17) ?> <b><?= esc($ph) ?></b></a>
    </div>
  </div>
</section>

<div class="va-sticky" id="va-sticky"><a class="va-btn" href="#form"><?= esc($page['cta']) ?></a><a class="va-sticky-call" href="<?= esc($tel) ?>" aria-label="Call <?= esc($ph) ?>"><?= va_icon('phone', 20) ?></a></div>

<script>
(function(){
  var f=document.getElementById('lp-form'); if(!f) return;
  var steps=[].slice.call(f.querySelectorAll('.va-step')), n=f.querySelector('.va-n'), bar=f.querySelector('.va-progress-bar i');
  var send=document.getElementById('lp-send'), cur=0;
  f.classList.add('va-js');
  function show(i,focus){
    cur=i; steps.forEach(function(s,k){ s.hidden = k!==i; });
    n.textContent=i+1; bar.style.width=((i+1)/steps.length*100)+'%';
    if(focus){ var t=steps[i].querySelector('.va-step-title'); t.setAttribute('tabindex','-1'); t.focus({preventScroll:true});
      var top=f.getBoundingClientRect().top+window.scrollY-90; if(window.scrollY>top) window.scrollTo({top:top,behavior:'smooth'}); }
  }
  function mark(el,msg){
    el.classList.add('lp-bad');
    var m=el.querySelector('.lp-msg'); if(!m){ m=document.createElement('span'); m.className='lp-msg'; el.appendChild(m); }
    m.textContent=msg;
  }
  function ok(i){
    var first=null;
    steps[i].querySelectorAll('.lp-field').forEach(function(el){
      el.classList.remove('lp-bad'); var m=el.querySelector('.lp-msg'); if(m) m.remove();
      if(el.dataset.req && !el.querySelector('input:checked')){ mark(el,'Please choose one.'); first=first||el.querySelector('input'); return; }
      var inp=el.querySelector('input.lp-input,textarea,select');
      if(inp && !inp.checkValidity()){ mark(el, inp.type==='email'&&inp.value ? 'Please enter a valid email address.' : 'Please fill this in.'); first=first||inp; }
    });
    if(first){ first.focus(); return false; } return true;
  }
  f.addEventListener('click',function(e){
    var b=e.target.closest('[data-go]'); if(!b) return;
    var to=+b.dataset.go; if(to>cur && !ok(cur)) return; show(to,true);
  });
  f.addEventListener('keydown',function(e){
    if(e.key==='Enter' && e.target.tagName==='INPUT' && cur<steps.length-1){ e.preventDefault(); var nx=steps[cur].querySelector('.va-next'); if(nx) nx.click(); }
  });
  f.addEventListener('submit',function(e){
    if(!ok(cur)){ e.preventDefault(); return; }
    setTimeout(function(){ send.disabled=true; send.textContent='Sending…'; },0);
  });
  // After a server-side error, open the step that holds the first one.
  var bad=f.querySelector('.lp-bad'), start=0;
  if(bad){ start=steps.indexOf(bad.closest('.va-step')); }
  show(Math.max(0,start),false);
  if(bad){ var inp=bad.querySelector('input,textarea,select'); if(inp) inp.focus({preventScroll:true}); bad.scrollIntoView({block:'center'}); }

  // The phone bar: shown once the hero's button has scrolled away, hidden while the form is on screen.
  var sticky=document.getElementById('va-sticky'), hero=document.querySelector('.va-hero .va-btn'), form=document.getElementById('form');
  if(sticky && 'IntersectionObserver' in window){
    var heroGone=false, formIn=false, upd=function(){ sticky.classList.toggle('on', heroGone && !formIn); };
    new IntersectionObserver(function(es){ heroGone=!es[0].isIntersecting; upd(); }).observe(hero);
    new IntersectionObserver(function(es){ formIn=es[0].isIntersecting; upd(); },{threshold:0.15}).observe(form);
  }
})();
</script>
<?php }

/** After the form: the download, on the page's own dark band. */
function va_thanks(array $page, callable $card): void {
    [$ph, $tel] = va_phone(); ?>
<section class="va-hero va-thanks">
  <div class="va-in">
    <?php $card(); ?>
    <ol class="va-next-steps">
      <li><span>1</span><p>Open the list and tick everything you would hand off.</p></li>
      <li><span>2</span><p>We reach out to talk it through. Or call <a href="<?= esc($tel) ?>"><?= esc($ph) ?></a>.</p></li>
      <li><span>3</span><p>Start with the task that costs you the most hours.</p></li>
    </ol>
  </div>
</section>
<?php }
