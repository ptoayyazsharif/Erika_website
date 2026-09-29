<?php
/**
 * Blog posts that ship with the site.
 *
 * The blog works the same way as every other piece of content here: this file
 * is the default, and anything saved in the admin overrides it. A post written
 * in the admin lives as JSON in the content table under 'blog.posts'; a post
 * written here needs no database at all, which is what lets an article go live
 * by uploading files.
 *
 * Both lists are merged by slug (see blog_posts() in cms.php): the admin's copy
 * of a post always wins, a post only in this file is appended, and a post
 * deleted in the admin stays deleted.
 *
 * Body copy is HTML. Allowed inline: <p> <h2> <h3> <ul> <ol> <li> <strong>
 * <em> <a> <blockquote>. For a picture use the token
 *
 *     [[img:assets/photos/17/name.jpg|alt text|credit HTML]]
 *
 * which is turned into a <figure> with the site's usual responsive images, so
 * body pictures get the same srcset ladder and intrinsic sizing as every other
 * picture on the site rather than a bare <img>.
 */

return [

    [
        'slug'           => 'closing-costs-in-georgia-explained',
        'title'          => 'Closing costs in Georgia, explained.',
        'seo_title'      => 'Closing Costs in Georgia, Explained',
        'seo_desc'       => 'Closing costs are not your down payment. What they actually cover, who pays what in Georgia, why your number differs from your neighbor\'s, and how to avoid surprises.',
        'excerpt'        => 'Closing costs are the most misunderstood number in a home purchase. Here is what they actually cover, who pays what in Georgia, and the four habits that keep the closing table calm.',
        'date'           => '2026-09-29',
        'updated'        => '2026-09-29',
        'author'         => 'Erika K. Page',
        'cat'            => 'buying',
        'tags'           => ['closing costs', 'Georgia real estate', 'first-time buyers', 'Metro Atlanta', 'title insurance'],
        'cover'          => 'assets/photos/17/a1-closing-table-signing.jpg',
        'cover_alt'      => 'Two people signing closing documents at a table',
        'cover_credit'   => 'Photo by <a href="https://www.pexels.com/@kampus" rel="nofollow noopener" target="_blank">Kampus Production</a> on <a href="https://www.pexels.com/" rel="nofollow noopener" target="_blank">Pexels</a>',
        'audio'          => 'assets/audio/closing-costs-in-georgia-explained.mp3',
        'guide'          => 'assets/guides/closing-costs-101.pdf',
        'audio_secs'     => 667,
        'published'      => true,
        'faq' => [
            [
                'q' => 'How much are closing costs in Georgia?',
                'a' => 'For buyers, usually <strong>2% to 5% of the purchase price</strong>. For sellers, closer to <strong>0.5% to 1.5%</strong> before commissions, with the commissions being the largest single piece they pay. Your loan type, your price, your lender and what you negotiated all move the number.',
            ],
            [
                'q' => 'Can the seller pay my closing costs in Georgia?',
                'a' => 'Yes &mdash; it is negotiated in the contract rather than fixed by anybody&rsquo;s rule. How much a seller can contribute is capped by your loan type, and how willing they are depends on the market you are negotiating in. It is written, never assumed.',
            ],
            [
                'q' => 'Is owner&rsquo;s title insurance worth it?',
                'a' => 'The lender&rsquo;s policy protects the lender, not you. The owner&rsquo;s policy is the one that protects you, it is paid once at closing, and it covers you for as long as you own the home. Skipping it to save a few hundred dollars is the expensive mistake.',
            ],
            [
                'q' => 'Is earnest money part of my closing costs?',
                'a' => 'No. Earnest money is your own money, held after you go under contract and then credited back to you at closing. If you hold up your end of the contract and stay inside your deadlines, it is refundable.',
            ],
        ],
        'body' => <<<'HTML'
<p>Somebody asks me this almost every week, and it is almost always two weeks before closing, in a slightly panicked voice: <em>&ldquo;Wait &mdash; what are all these fees?&rdquo;</em></p>

<p>Let me explain.</p>

<p>Closing costs are the most misunderstood number in a home purchase. Not because they are complicated &mdash; they really aren&rsquo;t &mdash; but because most people meet them for the first time on a settlement statement three days before they are supposed to sign. That is the whole problem. It&rsquo;s not the cost, it&rsquo;s the surprise, and most surprises are preventable.</p>

<p>So let&rsquo;s get them explained now, while nothing is on the line.</p>

<h2>First: closing costs are not your down payment</h2>

<p>This is the one that trips up almost everybody, so I start here every single time.</p>

<p>Your down payment is <strong>your money going into the house</strong>. It becomes your equity the moment the deed is recorded. Closing costs are <strong>the fees required to legally transfer ownership and complete the transaction</strong>. Different pile, different purpose, budgeted separately.</p>

<p>You need both. If you have saved a down payment and nothing else, you have saved for part of the purchase, not for the purchase.</p>

<h2>The four buckets your closing costs live in</h2>

<p>Every line on that statement belongs to one of four groups. Once you can sort them, the page stops being intimidating.</p>

<ul>
<li><strong>Loan-related fees.</strong> Origination, underwriting, the credit report, the appraisal, discount points if you buy your rate down. This is what it costs to have someone lend you money.</li>
<li><strong>Title and legal costs.</strong> The title search, the closing attorney, and title insurance. In Georgia a licensed attorney conducts your closing &mdash; that is not optional here, and it is not a fee you shop away.</li>
<li><strong>Government fees.</strong> Recording the deed, the Georgia transfer tax, and the intangible recording tax on your loan. Small words, real dollars.</li>
<li><strong>Prepaid items.</strong> Your first year of homeowners insurance, the property tax escrow, and interest for the days between closing and your first payment.</li>
</ul>

<p>Then, separately, there are the real estate commissions, which are set in your contract and your brokerage agreement rather than by anybody&rsquo;s fee schedule.</p>

<p>Here&rsquo;s the important part about that fourth bucket, because it is where people feel cheated and shouldn&rsquo;t: <strong>prepaids are not fees.</strong> They are funds set aside for future bills &mdash; your insurance, your taxes, your interest. Nobody is keeping that money. You were always going to pay it. It is simply being collected early so the house is never uninsured and the taxes are never late.</p>

[[img:assets/photos/17/a3-loan-estimate-paperwork.jpg|A loan application and closing paperwork on a table|Photo by <a href="https://www.pexels.com/@rdne" rel="nofollow noopener" target="_blank">RDNE Stock project</a> on <a href="https://www.pexels.com/" rel="nofollow noopener" target="_blank">Pexels</a>]]

<h2>The Georgia numbers, in plain English</h2>

<p>Two of those government line items are specific to this state, and they are worth knowing before you see them.</p>

<p>The <strong>real estate transfer tax</strong> runs about <strong>$1.00 for every $1,000</strong> of the sale price, paid to the Clerk of Superior Court when the deed is filed. On a $450,000 house that is roughly $450. Customarily it sits on the seller&rsquo;s side of the statement &mdash; and like most things here, that is customary, not compulsory.</p>

<p>The <strong>intangible recording tax</strong> is <strong>$1.50 for each $500</strong> of the loan amount, which works out to 0.3% of what you borrow, capped at $25,000 on a single note. On a $360,000 loan, about $1,080. The Georgia Department of Revenue puts the liability on the holder of the note, meaning your lender &mdash; but read your loan documents, because lenders routinely pass it straight through to you. It also only applies to long-term notes, which since July 2025 means any note with principal due more than 62 months out. Your ordinary 30-year mortgage qualifies.</p>

<p>Explanatory only, by the way. Not tax advice, not legal advice. Your closing attorney and your tax professional get the final word on your file.</p>

<h2>So who pays what?</h2>

<p>Broadly: <strong>buyers carry most of the closing costs, but sellers are not off the hook.</strong></p>

<p>Buyers typically cover the loan fees, the title costs and the prepaids &mdash; call it <strong>2% to 5% of the purchase price</strong>, depending mostly on your loan. Sellers typically cover the commissions and the transfer-related costs, which lands closer to <strong>0.5% to 1.5%</strong> before commissions, with the commissions being the largest single piece of what they pay.</p>

<p>And now the sentence I want you to actually keep: just because someone paid for something the last time you bought a house does not mean they will pay for it this time. <strong>It is different and negotiable every single time.</strong> Seller-paid closing costs, a rate buydown funded by the seller, a credit in place of a repair &mdash; all of that is written, not assumed. Which is exactly why <a href="/buy">who is representing you</a> matters.</p>

<h2>Why your number won&rsquo;t match your neighbor&rsquo;s</h2>

<p>Two buyers can go under contract on the identical house in the identical week and walk into two genuinely different sets of closing costs. That is normal. It is not a sign that one of them is being taken advantage of.</p>

<p>What moves the number: your <strong>loan type</strong> (conventional, FHA, VA and USDA all price differently), the <strong>purchase price</strong>, the <strong>location</strong>, which <strong>lender</strong> you chose, which <strong>title company and attorney</strong> are handling it, and whatever the two sides <strong>negotiated</strong>.</p>

<p>So if your numbers do not match your coworker&rsquo;s, understand that that is normal. What matters is understanding <em>your</em> numbers, and understanding them early &mdash; not comparing them to somebody else&rsquo;s deal.</p>

[[img:assets/photos/17/a2-suburban-home-for-sale.jpg|A suburban home with a for-sale sign in the front yard|Photo by <a href="https://pixabay.com/users/paulbr75-2938186/" rel="nofollow noopener" target="_blank">paulbr75</a> on <a href="https://pixabay.com/" rel="nofollow noopener" target="_blank">Pixabay</a>]]

<h2>Earnest money is not a closing cost</h2>

<p>People fold this into the same worry, so let&rsquo;s separate it.</p>

<p>Earnest money is the deposit you put down to show the seller you are serious. In Georgia it is often around 1% of the purchase price, though there is no rule that fixes it. It is not an extra cost. It is <strong>your money, held, and then credited back to you at closing.</strong></p>

<p>Here is the arithmetic, because it lands better than the explanation. Say you are buying a $300,000 home and you put down $3,000 in earnest money. At closing you are told you owe $5,000. You do not owe $8,000. You owe <strong>$2,000</strong> &mdash; because your $3,000 is already sitting there working for you.</p>

<p>And as long as you hold up your end of the contract and stay inside your deadlines, it is refundable. Those deadlines are the part people miss.</p>

<h2>Title insurance: the Carfax for your house</h2>

<p>You have heard of Carfax &mdash; the report that tells you the history of a used car. Did you know there is something similar for houses?</p>

<p>When you buy a home you are not just buying the structure. You are buying <strong>the right to own it</strong> without someone appearing later to say, <em>actually, this is mine.</em> Title insurance protects you from problems tied to the property&rsquo;s past: old liens, unpaid taxes, clerical errors in the record, forged documents, unknown heirs.</p>

<p>Two mechanics that almost nobody is told:</p>

<ul>
<li>It is a <strong>one-time cost paid at closing</strong> that protects you for as long as you own the home. This is not homeowners insurance. You do not renew it every year.</li>
<li>There are <strong>two different policies</strong>. Lender&rsquo;s title insurance protects the bank &mdash; period. If a problem surfaces, the lender gets made whole, not you. <strong>Owner&rsquo;s</strong> title insurance is the one that protects you, and it is usually optional.</li>
</ul>

<p>Skipping the owner&rsquo;s policy to save a few hundred dollars can cost you tens of thousands later. Most buyers never learn they had a choice until it is too late to make one. Which is why I keep saying it: <strong>real estate isn&rsquo;t expensive, but ignorance certainly is.</strong></p>

<h2>Where Metro Atlanta sits right now</h2>

<p>None of this is theory, and the market you are negotiating in decides how much of it you can ask for.</p>

<p>The most recent <a href="https://atlantarealtors.com/resources/news/atlanta-realtors-market-brief-july-2026" rel="nofollow noopener" target="_blank">Atlanta REALTORS&reg; Market Brief</a>, covering July 2026 across the eleven-county metro and compiled from FMLS data, put the median sale price at <strong>$445,000</strong>, up 2.1% on the year. There were <strong>20,863 active listings</strong> and a <strong>4.7-month supply</strong>, and homes were averaging <strong>24 days on market</strong>.</p>

<p>Read that as a buyer for a second. Four to five months of supply is a balanced market, not a frenzy. Twenty-four days is not a house selling before you can see it. In a market like this, asking a seller to contribute toward your closing costs is a normal conversation rather than an insult &mdash; and in a market like the one we had three years ago, it wasn&rsquo;t.</p>

<p>Which is my whole point. The market is not slow. <strong>The market is selective.</strong> Deals that make sense are still moving. Strategy matters more than speed.</p>

<h2>Four things that stop the surprise</h2>

<p>This is the part to save.</p>

<ul>
<li><strong>Read your loan estimate the day it arrives.</strong> Your lender has to give you one within three business days of your application. It is a real document with real numbers, and it is the earliest honest picture you will get.</li>
<li><strong>Ask your questions before closing day.</strong> The closing table is not the place for first-time questions. Preparation is what creates calm.</li>
<li><strong>Remember which lines are prepaids.</strong> Once you know that bucket is your own future bills, the total stops looking like a penalty.</li>
<li><strong>Keep a buffer.</strong> Not because something has gone wrong, but because real estate involves timing, operations and a lot of moving pieces. A cushion is not pessimism, it is planning.</li>
</ul>

<h2>So that&rsquo;s closing costs explained</h2>

<p>They are not random. They are strategic, and they are quite negotiable &mdash; once you know what you are looking at.</p>

<p>If you are buying in <a href="/atlanta-metro">Metro Atlanta</a> in the next few months, do not wait until you are under contract to find out what your number is. Whether you are buying in <a href="/gwinnett-lawrenceville">Gwinnett</a>, <a href="/east-cobb-marietta">East Cobb</a> or <a href="/mcdonough-henry">Henry County</a>, the four buckets are the same &mdash; only the numbers move. Let&rsquo;s <a href="/home-value">plan the numbers</a> for you before you fall in love with a house.</p>
HTML,
    ],

];
