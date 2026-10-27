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
        // Researched around her video "Septic Info" (Video_Edting_Erika, edited 26 Oct 2026; in
        // ceo_erika intake/edits/septic-info.md): her explainer lines only. The property manager
        // on her call is not quoted or named. Georgia's rules and design figures are from the
        // Department of Public Health's Manual for On-Site Sewage Management Systems (June 2026),
        // the upkeep from the EPA. Every fact is listed in tools/research/septic-systems-explained.md.
        'slug'           => 'septic-systems-explained',
        'title'          => 'On septic? Ask how many people it was built for.',
        'seo_title'      => 'Septic Systems Explained: Peak Design Flow, Bedrooms and What Georgia Buyers and Investors Should Check',
        'seo_desc'       => 'A septic system is sized for a number of bedrooms, not for whoever moves in. What Georgia’s rules say, what to check before you buy, and why it matters to investors.',
        'excerpt'        => 'A rental I looked at had a note: &ldquo;Due to the septic system, call first for details.&rdquo; The answer was four people, on a big house. Here&rsquo;s what a septic system is built for, and what to check.',
        'date'           => '2026-10-27',
        'updated'        => '2026-10-27',
        'author'         => 'Erika K. Page',
        'cat'            => 'buying',
        'tags'           => ['septic system', 'peak design flow', 'home inspection', 'real estate investing', 'rental property', 'Georgia'],
        'cover'          => 'assets/photos/17/septic-systems-explained-cover.jpg',
        'cover_alt'      => 'Erika K. Page beside the headline: On septic? Ask how many people it was built for.',
        'cover_credit'   => 'Inset photo by <a href="https://www.pexels.com/@quang-vuong-724225078" rel="nofollow noopener" target="_blank">Quang Vuong</a> on <a href="https://www.pexels.com/" rel="nofollow noopener" target="_blank">Pexels</a>',
        'audio'          => 'assets/audio/septic-systems-explained.mp3',
        'audio_note'     => 'A podcast episode on this topic, in Erika’s AI voice. The sources are listed at the end of the article.',
        'guide'          => '',
        'product'        => '',
        'short'          => 'septic',
        'audio_secs'     => 308,
        'published'      => true,
        'faq' => [
            [
                'q' => 'What is peak design flow?',
                'a' => 'The most wastewater a septic system is designed to handle in a day. In Georgia, a home&rsquo;s system is sized by its number of bedrooms, and the design assumes two people per bedroom and 150 gallons per bedroom per day.',
            ],
            [
                'q' => 'Can I add bedrooms to a house on septic?',
                'a' => 'Not without checking the system first. Georgia sizes residential systems by bedrooms, and repairs, replacements or additions to an existing system must be permitted and inspected by the county health department.',
            ],
            [
                'q' => 'How often should a septic tank be pumped?',
                'a' => 'The EPA says a typical household tank is pumped every three to five years and the system inspected at least every three years; systems with pumps or float switches, generally once a year.',
            ],
        ],
        'body' => <<<'HTML'
<p>A rental I was looking at had a note in it: due to the septic system, call first for details. &ldquo;You wouldn&rsquo;t see this as a customer, but as an agent I can see the private notes.&rdquo;</p>

<p>When I finally got the answer, the limit was four people. On a big house, I think five bedrooms. So let me explain what a septic system is really built for, and what to check before you buy, or rent, a home that has one.</p>

<h2>A septic system has a rating</h2>

<p>&ldquo;A lot of people don&rsquo;t realize that the septic system that goes with the house, it has a rating for the number of people that it can support.&rdquo; Everything goes there: &ldquo;all sink water comes through the septic system, bath water comes through the septic system, toilet paper.&rdquo; And problems can be in two places: &ldquo;at the actual tank&rdquo; or &ldquo;at the field lines.&rdquo;</p>

<p>Georgia&rsquo;s rules come from the Department of Public Health&rsquo;s Manual for On-Site Sewage Management Systems. For a house, the sewage flow is based on the number of bedrooms, and the calculations are made on peak flow, &ldquo;not on long term averages.&rdquo; <a href="https://dph.georgia.gov/media/92616/download" target="_blank" rel="noopener">[1]</a></p>

<h2>Peak design flow, and the 150 gallons</h2>

<p>The name for that rating is the peak design flow. In my video I said it&rsquo;s usually 150 gallons per bedroom, and that&rsquo;s the number Georgia&rsquo;s manual designs around: it assumes &ldquo;a home generates 75 gpcd with two people per bedroom or 150 gallons per bedroom per day.&rdquo; (gpcd is gallons per person per day.) <a href="https://dph.georgia.gov/media/92616/download" target="_blank" rel="noopener">[1]</a></p>

<p>The tank is sized by bedrooms too. For a single-family home, it&rsquo;s 1,000 gallons for one, two, three or four bedrooms, plus 250 gallons for each bedroom over four, and 50% more if there&rsquo;s a garbage grinder. <a href="https://dph.georgia.gov/media/92616/download" target="_blank" rel="noopener">[1]</a></p>

<p>So the rating is about the house as it was permitted. The manual defines a bedroom as &ldquo;any room that is intended primarily for sleeping purposes, as shown on the building plan.&rdquo; <a href="https://dph.georgia.gov/media/92616/download" target="_blank" rel="noopener">[1]</a> And a system that&rsquo;s old, or hasn&rsquo;t been maintained, may not keep up even with that.</p>

[[img:assets/photos/17/b41-house-on-a-big-lawn.jpg|A white house set back on a big lawn, with trees|Photo by <a href="https://www.pexels.com/@dianne-60973" rel="nofollow noopener" target="_blank">Dianne</a> on <a href="https://www.pexels.com/" rel="nofollow noopener" target="_blank">Pexels</a>]]

<h2>Now here&rsquo;s the important part, investors</h2>

<p>I&rsquo;ve had investors want to add bedrooms to put more people in. &ldquo;Well, you might not be able to do that. If you have a septic system.&rdquo;</p>

<p>Under Georgia&rsquo;s rules, &ldquo;repairs, replacement, or additions to existing systems must be permitted and inspected.&rdquo; And grading, digging or construction on the lot after the system&rsquo;s final inspection, if it may affect the system, voids the approval. <a href="https://dph.georgia.gov/media/92616/download" target="_blank" rel="noopener">[1]</a></p>

<p>A rooming house is rated differently from a family home, too. In the manual&rsquo;s flow schedule, a rooming house with no meals is figured at 60 gallons per bed. <a href="https://dph.georgia.gov/media/92616/download" target="_blank" rel="noopener">[1]</a> And that&rsquo;s before occupancy laws, which are a whole separate question.</p>

<h2>What to check before you buy</h2>

<ul>
<li><strong>The permit and the records.</strong> A septic system needs a construction permit from the county health department before it&rsquo;s installed, and the application lists the number of bedrooms. <a href="https://dph.georgia.gov/media/92616/download" target="_blank" rel="noopener">[1]</a> My tip: &ldquo;you can just Google it and you can see the drawing for the field lines and where your septic system is for your house.&rdquo; It usually takes a little digging on your county&rsquo;s website.</li>
<li><strong>An evaluation.</strong> County boards of health are routinely asked to do performance evaluations of existing systems, based on the installation inspection records, the maintenance records and a look at the site. <a href="https://dph.georgia.gov/media/92616/download" target="_blank" rel="noopener">[1]</a></li>
<li><strong>The maintenance history.</strong> The EPA says a typical household system should be inspected at least every three years and the tank pumped every three to five; systems with pumps or float switches, generally once a year. <a href="https://www.epa.gov/septic/how-care-your-septic-system" target="_blank" rel="noopener">[2]</a></li>
</ul>

[[img:assets/photos/17/b42-inspector-at-the-door.jpg|An inspector with a clipboard at a front door|Photo by <a href="https://www.pexels.com/@rdne" rel="nofollow noopener" target="_blank">RDNE Stock project</a> on <a href="https://www.pexels.com/" rel="nofollow noopener" target="_blank">Pexels</a>]]

<h2>Living with one</h2>

<p>&ldquo;If you go over capacity in your household, have your septic checked and cleaned more.&rdquo; And go easy on it. All the water a household sends down its pipes ends up in the septic system, so the EPA suggests spreading laundry through the week rather than doing it all in one day. Don&rsquo;t park or drive on the drainfield, and keep tree roots away from it. <a href="https://www.epa.gov/septic/how-care-your-septic-system" target="_blank" rel="noopener">[2]</a></p>

<p>Buying or investing in a home with a septic system? <a href="/contact">Reach out to me</a>.</p>

<p>Who knew there was something interesting about the septic system? I knew. So that&rsquo;s septic systems, explained.</p>

<p><em>This article explains how septic systems generally work and what Georgia&rsquo;s rules say, from the Department of Public Health&rsquo;s manual. It isn&rsquo;t legal or engineering advice: your county health department and a licensed septic professional have the final word on any system. Erika K. Page is a real estate agent with Axen Realty.</em></p>

<h2>Sources</h2>

<ol>
<li>Georgia Department of Public Health, <a href="https://dph.georgia.gov/media/92616/download" target="_blank" rel="noopener">Manual for On-Site Sewage Management Systems</a> (June 2026), including the On-Site Sewage Management Systems Rules, Chapter 511-3-1.</li>
<li>U.S. Environmental Protection Agency, <a href="https://www.epa.gov/septic/how-care-your-septic-system" target="_blank" rel="noopener">How to Care for Your Septic System</a> (updated August 17, 2026).</li>
<li>Erika K. Page, video &ldquo;Septic Info&rdquo; (October 2026), her own words.</li>
</ol>
HTML,
    ],

    [
        // Researched around her closing-day reel Dce4h7rJ52a (+ caption): a first-time buyer on
        // her final walkthrough with Erika ("My hand was held. I was educated."). The client is
        // not named here. The closing-week facts are the CFPB's (Close the deal, What is a Closing
        // Disclosure?, the closing checklist, the mortgage-closing-scams advice) and 12 CFR
        // 1026.19(f). Every fact is listed with its source in tools/research/the-week-before-closing.md.
        'slug'           => 'the-week-before-closing',
        'title'          => 'The week before closing: what to check before you sign.',
        'seo_title'      => 'The Week Before Closing: Your Closing Disclosure, the Final Walkthrough and Wire Fraud',
        'seo_desc'       => 'What happens the week before you close on a home: the 3-day Closing Disclosure rule, the final walkthrough, how to avoid wire fraud, and what to bring to the table.',
        'excerpt'        => 'A first-time buyer told me, on her final walkthrough: &ldquo;My hand was held. I was educated.&rdquo; Here&rsquo;s what that last week before closing looks like, and what to check before you sign.',
        'date'           => '2026-10-26',
        'updated'        => '2026-10-26',
        'author'         => 'Erika K. Page',
        'cat'            => 'buying',
        'tags'           => ['closing', 'Closing Disclosure', 'final walkthrough', 'wire fraud', 'first-time homebuyer', 'Metro Atlanta'],
        'cover'          => 'assets/photos/17/the-week-before-closing-cover.jpg',
        'cover_alt'      => 'Erika K. Page beside the headline: The week before closing: what to check before you sign.',
        'cover_credit'   => 'Inset photo by <a href="https://www.pexels.com/@silverkblack" rel="nofollow noopener" target="_blank">Vitaly Gariev</a> on <a href="https://www.pexels.com/" rel="nofollow noopener" target="_blank">Pexels</a>',
        'audio'          => 'assets/audio/the-week-before-closing.mp3',
        'audio_note'     => 'A podcast episode on this topic, in Erika’s AI voice. The sources are listed at the end of the article.',
        'guide'          => '',
        'product'        => '',
        'short'          => 'closing-week',
        'audio_secs'     => 315,
        'published'      => true,
        'faq' => [
            [
                'q' => 'When do I get my Closing Disclosure?',
                'a' => 'Your lender has to make sure you receive it at least three business days before you close on the loan, so you have time to compare it with your Loan Estimate and ask questions.',
            ],
            [
                'q' => 'Can a change to my Closing Disclosure delay closing?',
                'a' => 'Only certain changes restart the three-business-day wait: the APR becomes inaccurate, the loan product changes, or a prepayment penalty is added. Other changes get a corrected disclosure without a new waiting period.',
            ],
            [
                'q' => 'What if I get an email changing the wiring instructions?',
                'a' => 'Treat it as a likely scam. Don&rsquo;t use any phone number or link in the email; call a person you already trust (your agent or settlement agent) at a number you saved earlier. If money has already gone, call your bank for a wire recall right away and report it at ic3.gov.',
            ],
        ],
        'body' => <<<'HTML'
<p>&ldquo;Another client filled with joy, and this one is not about the house. It&rsquo;s how she was treated getting it.&rdquo;</p>

<p>That&rsquo;s how I introduced a reel of a first-time buyer, on her final walkthrough with me. What she said was: &ldquo;My hand was held. I was educated. Very professional people.&rdquo; The house is beautiful, but the experience is what she&rsquo;ll remember.</p>

<p>So let me explain the part where being educated matters most: the last week before closing. What arrives, what to check, and what to never do.</p>

<h2>Three business days with your Closing Disclosure</h2>

<p>The Closing Disclosure is a five-page form with the final details of your loan: the loan terms, your projected monthly payments, and what you&rsquo;ll pay in fees and other costs to get the mortgage. <a href="https://www.consumerfinance.gov/ask-cfpb/what-is-a-closing-disclosure-en-1983/" target="_blank" rel="noopener">[2]</a></p>

<p>Your lender has to make sure you receive it no later than three business days before you close. <a href="https://www.ecfr.gov/current/title-12/section-1026.19" target="_blank" rel="noopener">[3]</a> Use those days. Put it next to your most recent Loan Estimate. The CFPB&rsquo;s checklist points you to the table at the top of page three, because by law, only certain costs can change. <a href="https://www.consumerfinance.gov/owning-a-home/resources/checklist_mortgage_closing.pdf" target="_blank" rel="noopener">[4]</a> Ask your loan officer:</p>

<ul>
<li>Are the loan type, interest rate, monthly payment and other key terms what I expected?</li>
<li>Are there fees I don&rsquo;t understand, or that changed significantly?</li>
<li>Do I have an escrow account, and do I understand how it works?</li>
</ul>

<p>If something changes after that, you get a corrected disclosure, at or before closing. But only three kinds of change start a new three-business-day wait: the APR becomes inaccurate, the loan product changes, or a prepayment penalty is added. <a href="https://www.ecfr.gov/current/title-12/section-1026.19" target="_blank" rel="noopener">[3]</a></p>

<h2>The final walkthrough</h2>

<p>&ldquo;Before you sign any papers, do a final walk-through of the home.&rdquo; Make sure everything you and the seller agreed would be repaired has been repaired, and anything the seller agreed to leave in the house is in place. If it isn&rsquo;t, contact the seller right away to discuss it. <a href="https://www.consumerfinance.gov/owning-a-home/close/close-the-deal/" target="_blank" rel="noopener">[1]</a></p>

<p>Things can change a little in those last days. If the seller hasn&rsquo;t fixed something they agreed to fix, they might give you money toward your closing costs instead, a seller credit, rather than make the repair before closing. <a href="https://www.consumerfinance.gov/owning-a-home/close/close-the-deal/" target="_blank" rel="noopener">[1]</a></p>

[[img:assets/photos/17/b38-final-walkthrough-kitchen.jpg|A couple looking around the kitchen of a house before closing|Photo by <a href="https://www.pexels.com/@rdne" rel="nofollow noopener" target="_blank">RDNE Stock project</a> on <a href="https://www.pexels.com/" rel="nofollow noopener" target="_blank">Pexels</a>]]

<h2>Now here&rsquo;s the important part: your money</h2>

<p>Your down payment and closing costs are a tempting target. The scam works like this: someone gets into the email of a real estate or settlement agent, watches for a closing, then emails the buyer &ldquo;new&rdquo; wiring instructions. The email can look exactly like a real conversation with your agent. <a href="https://www.consumerfinance.gov/about-us/blog/mortgage-closing-scams-how-protect-yourself-and-your-closing-funds/" target="_blank" rel="noopener">[5]</a> The CFPB&rsquo;s advice:</p>

<ul>
<li><strong>Before closing,</strong> pick two people you trust, like your agent and your settlement agent, and save their phone numbers. Talk through how the money moves, in person or by phone.</li>
<li><strong>Before you wire anything,</strong> confirm the instructions, including the account name and number, by calling one of them at the number you already saved. Never follow instructions in an email.</li>
<li><strong>Don&rsquo;t use phone numbers or links in an email,</strong> and don&rsquo;t email your financial information.</li>
<li><strong>If it happens,</strong> call your bank or wire company immediately and ask for a wire recall, then file a complaint with the FBI at <a href="https://www.ic3.gov" target="_blank" rel="noopener">ic3.gov</a>.</li>
</ul>

<h2>At the closing table</h2>

<p>Bring your ID, your cashier&rsquo;s check or proof of your wire, your Closing Disclosure to compare one more time, your list of people to call, and your co-borrower. <a href="https://www.consumerfinance.gov/owning-a-home/resources/checklist_mortgage_closing.pdf" target="_blank" rel="noopener">[4]</a></p>

<p>Then take your time. You have the right to review every document, whoever requires it. If something looks different from what you were told, ask. Questions about the sale go to your agent or the settlement agent; questions about the loan go to your lender. And don&rsquo;t ever sign blank documents. <a href="https://www.consumerfinance.gov/owning-a-home/close/close-the-deal/" target="_blank" rel="noopener">[1]</a></p>

<p>You aren&rsquo;t committed until you&rsquo;ve signed. You can walk away at closing, but you might lose your deposit, and breaking the purchase contract can have other consequences. So know what your contract says before you walk in. <a href="https://www.consumerfinance.gov/owning-a-home/close/close-the-deal/" target="_blank" rel="noopener">[1]</a></p>

[[img:assets/photos/17/b39-signing-at-the-closing-table.jpg|A hand with a pen over papers at a table|Photo by <a href="https://www.pexels.com/@kampus" rel="nofollow noopener" target="_blank">Kampus Production</a> on <a href="https://www.pexels.com/" rel="nofollow noopener" target="_blank">Pexels</a>]]

<h2>After you get the keys</h2>

<p>Save your whole closing packet, exactly as you got it. Change your address with your bank, the post office and the DMV. And look at your homeowner&rsquo;s insurance again: does it cover floods? <a href="https://www.consumerfinance.gov/owning-a-home/resources/checklist_mortgage_closing.pdf" target="_blank" rel="noopener">[4]</a></p>

<p>That&rsquo;s what real estate should feel like: guided, supported, and cared for from start to finish. If you&rsquo;re buying, <a href="/contact">reach out to me</a>.</p>

<p>So that&rsquo;s the week before closing, explained.</p>

<p><em>This article explains how closing generally works under federal mortgage rules, from the Consumer Financial Protection Bureau&rsquo;s guidance. It isn&rsquo;t legal or financial advice: your purchase contract and your lender set the details. Erika K. Page is a real estate agent with Axen Realty.</em></p>

<h2>Sources</h2>

<ol>
<li>Consumer Financial Protection Bureau, <a href="https://www.consumerfinance.gov/owning-a-home/close/close-the-deal/" target="_blank" rel="noopener">Close the deal</a> (August 21, 2025).</li>
<li>Consumer Financial Protection Bureau, <a href="https://www.consumerfinance.gov/ask-cfpb/what-is-a-closing-disclosure-en-1983/" target="_blank" rel="noopener">What is a Closing Disclosure?</a></li>
<li>Code of Federal Regulations, <a href="https://www.ecfr.gov/current/title-12/section-1026.19" target="_blank" rel="noopener">12 CFR 1026.19, Certain mortgage and variable-rate transactions</a> (Regulation Z).</li>
<li>Consumer Financial Protection Bureau, <a href="https://www.consumerfinance.gov/owning-a-home/resources/checklist_mortgage_closing.pdf" target="_blank" rel="noopener">Your mortgage closing checklist</a> (2019).</li>
<li>Consumer Financial Protection Bureau, <a href="https://www.consumerfinance.gov/about-us/blog/mortgage-closing-scams-how-protect-yourself-and-your-closing-funds/" target="_blank" rel="noopener">Mortgage closing scams: how to protect yourself and your closing funds</a>.</li>
<li>Erika K. Page, closing-day reel with a first-time buyer, her own words and her client&rsquo;s.</li>
</ol>
HTML,
    ],

    [
        // Researched around her three "what do you wish you knew in your first year of real
        // estate?" reels (DcrGxmuCqHx, DcoGkAvih2-, DcqrYpwCsfR) and her mentor video
        // (erika-page-axen-mentor-remake). The license steps are from the Georgia Real Estate
        // Commission's own pages and the PSI candidate bulletin (7/1/2026). Her "12 to 17
        // touches" figure is left out: no source. Every fact is listed with its source in
        // tools/research/what-the-real-estate-class-wont-teach-you.md.
        'slug'           => 'what-the-real-estate-class-wont-teach-you',
        'title'          => 'What the real estate class won&rsquo;t teach you.',
        'seo_title'      => 'Getting a Georgia Real Estate License: The Official Steps, and What the Class Won’t Teach You',
        'seo_desc'       => 'How to get a real estate license in Georgia, step by step from the Commission’s own rules, and what your first year really takes: prospecting, follow-up, a mentor.',
        'excerpt'        => 'If you took the real estate class, you already know: it didn&rsquo;t teach you how to be a real estate agent. Here are Georgia&rsquo;s license steps, and what nobody teaches you.',
        'date'           => '2026-10-23',
        'updated'        => '2026-10-23',
        'author'         => 'Erika K. Page',
        'cat'            => 'lifestyle',
        'tags'           => ['real estate license', 'new real estate agent', 'Georgia Real Estate Commission', 'prospecting', 'mentorship', 'real estate career'],
        'cover'          => 'assets/photos/17/what-the-real-estate-class-wont-teach-you-cover.jpg',
        'cover_alt'      => 'Erika K. Page beside the headline: What the real estate class won&rsquo;t teach you.',
        'cover_credit'   => 'Inset photo by <a href="https://www.pexels.com/@rdne" rel="nofollow noopener" target="_blank">RDNE Stock project</a> on <a href="https://www.pexels.com/" rel="nofollow noopener" target="_blank">Pexels</a>',
        'audio'          => 'assets/audio/what-the-real-estate-class-wont-teach-you.mp3',
        'audio_note'     => 'A podcast episode on this topic, in Erika’s AI voice. The sources are listed at the end of the article.',
        'guide'          => '',
        'product'        => '',
        'short'          => 'new-agent',
        'audio_secs'     => 356,
        'published'      => true,
        'faq' => [
            [
                'q' => 'How do I get a real estate license in Georgia?',
                'a' => 'Be at least 18 with a high school diploma or equivalent, complete the 75-hour Salesperson Prelicense Course at a school the Georgia Real Estate Commission approves, bring your criminal history (GCIC) report to the PSI test center, pass the PSI exam, and apply for your license. To work, a broker has to sponsor you.',
            ],
            [
                'q' => 'How long do I have to apply after passing the Georgia exam?',
                'a' => 'Apply within three months of the exam date. Between three and twelve months the fee to activate doubles, and after twelve months you have to retake the exam (but not the prelicense course).',
            ],
            [
                'q' => 'What does a new Georgia agent have to do in the first year?',
                'a' => 'Complete a 25-hour Salesperson Postlicense course approved by the Commission. If you don&rsquo;t finish it in your first year, the license lapses. After that, active licensees take 36 hours of continuing education every four years, including 3 hours on license law.',
            ],
        ],
        'body' => <<<'HTML'
<p>&ldquo;If you took the real estate class, you already know that didn&rsquo;t teach you anything about&hellip; how to be a real estate agent.&rdquo;</p>

<p>I said that in a reel, answering a question I get all the time: what do you wish you&rsquo;d known in your first year? So let me explain. First, what Georgia actually asks of you to get the license. Then the part the class leaves out.</p>

<h2>Getting the license in Georgia: the official steps</h2>

<p>Here&rsquo;s what the Georgia Real Estate Commission lists for a salesperson&rsquo;s license <a href="https://grec.state.ga.us/obtaining-a-license/real-estate/" target="_blank" rel="noopener">[1]</a>:</p>

<ul>
<li><strong>Age and education:</strong> at least 18, and a high school graduate or the holder of a certificate of equivalency.</li>
<li><strong>The course:</strong> the 75-hour Salesperson Prelicense Course at a school the Commission approves. (Certain college or law-school coursework can count instead.)</li>
<li><strong>A background check:</strong> you bring your criminal history, the Georgia Crime Information Center (GCIC) report, to the PSI test center before you&rsquo;re licensed. If it shows a conviction, the Commission investigates and decides.</li>
<li><strong>The exam:</strong> administered by PSI and approved by the Commission.</li>
<li><strong>A broker:</strong> to work, you activate your license with a broker, using the Sponsoring Broker Statement that the broker signs.</li>
</ul>

<p>Two timing rules catch people. Apply within three months of your exam date: after that the fee to activate doubles, and after twelve months you retake the exam. And you can&rsquo;t start working the day you pass. The Commission&rsquo;s answer is that &ldquo;you may not begin brokerage activity until your broker receives your wall certificate and pocket card from the Commission.&rdquo; <a href="https://grec.state.ga.us/licensed-professionals/faq/real-estate/" target="_blank" rel="noopener">[2]</a></p>

[[img:assets/photos/17/b35-studying-for-the-license.jpg|A woman studying at her kitchen table with textbooks, a notebook and a laptop|Photo by <a href="https://www.pexels.com/@karola-g" rel="nofollow noopener" target="_blank">Karola G</a> on <a href="https://www.pexels.com/" rel="nofollow noopener" target="_blank">Pexels</a>]]

<h2>What the exam actually tests</h2>

<p>PSI publishes the outline <a href="https://test-takers.psiexams.com/api/content/bulletin/4678" target="_blank" rel="noopener">[4]</a>. The national part covers property ownership, land use, valuation, financing, contracts, agency, disclosures, property management, transfer of title, the practice of real estate and the math. Contracts are the biggest share, 19% of the salesperson exam. The Georgia part covers state laws and rules, real estate practice in Georgia, and finance and closing.</p>

<p>It even covers the rules around reaching people: fair housing advertising, Do-Not-Call compliance, social media. What you won&rsquo;t find is a section on how to find your first client. That&rsquo;s the rules of the road. It&rsquo;s not how to drive.</p>

<h2>Your first year has its own homework</h2>

<ul>
<li><strong>A second course.</strong> &ldquo;Salespersons must take a 25 hour Salespersons Postlicense course in their first year of licensure.&rdquo; Miss it, and the license lapses. <a href="https://grec.state.ga.us/obtaining-a-license/real-estate/" target="_blank" rel="noopener">[1]</a></li>
<li><strong>Then continuing education:</strong> 36 hours every four-year renewal, including 3 hours on license law. <a href="https://grec.state.ga.us/licensed-professionals/education-requirements/real-estate/" target="_blank" rel="noopener">[3]</a></li>
<li><strong>One broker at a time,</strong> with narrow exceptions. <a href="https://grec.state.ga.us/licensed-professionals/faq/real-estate/" target="_blank" rel="noopener">[2]</a></li>
<li><strong>Your advertising isn&rsquo;t only yours.</strong> Even as an independent contractor, &ldquo;all advertising must be in the name of and approved by the broker.&rdquo; <a href="https://grec.state.ga.us/licensed-professionals/faq/real-estate/" target="_blank" rel="noopener">[2]</a></li>
<li><strong>Referral gifts.</strong> May you pay a fee or give a gift to someone who refers a prospect to you? The Commission&rsquo;s answer: &ldquo;No, unless that person is a real estate licensee.&rdquo; <a href="https://grec.state.ga.us/licensed-professionals/faq/real-estate/" target="_blank" rel="noopener">[2]</a></li>
<li><strong>Read your agreement.</strong> The terms of your independent contractor agreement with your broker are a matter of negotiation. If you don&rsquo;t understand or don&rsquo;t agree with something, talk to the broker before you sign. <a href="https://grec.state.ga.us/licensed-professionals/faq/real-estate/" target="_blank" rel="noopener">[2]</a></li>
</ul>

<h2>Now here&rsquo;s the important part: what nobody teaches you</h2>

<p><strong>How to prospect.</strong> &ldquo;I wasted about a year just trying to learn prospecting.&rdquo; If someone had given me the one-twos of it, &ldquo;it would&rsquo;ve saved me a lot of time and a lot of money.&rdquo;</p>

<p><strong>Follow-up and consistency.</strong> &ldquo;It&rsquo;s not about chasing the lead sources that everyone else is doing. It&rsquo;s about doing business your way and understanding the power of the follow-up and the consistency.&rdquo; So many people never get to the point of getting that business, and then they find out the client went with somebody else. That was probably one of the most valuable things I learned early on.</p>

<p><strong>Finding your own leads.</strong> &ldquo;If you can create your own leads, if you can find leads, then you solve ninety-nine percent of the problem.&rdquo; And having a mentor to walk you through that is one of the most critical things.</p>

[[img:assets/photos/17/b36-mentor-and-new-agent.jpg|Two women at a desk going over papers together, both smiling|Photo by <a href="https://www.pexels.com/@edmond-dantes" rel="nofollow noopener" target="_blank">Edmond Dant&egrave;s</a> on <a href="https://www.pexels.com/" rel="nofollow noopener" target="_blank">Pexels</a>]]

<h2>What a mentor is for</h2>

<p>I&rsquo;ve been in this business 24+ years, and one thing I know for sure: &ldquo;success in real estate is not accidental. It comes from structure, consistency, confidence, and having the right guidance early.&rdquo;</p>

<p>When I mentor an agent, we focus on the things that move a business forward: &ldquo;systems, lead generation, follow-up, contracts, client conversations, time management, accountability, and the mindset required to build a business, not just hold a license.&rdquo;</p>

<p>&ldquo;You don&rsquo;t have to know everything yet. That&rsquo;s what mentorship is for. But you do need to be willing to show up, follow through, and grow.&rdquo;</p>

<p>If you&rsquo;re licensed, or about to be, and you want that kind of guidance, <a href="/contact">reach out to me</a>.</p>

<p>So that&rsquo;s what the real estate class won&rsquo;t teach you, explained.</p>

<p><em>This article explains how Georgia licensing generally works, from the Commission&rsquo;s own pages as of October 2026. It isn&rsquo;t legal advice. Requirements and fees change: confirm them with the Georgia Real Estate Commission before you enroll. Erika K. Page is a real estate agent with Axen Realty.</em></p>

<h2>Sources</h2>

<ol>
<li>Georgia Real Estate Commission, <a href="https://grec.state.ga.us/obtaining-a-license/real-estate/" target="_blank" rel="noopener">Obtaining a License: Real Estate (Salesperson)</a>.</li>
<li>Georgia Real Estate Commission, <a href="https://grec.state.ga.us/licensed-professionals/faq/real-estate/" target="_blank" rel="noopener">Frequently Asked Questions: Real Estate</a>.</li>
<li>Georgia Real Estate Commission, <a href="https://grec.state.ga.us/licensed-professionals/education-requirements/real-estate/" target="_blank" rel="noopener">Education Requirements: Real Estate</a>.</li>
<li>PSI Services, <a href="https://test-takers.psiexams.com/api/content/bulletin/4678" target="_blank" rel="noopener">Georgia Real Estate Commission Candidate Information Bulletin</a> (July 1, 2026).</li>
<li>Erika K. Page, reels on her first year in real estate and her mentor video, her own words.</li>
</ol>
HTML,
    ],

    [
        // Researched around her video trump-50-year-mortgage (November 2025), re-checked on
        // 22 Oct 2026: the proposal stalled in January 2026 (Politico via NASCUS), and the
        // Qualified Mortgage rule (12 CFR 1026.43) and Fannie Mae's Selling Guide still cap
        // terms at 30 years. NPR for the announcement and a Realtor.com example. Every fact
        // is listed with its source in tools/research/fifty-year-mortgage-explained.md.
        'slug'           => 'fifty-year-mortgage-explained',
        'title'          => 'A 50-year mortgage? Here&rsquo;s what it would really cost.',
        'seo_title'      => 'The 50-Year Mortgage, Explained: Lower Payment, Far More Interest, and Where the Idea Stands',
        'seo_desc'       => 'A 50-year mortgage would lower the monthly payment and nearly double the interest. What it would cost, why you can’t get one today, and what to do instead.',
        'excerpt'        => 'Imagine carrying your mortgage into your eighties. A 50-year loan would lower the payment and nearly double the interest. Here&rsquo;s the math, and where the idea stands now.',
        'date'           => '2026-10-22',
        'updated'        => '2026-10-22',
        'author'         => 'Erika K. Page',
        'cat'            => 'buying',
        'tags'           => ['50-year mortgage', 'mortgage', 'first-time homebuyer', 'home affordability', 'qualified mortgage', 'Metro Atlanta'],
        'cover'          => 'assets/photos/17/fifty-year-mortgage-explained-cover.jpg',
        'cover_alt'      => 'Erika K. Page beside the headline: A 50-year mortgage? Here&rsquo;s what it would really cost.',
        'cover_credit'   => 'Inset photo by <a href="https://www.pexels.com/@rdne" rel="nofollow noopener" target="_blank">RDNE Stock project</a> on <a href="https://www.pexels.com/" rel="nofollow noopener" target="_blank">Pexels</a>',
        'audio'          => 'assets/audio/fifty-year-mortgage-explained.mp3',
        'audio_note'     => 'A podcast episode on this topic, in Erika’s AI voice. The sources are listed at the end of the article.',
        'guide'          => '',
        'product'        => '',
        'short'          => '50-year',
        'audio_secs'     => 301,
        'published'      => true,
        'faq' => [
            [
                'q' => 'Can I get a 50-year mortgage today?',
                'a' => 'Not a mainstream one. As of October 2026 the idea announced in November 2025 hasn&rsquo;t become a product: a Qualified Mortgage can&rsquo;t have a term over 30 years, and Fannie Mae buys loans with terms up to 30 years.',
            ],
            [
                'q' => 'How much would a 50-year mortgage save each month?',
                'a' => 'Less than most people expect. On $300,000 at 7% (an illustration, same rate for both), the payment drops from about $1,996 to about $1,805, roughly $191 a month, while total interest rises from about $418,500 to about $783,000.',
            ],
            [
                'q' => 'Why does a longer mortgage build equity so slowly?',
                'a' => 'Early payments are mostly interest. In the same illustration, ten years of payments on the 50-year loan pay off about $9,500 of the balance, against about $42,600 on the 30-year loan.',
            ],
        ],
        'body' => <<<'HTML'
<p>Imagine carrying your mortgage into your eighties. Sounds extreme? But what if you could cut your monthly payment right now?</p>

<p>That was the pitch when the idea of a 50-year mortgage hit the news last November. Yes, five-zero. Half a century of payments. Let me explain what it would really cost, and where the idea stands today.</p>

<h2>Where the idea came from</h2>

<p>In November 2025 the President said he wanted the federal government to back a 50-year mortgage option for homebuyers, a plan the Federal Housing Finance Agency&rsquo;s director, Bill Pulte, called &ldquo;a complete game changer.&rdquo; <a href="https://www.npr.org/2025/11/12/nx-s1-5604384/50-year-mortgage-trump-housing-explainer" target="_blank" rel="noopener">[1]</a></p>

<p>Why then? Because with high home prices and high interest rates, the traditional 30-year mortgage just isn&rsquo;t cutting it for a lot of buyers, especially first-timers and younger families. That&rsquo;s what I said at the time.</p>

<h2>Where it stands now</h2>

<p>In January 2026, asked whether 50-year mortgages were still on the table, Pulte told reporters: &ldquo;I think we have other priorities.&rdquo; <a href="https://www.nascus.org/2026/01/15/pulte-backs-away-from-50-year-mortgage/" target="_blank" rel="noopener">[2]</a></p>

<p>And the rules behind almost every home loan still stop at 30 years. Under the federal Ability-to-Repay rule, a Qualified Mortgage is one &ldquo;for which the loan term does not exceed 30 years.&rdquo; <a href="https://www.ecfr.gov/current/title-12/section-1026.43" target="_blank" rel="noopener">[3]</a> Fannie Mae&rsquo;s Selling Guide says Fannie Mae &ldquo;purchases or securitizes loans that have original terms up to 30 years.&rdquo; <a href="https://selling-guide.fanniemae.com/sel/b2-1.5-02/loan-eligibility" target="_blank" rel="noopener">[4]</a></p>

<p>So as of October 2026, a 50-year mortgage isn&rsquo;t something you can walk into a lender and get. But the question behind it, what a longer loan really costs, is still worth answering.</p>

<h2>Now here&rsquo;s the important part: the math</h2>

<p>Here&rsquo;s the trade-off. Lower monthly payment, yes. But more total interest, absolutely, and slower equity buildup, which means it could take longer to actually own anything, if that&rsquo;s important to you.</p>

<p>Let&rsquo;s put numbers on it. Take a $300,000 loan at 7%. That&rsquo;s an illustration, not a rate quote, and it assumes the same rate for both loans. Principal and interest only:</p>

<ul>
<li><strong>30 years:</strong> about $1,996 a month. Total interest: about $418,500.</li>
<li><strong>50 years:</strong> about $1,805 a month. Total interest: about $783,000.</li>
</ul>

<p>That&rsquo;s about $191 a month less, for about $364,500 more in interest. And in real life the gap would likely be wider: a lender would want a higher rate for a longer loan. When NPR ran its own example, a Realtor.com economist put the saving at &ldquo;at most about $250 per month&rdquo; and the extra interest at 86%, and made the same point about the rate. <a href="https://www.npr.org/2025/11/12/nx-s1-5604384/50-year-mortgage-trump-housing-explainer" target="_blank" rel="noopener">[1]</a></p>

[[img:assets/photos/17/b33-couple-running-mortgage-numbers.jpg|A young couple going over figures with a calculator at the kitchen table|Photo by <a href="https://www.pexels.com/@mikhail-nilov" rel="nofollow noopener" target="_blank">Mikhail Nilov</a> on <a href="https://www.pexels.com/" rel="nofollow noopener" target="_blank">Pexels</a>]]

<h2>Equity is the part people miss</h2>

<p>Early payments on any mortgage are mostly interest. On a 50-year loan, that stretches out. In the same illustration, ten years of payments pay off about $42,600 of the 30-year loan, and about $9,500 of the 50-year one.</p>

<p>That&rsquo;s why I talked about longer commitments, more debt, and the risk of being house poor for longer. For sellers and investors, it might increase demand, because more people may qualify. But we&rsquo;d have to watch for market inflation.</p>

<h2>What to do instead</h2>

<p>This isn&rsquo;t just about buying a home. It&rsquo;s about understanding the real cost of money over time. Your mortgage is more than a payment. It&rsquo;s a wealth strategy, if it&rsquo;s done correctly.</p>

<p>So before you stretch the term, run the numbers: the payment, the total interest, and how fast you&rsquo;re building equity. If you want to run them together, <a href="/contact">reach out to me</a>.</p>

<p>So that&rsquo;s the 50-year mortgage, explained.</p>

<p><em>This article explains how mortgage terms generally work. It isn&rsquo;t financial advice, a loan offer or a rate quote: the figures above are an illustration. Erika K. Page is a real estate agent with Axen Realty, not a lender.</em></p>

<h2>Sources</h2>

<ol>
<li>NPR, <a href="https://www.npr.org/2025/11/12/nx-s1-5604384/50-year-mortgage-trump-housing-explainer" target="_blank" rel="noopener">3 questions about Trump&rsquo;s 50-year mortgage plan</a> (November 12, 2025).</li>
<li>Politico, as republished by NASCUS, <a href="https://www.nascus.org/2026/01/15/pulte-backs-away-from-50-year-mortgage/" target="_blank" rel="noopener">Pulte backs away from 50-year mortgage</a> (January 15, 2026).</li>
<li>Code of Federal Regulations, <a href="https://www.ecfr.gov/current/title-12/section-1026.43" target="_blank" rel="noopener">12 CFR 1026.43, Minimum standards for transactions secured by a dwelling</a> (Regulation Z).</li>
<li>Fannie Mae, Selling Guide, <a href="https://selling-guide.fanniemae.com/sel/b2-1.5-02/loan-eligibility" target="_blank" rel="noopener">B2-1.5-02, Loan Eligibility</a>.</li>
<li>Erika K. Page, video on the 50-year mortgage (November 2025), her own words.</li>
</ol>
HTML,
    ],

    [
        // Researched around her reel DcbXCobtMCq ("You can own the building and not own an inch
        // of the ground it's on") and its caption. MARTA's 2012 TOD board briefing, the Atlanta
        // Land Trust FAQs, Fannie Mae's Selling Guide B2-3-03 and 24 CFR 203.37. Every fact is
        // listed with its source in tools/research/ground-leases-explained.md.
        'slug'           => 'ground-leases-explained',
        'title'          => 'You can own the building without owning the dirt.',
        'seo_title'      => 'Ground Leases Explained: Owning the Building, Not the Land (with Atlanta Examples)',
        'seo_desc'       => 'How a ground lease works: the landowner keeps the dirt, you own what you build. Atlanta examples, land trust homes, and what lenders require of the lease.',
        'excerpt'        => 'You can own the building and not own an inch of the ground it&rsquo;s on. Here&rsquo;s how ground leases work, where you&rsquo;ll find them around Atlanta, and what to check before you sign one.',
        'date'           => '2026-10-21',
        'updated'        => '2026-10-21',
        'author'         => 'Erika K. Page',
        'cat'            => 'investing',
        'tags'           => ['ground lease', 'commercial real estate', 'land trust', 'leasehold', 'real estate investing', 'Metro Atlanta'],
        'cover'          => 'assets/photos/17/ground-leases-explained-cover.jpg',
        'cover_alt'      => 'Erika K. Page beside the headline: You can own the building without owning the dirt.',
        'cover_credit'   => 'Inset photo by <a href="https://www.pexels.com/@ninobur" rel="nofollow noopener" target="_blank">David Brown</a> on <a href="https://www.pexels.com/" rel="nofollow noopener" target="_blank">Pexels</a>',
        'audio'          => 'assets/audio/ground-leases-explained.mp3',
        'audio_note'     => 'A podcast episode on this topic, in Erika’s AI voice. The sources are listed at the end of the article.',
        'guide'          => '',
        'product'        => '',
        'short'          => 'ground-lease',
        'audio_secs'     => 386,
        'published'      => true,
        'faq' => [
            [
                'q' => 'What is a ground lease?',
                'a' => 'A ground lease splits a property in two: one party owns the land, and the other leases the land, builds on it and owns the building for the term of the lease. What happens to the building when the lease ends is whatever the lease says.',
            ],
            [
                'q' => 'Can I get a mortgage on a home on leased land?',
                'a' => 'Often, if the lease runs long enough. Fannie Mae requires the lease to run at least five years past the loan&rsquo;s maturity date. FHA requires a renewable lease of at least 99 years, or one with at least 10 years left after the mortgage matures.',
            ],
            [
                'q' => 'How do Atlanta Land Trust homes work?',
                'a' => 'The Atlanta Land Trust owns the land and the homeowner owns the house, which keeps the price lower. When you sell, a resale formula keeps the home affordable for the next buyer; you must live there, and you must use one of the trust&rsquo;s approved lenders.',
            ],
        ],
        'body' => <<<'HTML'
<p>You can own the building and not own an inch of the ground it&rsquo;s on. That&rsquo;s a ground lease.</p>

<p>The landowner keeps the dirt. You lease it for up to ninety-nine years. You build on it, and everything you build belongs to you for the whole term of the lease.</p>

<p>Let me explain how that works, where you&rsquo;ll find it around Atlanta, and what to check before you sign one.</p>

<h2>Two owners, one property</h2>

<p>A ground lease splits a property in two. One party owns the land. The other leases the land and owns the building on it.</p>

<p>The landowner collects income for decades without ever selling. The business, or the buyer, gets control of the site without paying for the land, and builds on it. And when the lease is up, the building typically goes back to the landowner. That&rsquo;s the usual deal, but the lease decides, so that&rsquo;s the first part to read.</p>

<h2>Here&rsquo;s what most people miss</h2>

<p>The pharmacy or the bank branch on the corner? A lot of them just lease the dirt.</p>

<p>And it isn&rsquo;t only corner stores. MARTA owns land around its stations, and it leases that land to developers. In a 2012 briefing to its board, MARTA listed five station areas with active ground leases, and said its 2010 guidelines for building around stations state &ldquo;a preference for ground leases rather than sales.&rdquo; <a href="https://www.itsmarta.com/pdfs/PER_minutes_52912.pdf" target="_blank" rel="noopener">[1]</a></p>

<p>One of them is One Atlantic Center, the 50-story office tower at 14th and West Peachtree, by Arts Center station. According to that briefing, MARTA signed a 49-year lease in 1983 with extensions to 99 years, and the tower went up in 1987 on 3.08 acres. At Lindbergh, the same briefing lists a 99-year lease with AT&amp;T from 2000 and a 99-year ground lease for the Uptown Square apartments from 2004. <a href="https://www.itsmarta.com/pdfs/PER_minutes_52912.pdf" target="_blank" rel="noopener">[1]</a></p>

<p>So a tower on the skyline can stand on land its owner rents. That&rsquo;s control, not ownership.</p>

[[img:assets/photos/17/b31-downtown-atlanta-towers.jpg|Office towers in downtown Atlanta from above|Photo by <a href="https://www.pexels.com/@kelly" rel="nofollow noopener" target="_blank">K</a> on <a href="https://www.pexels.com/" rel="nofollow noopener" target="_blank">Pexels</a>]]

<h2>The same idea at home: Atlanta&rsquo;s land trust homes</h2>

<p>You&rsquo;ll find the same split in homeownership. The Atlanta Land Trust is a community land trust: &ldquo;The trust owns the land, and the homeowner owns the house on it.&rdquo; Buyers purchase the house at a lower price and lease the land from the trust, which &ldquo;keeps the cost lower because the buyer doesn&rsquo;t have to pay for the land.&rdquo; <a href="https://atlantalandtrust.org/faqs/" target="_blank" rel="noopener">[2]</a></p>

<p>Know the trade-offs before you fall in love with the house:</p>

<ul>
<li><strong>The resale is set by a formula.</strong> When you sell, you agree to a resale formula that keeps the home affordable for the next buyer. The trust uses two: one lets the homeowner realize 25% of the appreciated value, and the other uses average income growth. <a href="https://atlantalandtrust.org/faqs/" target="_blank" rel="noopener">[2]</a></li>
<li><strong>It has to be your home.</strong> Land trust homes can&rsquo;t be rented out for any period; the home must be your primary residence. <a href="https://atlantalandtrust.org/faqs/" target="_blank" rel="noopener">[2]</a></li>
<li><strong>The lender has to be one of theirs.</strong> Buyers must use an approved lender trained in the land trust&rsquo;s &ldquo;dual ownership structure.&rdquo; Sales prices are pre-set, not negotiable. <a href="https://atlantalandtrust.org/faqs/" target="_blank" rel="noopener">[2]</a></li>
<li><strong>Taxes, for now, are the same.</strong> The trust says its properties are presently taxed at the same rate as other property. <a href="https://atlantalandtrust.org/faqs/" target="_blank" rel="noopener">[2]</a></li>
</ul>

<p>For the right buyer, that&rsquo;s a way into a neighborhood that might otherwise be out of reach.</p>

<h2>Now here&rsquo;s the important part: the clock</h2>

<p>A ground lease runs out. That one fact drives everything else, starting with the loan.</p>

<p>Lenders want the lease to outlast the loan. Fannie Mae&rsquo;s Selling Guide requires the lease to have &ldquo;an unexpired term that exceeds the maturity date of the loan by five (5) years or more.&rdquo; <a href="https://selling-guide.fanniemae.com/sel/b2-3-03/special-property-eligibility-and-underwriting-considerations-leasehold-estates" target="_blank" rel="noopener">[3]</a> FHA&rsquo;s rule is a lease &ldquo;for not less than 99 years which is renewable, or&hellip; having a period of not less than 10 years to run beyond the maturity date of the mortgage.&rdquo; <a href="https://www.ecfr.gov/current/title-24/section-203.37" target="_blank" rel="noopener">[4]</a></p>

<p>So on a 30-year loan, Fannie Mae&rsquo;s rule means at least 35 years left on the lease. The years left on the lease matter as much as the building on it.</p>

<h2>What to check before you sign</h2>

<p>Fannie Mae&rsquo;s lease requirements make a good checklist even if you never use a Fannie Mae loan, because they show what a lender worries about: <a href="https://selling-guide.fanniemae.com/sel/b2-3-03/special-property-eligibility-and-underwriting-considerations-leasehold-estates" target="_blank" rel="noopener">[3]</a></p>

<ul>
<li><strong>The years left</strong> on the lease, measured against your loan.</li>
<li><strong>Whether you can sell it on.</strong> The lease should let you assign, transfer, mortgage and sublease it, without the landowner running a credit review on the next buyer.</li>
<li><strong>What happens on a default.</strong> Fannie Mae wants the lender to get notice and at least 30 days to cure it, take over the lease, or foreclose.</li>
<li><strong>A taking.</strong> The lease should protect the lender if the property is condemned.</li>
<li><strong>The paperwork.</strong> The lease is recorded in the county land records, rent that&rsquo;s due is paid, and nobody claims a default.</li>
</ul>

<p>Then the money questions: how the ground rent changes over the years, who pays the property taxes and insurance, and what happens to the building when the lease ends.</p>

<h2>Control beats ownership, sometimes</h2>

<p>So before you think you have to buy the dirt, you don&rsquo;t. Sometimes control beats ownership, and never selling is the smartest play.</p>

<p>But the lease is the whole deal, so have a real estate attorney read it before you sign. If you&rsquo;re weighing a ground lease, as the landowner or as the one who builds, <a href="/contact">let&rsquo;s talk it through</a>.</p>

<p>So that&rsquo;s ground leases, explained.</p>

<p><em>This article explains how ground leases generally work. It isn&rsquo;t legal, tax or financial advice: the lease itself decides who owns what, and for how long, so have an attorney review it, and confirm the property-tax treatment with your county.</em></p>

<h2>Sources</h2>

<ol>
<li>MARTA Board of Directors, Planning &amp; External Relations Committee, <a href="https://www.itsmarta.com/pdfs/PER_minutes_52912.pdf" target="_blank" rel="noopener">minutes of May 29, 2012</a>: briefing on existing transit-oriented development.</li>
<li>Atlanta Land Trust, <a href="https://atlantalandtrust.org/faqs/" target="_blank" rel="noopener">FAQs</a>.</li>
<li>Fannie Mae, Selling Guide, <a href="https://selling-guide.fanniemae.com/sel/b2-3-03/special-property-eligibility-and-underwriting-considerations-leasehold-estates" target="_blank" rel="noopener">B2-3-03, Special Property Eligibility and Underwriting Considerations: Leasehold Estates</a> (August 5, 2026).</li>
<li>Code of Federal Regulations, <a href="https://www.ecfr.gov/current/title-24/section-203.37" target="_blank" rel="noopener">24 CFR 203.37, Nature of title to realty</a> (FHA).</li>
<li>Erika K. Page, reel on ground leases and its caption (2026), her own words.</li>
</ol>
HTML,
    ],

    [
        // Researched around her reel DcKdzUiulyk with her photographer, Jonathan Martinez,
        // flying a drone over a listing of a little over an acre. NAR (2025 Profile via
        // Virginia REALTORS, Code of Ethics Article 12) and the FAA's Part 107 pages. Every
        // fact is listed with its source in tools/research/listing-photos-and-drones.md.
        'slug'           => 'listing-photos-and-drones',
        'title'          => 'Buyers scroll. Good photos stop the scroll.',
        'seo_title'      => 'Listing Photos and Drone Shots: Why They Sell Homes, “A True Picture”, and the FAA Rules',
        'seo_desc'       => 'Most home searches start online. Why professional listing photos matter, what “a true picture” means for editing, and the FAA rules for real estate drones.',
        'excerpt'        => 'Before anyone walks through your front door, they&rsquo;ve walked through your photos. Here&rsquo;s why professional listing photos matter, where editing has to stop, and what to ask before a drone goes up over your home.',
        'date'           => '2026-10-20',
        'updated'        => '2026-10-20',
        'author'         => 'Erika K. Page',
        'cat'            => 'selling',
        'tags'           => ['listing photos', 'real estate photography', 'drone photography', 'selling a home', 'Metro Atlanta', 'Part 107'],
        'cover'          => 'assets/photos/17/listing-photos-and-drones-cover.jpg',
        'cover_alt'      => 'Erika K. Page beside the headline: Buyers scroll. Good photos stop the scroll.',
        'cover_credit'   => 'Inset photo by <a href="https://www.pexels.com/@curtis-adams-1694007" rel="nofollow noopener" target="_blank">Curtis Adams</a> on <a href="https://www.pexels.com/" rel="nofollow noopener" target="_blank">Pexels</a>',
        'audio'          => 'assets/audio/listing-photos-and-drones.mp3',
        'audio_note'     => 'A podcast episode on this topic, in Erika’s AI voice. The sources are listed at the end of the article.',
        'guide'          => '',
        'product'        => '',
        'short'          => 'listing-photos',
        'audio_secs'     => 236,
        'published'      => true,
        'faq' => [
            [
                'q' => 'Why do listing photos matter so much?',
                'a' => 'Because the search usually starts online. In NAR&rsquo;s 2025 Profile of Home Buyers and Sellers, the home search most often began online, and buyers typically viewed a mix of online and in-person properties. Your photos are the first showing.',
            ],
            [
                'q' => 'Can listing photos be edited?',
                'a' => 'Editing can show a home at its best, but the REALTOR&reg; Code of Ethics requires &ldquo;a true picture&rdquo; in advertising, including images, and prohibits manipulating listing content in a way that produces a deceptive or misleading result.',
            ],
            [
                'q' => 'Who can fly a drone over my home for the listing?',
                'a' => 'Flying a drone for work or business falls under the FAA&rsquo;s Part 107 rules. The pilot needs a Remote Pilot Certificate, and the drone has to be registered and comply with Remote ID. Ask whoever flies it whether they hold the certificate.',
            ],
        ],
        'body' => <<<'HTML'
<p>Meet the man behind every photo you&rsquo;ve ever seen on my listings: Jonathan Martinez. He&rsquo;s camera shy, which is very funny for a man who points a camera at things for a living.</p>

<p>I pulled up to a listing that&rsquo;s a little over an acre, and I hadn&rsquo;t ordered drone. He had it in the trunk anyway, because he just has his stuff with him all the time. That property was over an acre, and I wanted to show it.</p>

<p>Buyers scroll. Good photos stop the scroll. Let me explain why that matters when you sell.</p>

<h2>Your photos are your first showing</h2>

<p>The National Association of REALTORS&reg; surveys recent buyers and sellers every year. Its 2025 Profile of Home Buyers and Sellers covers people who bought or sold between July 2024 and June 2025. <a href="https://www.nar.realtor/research-and-statistics/research-reports/highlights-from-the-profile-of-home-buyers-and-sellers" target="_blank" rel="noopener">[1]</a></p>

<p>Most often, the home search began online. The median search took 10 weeks, and buyers typically viewed a mix of online and in-person properties. Real estate agents were still ranked the most useful source of information, by 85% of recent buyers. <a href="https://virginiarealtors.org/2025/12/08/key-takeaways-from-nars-2025-profile-of-home-buyers-and-sellers" target="_blank" rel="noopener">[2]</a></p>

<p>So before anyone walks through your front door, they&rsquo;ve walked through your photos. If the photos don&rsquo;t stop them, they keep scrolling.</p>

<h2>What professional photos get you</h2>

<p>Here&rsquo;s what that gets you:</p>

<ul>
<li>professional, beautiful photos;</li>
<li>fast delivery;</li>
<li>properties that look amazing;</li>
<li>under contract faster.</li>
</ul>

<p>That last one isn&rsquo;t a coincidence. Jonathan edits the photos, and he makes our properties look amazing, which is part of the reason why we can get them under contract so fast.</p>

[[img:assets/photos/17/b29-drone-over-house.jpg|A camera drone hovering in front of a house|Photo by <a href="https://www.pexels.com/@matthew-529520" rel="nofollow noopener" target="_blank">Matthew</a> on <a href="https://www.pexels.com/" rel="nofollow noopener" target="_blank">Pexels</a>]]

<h2>Now here&rsquo;s the important part: edited is not the same as altered</h2>

<p>Editing makes a home look its best. It can&rsquo;t show a home that isn&rsquo;t there.</p>

<p>The REALTOR&reg; Code of Ethics says REALTORS&reg; &ldquo;shall present a true picture in their advertising, marketing, and other representations.&rdquo; That obligation includes images, and it prohibits &ldquo;manipulating&hellip; listing and other content in any way that produces a deceptive or misleading result.&rdquo; <a href="https://www.nar.realtor/about-nar/governing-documents/code-of-ethics/2025-code-of-ethics-standards-of-practice" target="_blank" rel="noopener">[3]</a></p>

<p>That protects you too: what a buyer sees online should be what they find at the door.</p>

<h2>When a drone earns its place</h2>

<p>On a lot over an acre, the land is part of what you&rsquo;re selling. That&rsquo;s why I wanted it shown, and that&rsquo;s what the drone does.</p>

<p>There are rules, though. A small drone under 55 pounds flown for work or business falls under the FAA&rsquo;s Part 107 rules. <a href="https://www.faa.gov/uas/commercial_operators" target="_blank" rel="noopener">[4]</a> To fly under Part 107, the pilot must hold a <strong>Remote Pilot Certificate</strong> from the FAA. That means being at least 16, passing the aeronautical knowledge exam, and completing recurrent training every 24 calendar months. <a href="https://www.faa.gov/uas/commercial_operators/become_a_drone_pilot" target="_blank" rel="noopener">[5]</a></p>

<p>The drone itself has to be registered, and a drone registered only for recreation can&rsquo;t be flown under Part 107. Registered drones have to comply with Remote ID. <a href="https://www.faa.gov/uas/getting_started/register_drone" target="_blank" rel="noopener">[6]</a></p>

<p>So when you interview a listing agent, ask who takes the photos, and if there&rsquo;s a drone, whether the pilot holds a Remote Pilot Certificate.</p>

<h2>What this means when you sell</h2>

<p>If you want the whole thing done chef&rsquo;s-kiss perfect when you sell, that&rsquo;s what we do. <a href="/contact">Let&rsquo;s talk about your home</a>, or start with <a href="/home-value">what it&rsquo;s worth</a>.</p>

<p>So that&rsquo;s listing photos, explained.</p>

<p><em>This article explains how listing photography and drone rules generally work. It isn&rsquo;t legal advice: rules change, so check the FAA&rsquo;s pages before any drone flight.</em></p>

<h2>Sources</h2>

<ol>
<li>National Association of REALTORS&reg;, <a href="https://www.nar.realtor/research-and-statistics/research-reports/highlights-from-the-profile-of-home-buyers-and-sellers" target="_blank" rel="noopener">Highlights From the Profile of Home Buyers and Sellers</a> (2025).</li>
<li>Virginia REALTORS&reg;, <a href="https://virginiarealtors.org/2025/12/08/key-takeaways-from-nars-2025-profile-of-home-buyers-and-sellers" target="_blank" rel="noopener">Key Takeaways from NAR&rsquo;s 2025 Profile of Home Buyers and Sellers</a> (December 8, 2025).</li>
<li>National Association of REALTORS&reg;, <a href="https://www.nar.realtor/about-nar/governing-documents/code-of-ethics/2025-code-of-ethics-standards-of-practice" target="_blank" rel="noopener">2025 Code of Ethics and Standards of Practice</a>, Article 12.</li>
<li>Federal Aviation Administration, <a href="https://www.faa.gov/uas/commercial_operators" target="_blank" rel="noopener">Certificated Remote Pilots including Commercial Operators</a>.</li>
<li>Federal Aviation Administration, <a href="https://www.faa.gov/uas/commercial_operators/become_a_drone_pilot" target="_blank" rel="noopener">Become a Certificated Remote Pilot</a>.</li>
<li>Federal Aviation Administration, <a href="https://www.faa.gov/uas/getting_started/register_drone" target="_blank" rel="noopener">How to Register Your Drone</a>.</li>
<li>Erika K. Page, reel with her photographer and its caption (2026), her own words.</li>
</ol>
HTML,
    ],

    [
        // Researched around her closing-day reel DbXJNqdPo6y ("a first-time homebuyer who
        // scored an incredible foreclosure deal"). HUD facts from HUD Homestore and its FAQ;
        // Fannie Mae HomePath left out (unreadable). Every fact is listed with its source in
        // tools/research/buying-a-hud-home.md.
        'slug'           => 'buying-a-hud-home',
        'title'          => 'Your first home could be a foreclosure. Here’s how HUD homes work.',
        'seo_title'      => 'Buying a HUD Home as a First-Time Buyer: Owner-Occupant Bidding, Repair Codes and Good Neighbor Next Door',
        'seo_desc'       => 'HUD homes give owner-occupants a head start on investors. How bidding works, what IN, IE and UI mean for your loan, and the 50% Good Neighbor discount.',
        'excerpt'        => 'I handed keys to a first-time buyer who scored an incredible foreclosure deal. One kind of foreclosure is built to give people like her a head start: HUD homes. Here&rsquo;s how the bidding works, what the repair codes mean for your loan, and who gets 50% off.',
        'date'           => '2026-10-19',
        'updated'        => '2026-10-19',
        'author'         => 'Erika K. Page',
        'cat'            => 'buying',
        'tags'           => ['HUD homes', 'foreclosure', 'first-time buyer', 'FHA', 'Good Neighbor Next Door', 'Metro Atlanta'],
        'cover'          => 'assets/photos/17/buying-a-hud-home-cover.jpg',
        'cover_alt'      => 'Erika K. Page beside the headline: Your first home could be a foreclosure. Here&rsquo;s how HUD homes work.',
        'cover_credit'   => 'Inset photo by <a href="https://www.pexels.com/@gustavo-fring" rel="nofollow noopener" target="_blank">Gustavo Fring</a> on <a href="https://www.pexels.com/" rel="nofollow noopener" target="_blank">Pexels</a>',
        'audio'          => 'assets/audio/buying-a-hud-home.mp3',
        'audio_note'     => 'A podcast episode on this topic, in Erika’s AI voice. The sources are listed at the end of the article.',
        'guide'          => '',
        'product'        => '',
        'short'          => 'hud-homes',
        'audio_secs'     => 271,
        'published'      => true,
        'faq' => [
            [
                'q' => 'What is a HUD home?',
                'a' => 'A single-family home the Federal Housing Administration acquired through foreclosure, which HUD then sells. You can search them by state, city or ZIP code at HUDHomestore.gov.',
            ],
            [
                'q' => 'Do owner-occupants get priority on HUD homes?',
                'a' => 'Yes. HUD gives owner-occupants an Exclusive period with no investor bids: 15 days for insured homes and 5 days for uninsured ones. Investors can only bid in the Extended period that follows.',
            ],
            [
                'q' => 'What do IN, IE and UI mean on a HUD listing?',
                'a' => 'They tell you about FHA financing. IN: eligible for an FHA-insured loan as it is. IE: eligible with a repair escrow, for repairs estimated at no more than $10,000. UI: repairs estimated over $10,000, so not eligible for an FHA-insured loan unless a Section 203(k) loan can be arranged.',
            ],
            [
                'q' => 'What is Good Neighbor Next Door?',
                'a' => 'A HUD program for law enforcement officers, firefighters, emergency medical technicians and teachers: 50% off eligible homes in revitalization areas if you live there for three years. With an FHA-insured mortgage, the down payment is only $100.',
            ],
        ],
        'body' => <<<'HTML'
<p>A morning of negotiating offers. An afternoon handing over keys. That was one of my days, celebrating a first-time homebuyer who scored an incredible foreclosure deal.</p>

<p>You might think foreclosures are only for investors with cash. Some of them are built to give you a head start. Let me explain one kind: <strong>HUD homes</strong>.</p>

<h2>What a HUD home is</h2>

<p>When a home with an FHA-insured mortgage goes through foreclosure, the Federal Housing Administration can end up owning it, and HUD sells it. HUD describes these as single-family homes &ldquo;acquired by the Federal Housing Administration (FHA) as a result of foreclosure.&rdquo; <a href="https://www.hudhomestore.gov/" target="_blank" rel="noopener">[1]</a></p>

<p>You can search every listing by state, city or ZIP code at <a href="https://www.hudhomestore.gov/" target="_blank" rel="noopener">HUDHomestore.gov</a>. <a href="https://www.hudhomestore.gov/" target="_blank" rel="noopener">[1]</a></p>

<h2>Now here&rsquo;s the important part: you go before the investors</h2>

<p>HUD sorts its listings into periods, and the order matters. <a href="https://www.hudhomestore.gov/FAQ" target="_blank" rel="noopener">[2]</a></p>

<ul>
<li><strong>Exclusive period: no bidding for investors.</strong> 15 days for insured homes and 5 days for uninsured ones. This is your window.</li>
<li><strong>Lottery period (7 days).</strong> Uninsured homes start here, and so do insured single-unit homes in a revitalization area.</li>
<li><strong>Extended period.</strong> Up to 180 days, or open-ended. This is the only period in which investors can bid.</li>
</ul>

<p>To bid as an owner-occupant, you must not have bought a HUD home as an owner-occupant in the past two years. <a href="https://www.hudhomestore.gov/FAQ" target="_blank" rel="noopener">[2]</a></p>

<h2>You can&rsquo;t bid alone</h2>

<p>HUD only takes offers through a HUD-registered selling broker or agent. <a href="https://www.hudhomestore.gov/" target="_blank" rel="noopener">[1]</a> <a href="https://www.hudhomestore.gov/FAQ" target="_blank" rel="noopener">[2]</a> Your agent submits the bid for you, and you can follow its status under My Bids on HUDHomestore.gov. <a href="https://www.hudhomestore.gov/FAQ" target="_blank" rel="noopener">[2]</a></p>

[[img:assets/photos/17/b27-restoring-old-home-interior.jpg|A woman working on an old house mid-renovation, by a wooden staircase|Photo by <a href="https://www.pexels.com/@monica" rel="nofollow noopener" target="_blank">Monica Silvestre</a> on <a href="https://www.pexels.com/" rel="nofollow noopener" target="_blank">Pexels</a>]]

<h2>Read the repair code before you fall in love</h2>

<p>Every HUD listing tells you how it can be financed, and that tells you a lot about its condition. <a href="https://www.hudhomestore.gov/FAQ" target="_blank" rel="noopener">[2]</a></p>

<ul>
<li><strong>IN, insurable:</strong> eligible for an FHA-insured loan in its current condition.</li>
<li><strong>IE, insurable with repair escrow:</strong> needs repairs estimated at no more than $10,000; an FHA loan works if your lender sets up a repair escrow at closing.</li>
<li><strong>UI, uninsured:</strong> needs repairs estimated at more than $10,000; not eligible for an FHA-insured loan unless a Section 203(k) loan can be arranged.</li>
</ul>

<p>Those are estimates. Get your own inspection, and get your financing lined up before you bid, because the loan you can use depends on that code.</p>

<h2>Teachers, officers, firefighters and EMTs: read this twice</h2>

<p>HUD&rsquo;s <strong>Good Neighbor Next Door</strong> program is for law enforcement officers, firefighters, emergency medical technicians and teachers. On eligible homes in revitalization areas, HUD gives a <strong>50% discount</strong> off the list price if you live in the home for three years. <a href="https://www.hudhomestore.gov/" target="_blank" rel="noopener">[1]</a></p>

<p>The discount applies to bids in the Lottery period, and if you qualify for an FHA-insured mortgage, your down payment is only $100 and you may finance your closing costs. <a href="https://www.hudhomestore.gov/FAQ" target="_blank" rel="noopener">[2]</a></p>

<h2>Strategy, patience and teamwork</h2>

<p>Behind every closing is a lot of strategy, patience, and teamwork. If you&rsquo;re a first-time buyer in Metro Atlanta and want to know whether a HUD home could be your way in, <a href="/contact">let&rsquo;s talk</a>. And if you already own, find out <a href="/home-value">what your home is worth</a>.</p>

<p>So that&rsquo;s buying a HUD home, explained.</p>

<p><em>This article explains how HUD&rsquo;s home sales generally work, from HUD&rsquo;s own site. Periods, programs and rules can change: check HUDHomestore.gov and each listing before you rely on them. It isn&rsquo;t legal or financial advice.</em></p>

<h2>Sources</h2>

<ol>
<li>U.S. Department of Housing and Urban Development, <a href="https://www.hudhomestore.gov/" target="_blank" rel="noopener">HUD Homestore</a>.</li>
<li>U.S. Department of Housing and Urban Development, <a href="https://www.hudhomestore.gov/FAQ" target="_blank" rel="noopener">HUD Homestore Help / FAQ</a>.</li>
<li>Erika K. Page, closing-day reel and caption (2026), her own words.</li>
</ol>
HTML,
    ],

    [
        // Built from her Atlanta-weekend videos and reels (erika-explains-perfect-
        // atlanta-weekend, atlanta-this-weekend, phipps-green-fridays, atlantas-rooftops;
        // reels DbMgF1chCj1, Da6I7GNh-2C, Dbe3bbbgb0c, DaoAvpmB1DG). Only year-round places
        // are kept; her dated picks are left out. Every fact is listed with its source in
        // tools/research/the-perfect-atlanta-weekend.md.
        'slug'           => 'the-perfect-atlanta-weekend',
        'title'          => 'Want the perfect Atlanta weekend? Here’s exactly where I’d go.',
        'seo_title'      => 'The Perfect Atlanta Weekend: Ponce City Market, the BeltLine, Buckhead and a Day in Madison',
        'seo_desc'       => 'An Atlanta agent&rsquo;s weekend: free Friday movies, the roof at Ponce City Market, Buckhead homes, brunch, the BeltLine, and a Sunday drive to Madison, GA.',
        'excerpt'        => 'Great food, beautiful homes, a little luxury and a Sunday drive. Here&rsquo;s the Atlanta weekend I&rsquo;d plan, from a free Friday movie to the roof at Ponce City Market, the BeltLine and historic Madison.',
        'date'           => '2026-10-16',
        'updated'        => '2026-10-16',
        'author'         => 'Erika K. Page',
        'cat'            => 'lifestyle',
        'tags'           => ['Atlanta weekend', 'things to do in Atlanta', 'Atlanta BeltLine', 'Ponce City Market', 'Buckhead', 'Madison GA'],
        'cover'          => 'assets/photos/17/the-perfect-atlanta-weekend-cover.jpg',
        'cover_alt'      => 'Erika K. Page beside the headline: Want the perfect Atlanta weekend? Here&rsquo;s exactly where I&rsquo;d go.',
        'cover_credit'   => 'Inset photo by <a href="https://www.pexels.com/@gryziu" rel="nofollow noopener" target="_blank">Dominik Gryzbon</a> on <a href="https://www.pexels.com/" rel="nofollow noopener" target="_blank">Pexels</a>',
        'audio'          => 'assets/audio/the-perfect-atlanta-weekend.mp3',
        'audio_note'     => 'A podcast episode on this topic, in Erika’s AI voice. The sources are listed at the end of the article.',
        'guide'          => '',
        'product'        => '',
        'short'          => 'weekend',
        'audio_secs'     => 232,
        'published'      => true,
        'faq' => [
            [
                'q' => 'How long is the Atlanta BeltLine?',
                'a' => 'The Atlanta Beltline describes itself as a 22-mile loop of trails, parks and future transit connecting 45 neighborhoods, with over 16 miles of urban trails open to explore.',
            ],
            [
                'q' => 'Is Ponce City Market on the BeltLine?',
                'a' => 'Yes. Ponce City Market describes itself as &ldquo;10 steps from the beltline,&rdquo; and it has a rooftop, The Roof, with skyline views.',
            ],
            [
                'q' => 'How far is Madison, Georgia from Atlanta?',
                'a' => 'Madison&rsquo;s visitors bureau puts it &ldquo;just an hour east of Atlanta,&rdquo; about 60 miles, and calls it home to one of Georgia&rsquo;s largest historic districts.',
            ],
        ],
        'body' => <<<'HTML'
<p>Want the perfect Atlanta weekend? Let me explain.</p>

<p>If I were planning my weekend in Atlanta, here&rsquo;s exactly where I&rsquo;d go if I wanted great food, fun experiences, and a little luxury. I&rsquo;ve made a few videos about this; here it is in one place.</p>

<h2>Friday night: a movie, or a rooftop</h2>

<p><strong>A free movie at Phipps Plaza.</strong> Atlanta, did you know this was free? In my video I talked about Phipps Plaza&rsquo;s Screen on the Green: free movies on Friday nights, out on the green. There are chairs and Wi-Fi, you can bring a blanket or a chair, and there&rsquo;s free parking underneath. I said I thought it ran all the way through October, around 6 p.m. Check Phipps Plaza&rsquo;s current schedule before you go.</p>

<p><strong>Or go up.</strong> Luxury starts with standards. Sometimes it&rsquo;s choosing a rooftop instead of sitting at home, and Lord knows we have plenty of rooftops in Atlanta. It&rsquo;s enjoying one great cocktail instead of three average ones. The roof at Ponce City Market is one of my favorite places to watch the sun go down, with skyline views. <a href="https://poncecitymarket.com/" target="_blank" rel="noopener">[2]</a></p>

<h2>Saturday morning: tour a few Buckhead homes</h2>

<p>I am in real estate, after all. On Saturday morning I&rsquo;m touring a few luxury homes in Buckhead. Even if you&rsquo;re not buying, it&rsquo;s one of my favorite ways to get inspired by beautiful architecture, gorgeous landscape, and creative design.</p>

<p>You don&rsquo;t have to be buying a house to appreciate beautiful architecture and dream a little bigger.</p>

[[img:assets/photos/17/b25-atlanta-graffiti-underpass.jpg|A graffiti-covered underpass in Atlanta|Photo by <a href="https://www.pexels.com/@msuatgunerli" rel="nofollow noopener" target="_blank">Mehmet Suat Gunerli</a> on <a href="https://www.pexels.com/" rel="nofollow noopener" target="_blank">Pexels</a>]]

<h2>Saturday afternoon: brunch, then the BeltLine</h2>

<p>After that, it&rsquo;s brunch at Ponce City Market, followed by a walk along the Atlanta BeltLine. Great food, local art, amazing people-watching, and some of the best spots in the city to create content.</p>

<p>The two go together. Ponce City Market says it&rsquo;s &ldquo;10 steps from the beltline.&rdquo; <a href="https://poncecitymarket.com/" target="_blank" rel="noopener">[2]</a> And the BeltLine itself is a 22-mile loop of trails, parks and future transit connecting 45 neighborhoods, with over 16 miles of trails that link up neighborhoods, shops, parks and public art. <a href="https://beltline.org/visit/" target="_blank" rel="noopener">[1]</a> Wear comfortable shoes.</p>

<h2>Saturday night: the skyline</h2>

<p>Then I&rsquo;d end the evening with rooftop cocktails overlooking the Atlanta skyline. There&rsquo;s just something so beautiful and special about the view, and it reminds you how amazing our city really is.</p>

<h2>Sunday: slow down in Madison</h2>

<p>Sunday, I&rsquo;d slow things down with a drive to Madison, Georgia. Think charming boutiques, historic homes, great restaurants, and a relaxing escape that&rsquo;s less than two hours from Atlanta.</p>

<p>It&rsquo;s actually closer than that. Madison&rsquo;s visitors bureau calls it &ldquo;just an hour east of Atlanta,&rdquo; about 60 miles, and says it has one of Georgia&rsquo;s largest historic districts. <a href="https://www.visitmadisonga.com/" target="_blank" rel="noopener">[3]</a></p>

<h2>Why a real estate agent cares about your weekend</h2>

<p>On Erika Explains, we talk about real estate, wealth, and the lifestyle that makes it all worth it. A weekend like this is the lifestyle part.</p>

<p>If one of these weekends has you thinking about living closer to it, <a href="/contact">let&rsquo;s talk</a>. And if you already own and want to know what your home is worth, <a href="/home-value">start here</a>.</p>

<p>So that&rsquo;s the perfect Atlanta weekend, explained.</p>

<p><em>Places, hours and events change. Check each venue&rsquo;s own site before you go.</em></p>

<h2>Sources</h2>

<ol>
<li>Atlanta Beltline, <a href="https://beltline.org/visit/" target="_blank" rel="noopener">Visit</a>.</li>
<li><a href="https://poncecitymarket.com/" target="_blank" rel="noopener">Ponce City Market</a>.</li>
<li><a href="https://www.visitmadisonga.com/" target="_blank" rel="noopener">Visit Madison GA</a>.</li>
<li>Erika K. Page, her Atlanta-weekend videos and reels (2026), her own words.</li>
</ol>
HTML,
    ],

    [
        // Researched around her four-part Home Depot "Path to Pro" series
        // (intake/transcripts/video/home-depot-part-1, -part-2-v2, -part-3,
        // trades-vs-4-year-college). Her pay figure is quoted as hers, with BLS medians
        // beside it. Every fact is listed with its source in
        // tools/research/not-everyone-needs-a-degree.md.
        'slug'           => 'not-everyone-needs-a-degree',
        'title'          => 'Not everyone needs a degree. Some people need a trade.',
        'seo_title'      => 'Not Everyone Needs a Degree: Free Trades Training, What Trades Pay, and Georgia&rsquo;s HOPE Career Grant',
        'seo_desc'       => 'Home Depot&rsquo;s free Path to Pro training, what electricians, plumbers and HVAC techs really earn, and how Georgia&rsquo;s HOPE Career Grant can cover tuition.',
        'excerpt'        => 'College is great for the right person, but it isn&rsquo;t the only way. Here&rsquo;s the free trades training Home Depot runs, what electricians, plumbers and HVAC techs actually earn, and how Georgia can cover your technical college tuition.',
        'date'           => '2026-10-15',
        'updated'        => '2026-10-15',
        'author'         => 'Erika K. Page',
        'cat'            => 'lifestyle',
        'tags'           => ['skilled trades', 'Path to Pro', 'HOPE Career Grant', 'careers', 'wealth', 'Georgia'],
        'cover'          => 'assets/photos/17/not-everyone-needs-a-degree-cover.jpg',
        'cover_alt'      => 'Erika K. Page beside the headline: Not everyone needs a degree. Some people need a trade.',
        'cover_credit'   => 'Inset photo by <a href="https://www.pexels.com/@kathleen-austin-kuhn-2152973960" rel="nofollow noopener" target="_blank">Kathleen Austin Kuhn</a> on <a href="https://www.pexels.com/" rel="nofollow noopener" target="_blank">Pexels</a>',
        'audio'          => 'assets/audio/not-everyone-needs-a-degree.mp3',
        'audio_note'     => 'A podcast episode on this topic, in Erika’s AI voice. The sources are listed at the end of the article.',
        'guide'          => '',
        'product'        => '',
        'short'          => 'trades',
        'audio_secs'     => 364,
        'published'      => true,
        'faq' => [
            [
                'q' => 'Is Home Depot&rsquo;s Path to Pro training really free?',
                'a' => 'Home Depot describes its online Skills Program as free, and says more than 200,000 people have graduated from it. It also runs the Path to Pro Network, a database of nearly 165,000 candidates looking for work in the skilled trades.',
            ],
            [
                'q' => 'How much do electricians, plumbers and HVAC technicians make?',
                'a' => 'According to the Bureau of Labor Statistics, median pay in May 2025 was $63,190 for electricians, $63,800 for plumbers, pipefitters and steamfitters, and $61,010 for HVAC mechanics and installers. The top 10% earned more than about $95,000 to $108,000.',
            ],
            [
                'q' => 'Can Georgia pay for trade school?',
                'a' => 'Georgia&rsquo;s HOPE Career Grant can cover 100% of tuition at a Technical College System of Georgia college for HOPE Grant-qualified students in certain high-demand fields. Ask your college whether your program is on the current list.',
            ],
            [
                'q' => 'Do I need a license to work in the trades?',
                'a' => 'Often, yes. The BLS says most states require electricians to pass a test and be licensed, most states and some localities license plumbers, and nearly all HVAC technicians need EPA certification to handle refrigerant.',
            ],
        ],
        'body' => <<<'HTML'
<p>They say college is the only way. What they forget to mention is that some people graduate from college with a lot of debt and no job.</p>

<p>I made a series of videos about this, and one line sums it up: <strong>&ldquo;Not everyone needs a degree. Some people need skills, certification, and access to real jobs.&rdquo;</strong> College is great for the right person. But it&rsquo;s not the only way, and too many people were never shown another one.</p>

<p>Let me explain the path I talked about, what the trades actually pay, and how Georgia can help you pay for the training.</p>

<h2>The Home Depot program I told you about</h2>

<p>It&rsquo;s called <strong>Path to Pro</strong>, and it&rsquo;s run by The Home Depot, whose Store Support Center is right here in Atlanta. <a href="https://corporate.homedepot.com/page/about-us" target="_blank" rel="noopener">[3]</a> In my videos I described it as &ldquo;free trades training, free certifications, and free connections to hiring.&rdquo; Here&rsquo;s how Home Depot itself describes it today:</p>

<ul>
<li><strong>A free online Skills Program.</strong> More than 200,000 people have graduated from it. <a href="https://corporate.homedepot.com/node/1957" target="_blank" rel="noopener">[1]</a> <a href="https://corporate.homedepot.com/node/2024" target="_blank" rel="noopener">[2]</a></li>
<li><strong>The Path to Pro Network,</strong> &ldquo;a database of nearly 165,000 candidates seeking employment in the skilled trades.&rdquo; <a href="https://corporate.homedepot.com/node/1957" target="_blank" rel="noopener">[1]</a></li>
<li><strong>Certification through nonprofit partners,</strong> with more than 70,000 people certified. <a href="https://corporate.homedepot.com/node/1957" target="_blank" rel="noopener">[1]</a> <a href="https://corporate.homedepot.com/node/2024" target="_blank" rel="noopener">[2]</a></li>
<li><strong>Who it&rsquo;s for:</strong> Home Depot says it has given free training to separating military, youth, career changers and others, and it runs a free curriculum called Skills Explorers for young people ages 11 to 17. <a href="https://corporate.homedepot.com/node/2024" target="_blank" rel="noopener">[2]</a></li>
</ul>

<p>And here&rsquo;s a story from my own travels. While I was in the Atlanta airport, I met a gentleman who worked for an engineering company. He was a mechanical engineer, and he told me straight up that they recruit from graduates of the Home Depot Path to Pro program.</p>

<h2>Now here&rsquo;s the important part: what the trades pay</h2>

<p>In one of my videos I said skilled trades can earn seventy-five thousand to one hundred and fifty thousand a year, with many hitting six figures once they&rsquo;re independent. That&rsquo;s the upside. Here&rsquo;s what the Bureau of Labor Statistics says about the typical worker, as of May 2025:</p>

<ul>
<li><strong>Electricians:</strong> median pay $63,190. The top 10% earned more than $108,510. <a href="https://www.bls.gov/ooh/construction-and-extraction/electricians.htm" target="_blank" rel="noopener">[4]</a></li>
<li><strong>Plumbers, pipefitters and steamfitters:</strong> median pay $63,800. The top 10% earned more than $108,420. <a href="https://www.bls.gov/ooh/construction-and-extraction/plumbers-pipefitters-and-steamfitters.htm" target="_blank" rel="noopener">[5]</a></li>
<li><strong>HVAC mechanics and installers:</strong> median pay $61,010. The top 10% earned more than $95,210. <a href="https://www.bls.gov/ooh/installation-maintenance-and-repair/heating-air-conditioning-and-refrigeration-mechanics-and-installers.htm" target="_blank" rel="noopener">[6]</a></li>
</ul>

<p>Median means half earn more and half earn less. So six figures is real, but it&rsquo;s the top of the field, and in my videos I tied it to people who go on to run their own business.</p>

<p>The work is there, too. The BLS projects about 72,700 openings a year for electricians, 42,000 for plumbers and 40,600 for HVAC technicians over the 2025 to 2035 decade. <a href="https://www.bls.gov/ooh/construction-and-extraction/electricians.htm" target="_blank" rel="noopener">[4]</a> <a href="https://www.bls.gov/ooh/construction-and-extraction/plumbers-pipefitters-and-steamfitters.htm" target="_blank" rel="noopener">[5]</a> <a href="https://www.bls.gov/ooh/installation-maintenance-and-repair/heating-air-conditioning-and-refrigeration-mechanics-and-installers.htm" target="_blank" rel="noopener">[6]</a> And Home Depot says more than 4.1 million skilled trades jobs are expected to open over the next decade. <a href="https://corporate.homedepot.com/node/2024" target="_blank" rel="noopener">[2]</a></p>

[[img:assets/photos/17/b23-electrician-stripping-wire.jpg|An electrician in blue coveralls stripping a wire on a ladder|Photo by <a href="https://www.pexels.com/@audy-of-course-306413796" rel="nofollow noopener" target="_blank">Audy of Course</a> on <a href="https://www.pexels.com/" rel="nofollow noopener" target="_blank">Pexels</a>]]

<h2>How you actually get in</h2>

<ul>
<li><strong>Electricians and plumbers</strong> mostly learn through a 4- or 5-year apprenticeship, which is paid on-the-job training. <a href="https://www.bls.gov/ooh/construction-and-extraction/electricians.htm" target="_blank" rel="noopener">[4]</a> <a href="https://www.bls.gov/ooh/construction-and-extraction/plumbers-pipefitters-and-steamfitters.htm" target="_blank" rel="noopener">[5]</a></li>
<li><strong>HVAC technicians</strong> usually complete a program at a technical school or community college that lasts 6 months to 2 years. <a href="https://www.bls.gov/ooh/installation-maintenance-and-repair/heating-air-conditioning-and-refrigeration-mechanics-and-installers.htm" target="_blank" rel="noopener">[6]</a></li>
<li><strong>Licenses matter.</strong> Most states require electricians to pass a test and be licensed, most states and some localities license plumbers, and nearly all HVAC technicians need EPA certification to handle refrigerant. <a href="https://www.bls.gov/ooh/construction-and-extraction/electricians.htm" target="_blank" rel="noopener">[4]</a> <a href="https://www.bls.gov/ooh/construction-and-extraction/plumbers-pipefitters-and-steamfitters.htm" target="_blank" rel="noopener">[5]</a> <a href="https://www.bls.gov/ooh/installation-maintenance-and-repair/heating-air-conditioning-and-refrigeration-mechanics-and-installers.htm" target="_blank" rel="noopener">[6]</a> Check Georgia&rsquo;s requirements before you take paid work.</li>
</ul>

<h2>Georgia can cover your tuition</h2>

<p>If you&rsquo;re in Georgia, look at the <strong>HOPE Career Grant</strong>. It&rsquo;s for HOPE Grant-qualified students who enroll in majors tied to industries where Georgia has more jobs than skilled workers to fill them. <a href="https://www.gafutures.org/hope-state-aid-programs/hope-zell-miller-grants/hope-career-grant/" target="_blank" rel="noopener">[7]</a> The Technical College System of Georgia says it can cover <strong>100% of your college tuition</strong> in those high-demand fields. <a href="https://www.tcsg.edu/career/" target="_blank" rel="noopener">[8]</a></p>

<p>The list of eligible programs changes, so ask the financial aid office at your technical college whether the trade you want is on it this year.</p>

<h2>Then build it into a business</h2>

<p>And you know, entrepreneurship is my jam. Once you&rsquo;ve learned, you&rsquo;re licensed and you&rsquo;re working, you can start your own business. In my video I said you can add a Google business page, a scheduling app, and now you&rsquo;ve got a business, not just a job.</p>

<p>In real estate, I work with these service providers on a constant basis.</p>

<h2>A note for parents</h2>

<p>I said this as a parent, and I&rsquo;ll say it again: a lot of parents could have a great conversation with their kids about the trades. If nobody ever told you this path existed, that&rsquo;s not your fault. But once you know, you can choose differently.</p>

<p>If you&rsquo;re building wealth, whether through a trade, a business or your first home, <a href="/contact">reach out to me</a>.</p>

<p>So that&rsquo;s the path into the trades, explained.</p>

<p><em>This article explains publicly available programs and data. Pay, programs and eligibility change; confirm details with the program, your technical college and Georgia&rsquo;s licensing authorities before you rely on them.</em></p>

<h2>Sources</h2>

<ol>
<li>The Home Depot, <a href="https://corporate.homedepot.com/node/1957" target="_blank" rel="noopener">The Home Depot Foundation Helps Revitalize &ldquo;Shop Class&rdquo; with Nationwide Grants</a> (March 10, 2026).</li>
<li>The Home Depot, <a href="https://corporate.homedepot.com/node/2024" target="_blank" rel="noopener">The Home Depot and The Home Depot Foundation Aim to Provide 15 Million Introductions to Skilled Trades Careers by 2032</a> (September 17, 2026).</li>
<li>The Home Depot, <a href="https://corporate.homedepot.com/page/about-us" target="_blank" rel="noopener">About us</a>.</li>
<li>U.S. Bureau of Labor Statistics, Occupational Outlook Handbook, <a href="https://www.bls.gov/ooh/construction-and-extraction/electricians.htm" target="_blank" rel="noopener">Electricians</a> (May 2025 wages; 2025&ndash;35 projections).</li>
<li>U.S. Bureau of Labor Statistics, Occupational Outlook Handbook, <a href="https://www.bls.gov/ooh/construction-and-extraction/plumbers-pipefitters-and-steamfitters.htm" target="_blank" rel="noopener">Plumbers, Pipefitters, and Steamfitters</a>.</li>
<li>U.S. Bureau of Labor Statistics, Occupational Outlook Handbook, <a href="https://www.bls.gov/ooh/installation-maintenance-and-repair/heating-air-conditioning-and-refrigeration-mechanics-and-installers.htm" target="_blank" rel="noopener">Heating, Air Conditioning, and Refrigeration Mechanics and Installers</a>.</li>
<li>Georgia Student Finance Commission (GAfutures), <a href="https://www.gafutures.org/hope-state-aid-programs/hope-zell-miller-grants/hope-career-grant/" target="_blank" rel="noopener">HOPE Career Grant</a>.</li>
<li>Technical College System of Georgia, <a href="https://www.tcsg.edu/career/" target="_blank" rel="noopener">Career</a> (&ldquo;Free Tuition&rdquo;).</li>
<li>Erika K. Page, her Home Depot &ldquo;Path to Pro&rdquo; series (parts 1&ndash;3) and &ldquo;Trades vs. 4-year college&rdquo; video, her own words.</li>
</ol>
HTML,
    ],

    [
        // Researched around her video "Banning Wall Street homebuying"
        // (intake/transcripts/video/banning-wall-street-homebuying, early 2026). What she
        // described as a proposal became law on 11 July 2026 (H.R. 6644, Sec. 1001); the
        // purchase ban starts 7 January 2027. Every fact is listed with its source in
        // tools/research/wall-street-homebuying-ban-explained.md.
        'slug'           => 'wall-street-homebuying-ban-explained',
        'title'          => 'The Wall Street homebuying ban is law now. Here’s what it does.',
        'seo_title'      => 'The Wall Street Homebuying Ban, Explained: What the New Law Means for Metro Atlanta',
        'seo_desc'       => 'From January 7, 2027, investors with 350+ homes can&rsquo;t buy most single-family homes. What the law does, what it doesn&rsquo;t, and why Atlanta should care.',
        'excerpt'        => 'Earlier this year I told you Wall Street could be your next-door neighbor. Now there&rsquo;s a federal law about it. Starting January 7, 2027, the biggest investors can&rsquo;t buy most single-family homes. Here&rsquo;s what it does, what it doesn&rsquo;t, and why Metro Atlanta in particular should pay attention.',
        'date'           => '2026-10-14',
        'updated'        => '2026-10-14',
        'author'         => 'Erika K. Page',
        'cat'            => 'buying',
        'tags'           => ['institutional investors', 'housing law', 'first-time buyer', 'single-family rentals', 'Metro Atlanta', 'ROAD to Housing Act'],
        'cover'          => 'assets/photos/17/wall-street-homebuying-ban-explained-cover.jpg',
        'cover_alt'      => 'Erika K. Page beside the headline: The Wall Street homebuying ban is law now. Here&rsquo;s what it does.',
        'cover_credit'   => 'Inset photo by <a href="https://www.pexels.com/@loquellano" rel="nofollow noopener" target="_blank">Raphael Loquellano</a> on <a href="https://www.pexels.com/" rel="nofollow noopener" target="_blank">Pexels</a>',
        'audio'          => 'assets/audio/wall-street-homebuying-ban-explained.mp3',
        'audio_note'     => 'A podcast episode on this topic, in Erika’s AI voice. The sources are listed at the end of the article.',
        'guide'          => '',
        'product'        => '',
        'short'          => 'wall-street',
        'audio_secs'     => 370,
        'published'      => true,
        'faq' => [
            [
                'q' => 'Is the ban on Wall Street buying homes actually law?',
                'a' => 'Yes. It&rsquo;s Section 1001 of the 21st Century ROAD to Housing Act (H.R. 6644), which became law on July 11, 2026. The purchase ban takes effect 180 days after that, on January 7, 2027, and lasts 15 years.',
            ],
            [
                'q' => 'Who counts as a large institutional investor?',
                'a' => 'A for-profit company or fund that, alone or with others, controls 350 or more single-family homes. Governments aren&rsquo;t covered, and neither is a small landlord with a few rentals.',
            ],
            [
                'q' => 'Will big investors have to sell the homes they already own?',
                'a' => 'No. The law says nothing in it requires a large institutional investor to sell any single-family home it bought before the law was enacted.',
            ],
            [
                'q' => 'Are there exceptions?',
                'a' => 'Several, including build-to-rent communities, renovate-to-rent programs that put at least 15% of the price into repairs, homeownership programs that report rent to the credit bureaus and give renters a first look, foreclosures, 55+ communities, and for two years, purchases from smaller investors.',
            ],
        ],
        'body' => <<<'HTML'
<p>Did you know that Wall Street could be your next-door neighbor? I asked that in a video earlier this year, when the President announced he was moving to ban large institutional investors from buying single-family homes.</p>

<p>Well, it isn&rsquo;t just an announcement anymore. It&rsquo;s a federal law. Let me explain what it does, what it doesn&rsquo;t, and why Metro Atlanta in particular should pay attention.</p>

<h2>How we got here</h2>

<p>On January 20, 2026, the President signed an executive order called &ldquo;Stopping Wall Street From Competing With Main Street Homebuyers.&rdquo; It set the policy that large institutional investors &ldquo;should not buy single-family homes,&rdquo; and it asked for a law to make it stick. <a href="https://www.govinfo.gov/content/pkg/FR-2026-01-23/html/2026-01424.htm" target="_blank" rel="noopener">[3]</a></p>

<p>Congress delivered. The 21st Century ROAD to Housing Act passed the Senate 85 to 5 and the House 358 to 32, and it became law on July 11, 2026. <a href="https://www.lw.com/en/insights/21st-century-road-to-housing-act-becomes-law" target="_blank" rel="noopener">[2]</a> The section that matters here is titled &ldquo;Homes are for people, not corporations.&rdquo; <a href="https://www.govinfo.gov/content/pkg/BILLS-119hr6644enr/html/BILLS-119hr6644enr.htm" target="_blank" rel="noopener">[1]</a></p>

<h2>What the law actually says</h2>

<p>The core of it is one sentence: <strong>&ldquo;No large institutional investor may purchase, or enter into a contract to directly or indirectly purchase, any single-family home.&rdquo;</strong> <a href="https://www.govinfo.gov/content/pkg/BILLS-119hr6644enr/html/BILLS-119hr6644enr.htm" target="_blank" rel="noopener">[1]</a></p>

<ul>
<li><strong>Who it covers.</strong> A for-profit company or fund that, alone or with others, controls 350 or more single-family homes. Governments aren&rsquo;t covered. <a href="https://www.govinfo.gov/content/pkg/BILLS-119hr6644enr/html/BILLS-119hr6644enr.htm" target="_blank" rel="noopener">[1]</a></li>
<li><strong>What a single-family home is.</strong> A building with two or fewer units, not counting manufactured homes. <a href="https://www.govinfo.gov/content/pkg/BILLS-119hr6644enr/html/BILLS-119hr6644enr.htm" target="_blank" rel="noopener">[1]</a></li>
<li><strong>When it starts.</strong> 180 days after it became law, which is January 7, 2027, and it runs for 15 years. <a href="https://www.govinfo.gov/content/pkg/BILLS-119hr6644enr/html/BILLS-119hr6644enr.htm" target="_blank" rel="noopener">[1]</a> <a href="https://www.lw.com/en/insights/21st-century-road-to-housing-act-becomes-law" target="_blank" rel="noopener">[2]</a></li>
<li><strong>The teeth.</strong> A civil penalty of up to $1,000,000 per violation, or three times the purchase price, whichever is greater. <a href="https://www.govinfo.gov/content/pkg/BILLS-119hr6644enr/html/BILLS-119hr6644enr.htm" target="_blank" rel="noopener">[1]</a></li>
</ul>

<h2>Now here&rsquo;s the important part: what it doesn&rsquo;t do</h2>

<p>Headlines called it a ban, and for buying existing homes, it mostly is. But read the details before you decide what it means for you.</p>

<ul>
<li><strong>Nobody has to sell.</strong> The law says plainly that it doesn&rsquo;t require a large investor to sell any single-family home it already owned. <a href="https://www.govinfo.gov/content/pkg/BILLS-119hr6644enr/html/BILLS-119hr6644enr.htm" target="_blank" rel="noopener">[1]</a> The homes they own today stay theirs.</li>
<li><strong>They can still build.</strong> Build-to-rent communities, homes built new to be rented, are an exception. So are renovate-to-rent programs that fix homes that don&rsquo;t meet code and put at least 15% of the price into the work. <a href="https://www.govinfo.gov/content/pkg/BILLS-119hr6644enr/html/BILLS-119hr6644enr.htm" target="_blank" rel="noopener">[1]</a></li>
<li><strong>Rent-to-own programs can continue,</strong> if they report the renter&rsquo;s on-time rent to the credit bureaus (when the renter opts in), and some give the renter a right of first refusal and a 30-day &ldquo;first look.&rdquo; <a href="https://www.govinfo.gov/content/pkg/BILLS-119hr6644enr/html/BILLS-119hr6644enr.htm" target="_blank" rel="noopener">[1]</a></li>
<li><strong>Other exceptions</strong> cover foreclosures, communities for people 55 and older, and, for the first two years after it starts, buying from smaller investors. <a href="https://www.govinfo.gov/content/pkg/BILLS-119hr6644enr/html/BILLS-119hr6644enr.htm" target="_blank" rel="noopener">[1]</a></li>
<li><strong>Small landlords aren&rsquo;t touched.</strong> If you own a few rentals, you are nowhere near 350 homes.</li>
</ul>

[[img:assets/photos/17/b21-row-of-new-homes-by-a-pond.jpg|A row of new single-family homes reflected in a pond|Photo by <a href="https://www.pexels.com/@gentooexpressions" rel="nofollow noopener" target="_blank">TLK GentooExpressions</a> on <a href="https://www.pexels.com/" rel="nofollow noopener" target="_blank">Pexels</a>]]

<h2>Why Atlanta should care more than most</h2>

<p>Nationally, the big investors are a small slice: about 3 percent of single-family rentals, and less than half a percent of all single-family homes. <a href="https://www.urban.org/urban-wire/will-regulating-large-institutional-investors-actually-make-housing-more-affordable" target="_blank" rel="noopener">[6]</a></p>

<p>Metro Atlanta is different. In its 2024 report, the Government Accountability Office estimated that as of 2022, investors with over 1,000 homes held about <strong>25% of Atlanta&rsquo;s single-family rental market</strong>, the highest of the areas it looked at, ahead of Jacksonville and Charlotte. <a href="https://www.gao.gov/products/gao-24-106643" target="_blank" rel="noopener">[4]</a> <a href="https://nlihc.org/resource/gao-releases-report-institutional-investments-single-family-rental-housing" target="_blank" rel="noopener">[5]</a> So if this law changes anything anywhere, it&rsquo;s here.</p>

<h2>So will it help you buy a house?</h2>

<p>In my video I said that cutting out the big institutional buyers reduces competition, and &ldquo;that means fewer cash offers and also more breathing room for everyday buyers.&rdquo;</p>

<p>That&rsquo;s the hope. To be fair, not everybody agrees it will add much. The Urban Institute&rsquo;s Laurie Goodman wrote that limiting large investors &ldquo;may not actually add to the housing supply for first-time homebuyers,&rdquo; partly because many homes investors buy need repairs and are &ldquo;unlikely to be purchased by owner-occupants otherwise.&rdquo; <a href="https://www.urban.org/urban-wire/will-regulating-large-institutional-investors-actually-make-housing-more-affordable" target="_blank" rel="noopener">[6]</a></p>

<p>What the law itself guarantees is narrower: from January 7, 2027, one kind of buyer, the very largest, steps back from most existing homes. How much that matters depends on your price range and your neighborhood. That&rsquo;s a local question, not a national one.</p>

<h2>If you rent from a big investor</h2>

<p>The law also tells HUD to set up a toll-free number and a website, within 180 days, to help renters in homes owned by large institutional investors with disputes. <a href="https://www.govinfo.gov/content/pkg/BILLS-119hr6644enr/html/BILLS-119hr6644enr.htm" target="_blank" rel="noopener">[1]</a> And if your landlord offers a rent-to-own or homeownership program, ask whether your on-time rent is being reported to the credit bureaus. Under this law, that&rsquo;s part of how those programs qualify.</p>

<h2>What to do now</h2>

<p>If you&rsquo;re buying, don&rsquo;t wait for January to start. Get your financing lined up and know your numbers, because a law doesn&rsquo;t change what you can afford. If you&rsquo;re selling, know who&rsquo;s likely to be on the other side of your deal in 2027.</p>

<p>And like I said in my video, I&rsquo;ll pull local numbers and walk you through all of your options. <a href="/contact">Get with me here</a>, or if you own and want to know what your house is worth, <a href="/home-value">start here</a>.</p>

<p>So that&rsquo;s the Wall Street homebuying ban, explained.</p>

<p><em>This article explains what the law says. It isn&rsquo;t legal or financial advice, and regulations under it may add detail. Check the current rules before you rely on them.</em></p>

<h2>Sources</h2>

<ol>
<li>H.R. 6644, <a href="https://www.govinfo.gov/content/pkg/BILLS-119hr6644enr/html/BILLS-119hr6644enr.htm" target="_blank" rel="noopener">21st Century ROAD to Housing Act, enrolled bill</a>, Title X, Section 1001, &ldquo;Homes are for people, not corporations.&rdquo;</li>
<li>Latham &amp; Watkins, <a href="https://www.lw.com/en/insights/21st-century-road-to-housing-act-becomes-law" target="_blank" rel="noopener">21st Century ROAD to Housing Act Becomes Law</a> (July 14, 2026).</li>
<li>Executive Order 14376, <a href="https://www.govinfo.gov/content/pkg/FR-2026-01-23/html/2026-01424.htm" target="_blank" rel="noopener">Stopping Wall Street From Competing With Main Street Homebuyers</a>, Federal Register (January 23, 2026).</li>
<li>U.S. Government Accountability Office, <a href="https://www.gao.gov/products/gao-24-106643" target="_blank" rel="noopener">Rental Housing: Information on Institutional Investment in Single-Family Homes</a>, GAO-24-106643 (May 22, 2024).</li>
<li>National Low Income Housing Coalition, <a href="https://nlihc.org/resource/gao-releases-report-institutional-investments-single-family-rental-housing" target="_blank" rel="noopener">GAO Releases Report on Institutional Investments in Single-Family Rental Housing</a> (May 28, 2024).</li>
<li>Urban Institute, Laurie Goodman, <a href="https://www.urban.org/urban-wire/will-regulating-large-institutional-investors-actually-make-housing-more-affordable" target="_blank" rel="noopener">Will Regulating Large Institutional Investors Actually Make Housing More Affordable?</a> (January 26, 2026).</li>
<li>Erika K. Page, her &ldquo;Banning Wall Street homebuying&rdquo; video (2026), her own words.</li>
</ol>
HTML,
    ],

    [
        // NEXA / Andrew series #2 (October). Researched around her "Fannie Mae"
        // video (intake/transcripts/video/fannie-mae, November 2025), re-checked
        // against the current Selling Guide on 13 Oct 2026; her claims about Andrew
        // come only from "Drew's Video". Every fact is listed with its source in
        // tools/research/fannie-mae-dropped-the-620-minimum.md.
        'slug'           => 'fannie-mae-dropped-the-620-minimum',
        'title'          => 'No more 620 minimum? Here’s what really changed.',
        'seo_title'      => 'Fannie Mae Dropped the 620 Minimum Credit Score: What It Means for Home Buyers',
        'seo_desc'       => 'Fannie Mae no longer sets a minimum credit score for loans run through its DU system. What changed, what didn&rsquo;t, and how rent history can count.',
        'excerpt'        => 'Fannie Mae took away its 620 minimum credit score for loans run through its automated system. That&rsquo;s real, and it&rsquo;s good news. But it isn&rsquo;t a free pass. Here&rsquo;s what changed, what didn&rsquo;t, and how your rent history can now count.',
        'date'           => '2026-10-13',
        'updated'        => '2026-10-13',
        'author'         => 'Erika K. Page',
        'cat'            => 'buying',
        'tags'           => ['credit score', 'Fannie Mae', 'mortgage', 'first-time buyer', 'rent history', 'Metro Atlanta'],
        'cover'          => 'assets/photos/17/fannie-mae-dropped-the-620-minimum-cover.jpg',
        'cover_alt'      => 'Erika K. Page beside the headline: No more 620 minimum? Here&rsquo;s what really changed.',
        'cover_credit'   => 'Inset photo by <a href="https://www.pexels.com/@cottonbro" rel="nofollow noopener" target="_blank">cottonbro studio</a> on <a href="https://www.pexels.com/" rel="nofollow noopener" target="_blank">Pexels</a>',
        'audio'          => 'assets/audio/fannie-mae-dropped-the-620-minimum.mp3',
        'audio_note'     => 'A podcast episode on this topic, in Erika’s AI voice. The sources are listed at the end of the article.',
        'guide'          => '',
        'product'        => '',
        'short'          => 'credit-score',
        'audio_secs'     => 409,
        'published'      => true,
        'faq' => [
            [
                'q' => 'Did Fannie Mae really remove the 620 minimum credit score?',
                'a' => 'For loans run through its automated underwriting system, Desktop Underwriter (DU), yes. Fannie Mae&rsquo;s Selling Guide now says a minimum credit score is not required for DU loan casefiles; DU assesses creditworthiness through its own risk factors. Fannie Mae announced the change on November 5, 2025.',
            ],
            [
                'q' => 'Does that mean anyone can get a mortgage now?',
                'a' => 'No. DU still weighs your payment history, late payments, card balances, collections, inquiries, debt-to-income ratio, reserves and more. Manually underwritten loans still need 620 for a fixed rate and 640 for an adjustable rate, and government-backed loans Fannie Mae buys still need 620.',
            ],
            [
                'q' => 'Can my rent payments help me qualify?',
                'a' => 'They can. If at least one borrower has rented for 12 months or more at $300 a month or more, and has no mortgage on their credit report, a limited credit history or no score, DU can find the rent payments in 12 months of bank statement data and use them to help, never to hurt.',
            ],
            [
                'q' => 'How do I check my credit before I talk to a lender?',
                'a' => 'Get your free credit reports at AnnualCreditReport.com or by calling (877) 322-8228. If something is wrong, the CFPB says to dispute it in writing with the credit reporting company and with the company that reported it, and include copies of your proof.',
            ],
        ],
        'body' => <<<'HTML'
<p>Ever felt like your credit score was the final boss in the whole home buying game? That&rsquo;s how I opened a video back in November 2025, because Fannie Mae had just hit the reset.</p>

<p>Let me explain what changed, what didn&rsquo;t, and what you should do about it.</p>

<h2>What I said, and what the rule says now</h2>

<p>In that video I said: &ldquo;Starting November 2025, Fannie Mae is removing the 620 minimum credit score requirement.&rdquo; Almost a year later, I went back and checked it against Fannie Mae&rsquo;s own rule book, the Selling Guide. It holds up.</p>

<p>Fannie Mae announced the change on November 5, 2025. Today the Guide says it plainly: <strong>&ldquo;A minimum credit score is not required for DU loan casefiles.&rdquo;</strong> <a href="https://selling-guide.fanniemae.com/sel/b3-5.1-01/general-requirements-credit-scores" target="_blank" rel="noopener">[1]</a></p>

<p>DU is Desktop Underwriter, Fannie Mae&rsquo;s automated underwriting system. Instead of a cutoff, it &ldquo;will assess a borrower&rsquo;s creditworthiness&rdquo; by weighing a whole list of risk factors. In the Guide&rsquo;s own words, credit scores &ldquo;are not an integral part of DU&rsquo;s risk assessment because DU performs its own analysis of the credit report data.&rdquo; <a href="https://selling-guide.fanniemae.com/sel/b3-5.1-01/general-requirements-credit-scores" target="_blank" rel="noopener">[1]</a></p>

<p>And here&rsquo;s why that matters. I&rsquo;ve seen clients with all the assets in the world struggle just because of credit score. A single number used to close the door before anybody looked at the rest of the file.</p>

<h2>Now here&rsquo;s the important part: it isn&rsquo;t a free pass</h2>

<p>No minimum score is not the same as no standards. A few things didn&rsquo;t change at all:</p>

<ul>
<li><strong>Your scores still get pulled.</strong> Lenders still have to request scores from all three credit bureaus when they order your credit report. <a href="https://selling-guide.fanniemae.com/sel/b3-5.1-01/general-requirements-credit-scores" target="_blank" rel="noopener">[1]</a></li>
<li><strong>Manual underwriting still has a floor.</strong> If a loan is underwritten by hand instead of through DU, the minimum is still 620 for a fixed-rate loan and 640 for an adjustable-rate one. <a href="https://selling-guide.fanniemae.com/sel/b3-5.1-01/general-requirements-credit-scores" target="_blank" rel="noopener">[1]</a></li>
<li><strong>Government-backed loans are different.</strong> For FHA, VA and USDA loans that Fannie Mae buys, the Guide still lists a minimum representative score of 620. <a href="https://selling-guide.fanniemae.com/sel/b3-5.1-01/general-requirements-credit-scores" target="_blank" rel="noopener">[1]</a></li>
<li><strong>Fannie Mae doesn&rsquo;t lend you the money.</strong> It buys mortgages from lenders. <a href="https://www.fanniemae.com/about-us" target="_blank" rel="noopener">[4]</a> Your lender still makes the loan, and this change doesn&rsquo;t guarantee anybody a loan.</li>
</ul>

<h2>What DU actually looks at</h2>

<p>If the score isn&rsquo;t the gatekeeper, the rest of your credit story is. Fannie Mae publishes what DU weighs. <a href="https://selling-guide.fanniemae.com/sel/b3-2-03/risk-factors-evaluated-du" target="_blank" rel="noopener">[2]</a> Here&rsquo;s what I&rsquo;d want you to know:</p>

<ul>
<li><strong>Time is on your side.</strong> An older, established history helps. But a new credit history &ldquo;is not automatically considered a high credit risk,&rdquo; especially if you&rsquo;ve paid as agreed.</li>
<li><strong>Recent late payments weigh more.</strong> The Guide&rsquo;s own example: a 30-day late payment less than three months old is a higher risk than one from several years ago.</li>
<li><strong>Keep your card balances low.</strong> Low use of your revolving credit, or paying it off regularly, is considered lower risk.</li>
<li><strong>Medical collections don&rsquo;t count.</strong> They &ldquo;are not used in the DU risk assessment.&rdquo;</li>
<li><strong>Shopping for a mortgage is fine.</strong> Inquiries from different mortgage lenders in the same time frame aren&rsquo;t treated as multiple inquiries. Applying for lots of new credit is a different story.</li>
<li><strong>It&rsquo;s more than credit.</strong> DU also weighs your down payment and equity, your savings, your debt-to-income ratio, the property and the loan itself.</li>
</ul>

[[img:assets/photos/17/b19-magnifying-glass-on-credit-document.jpg|A magnifying glass held over a bank statement|Photo by <a href="https://www.pexels.com/@rdne" rel="nofollow noopener" target="_blank">RDNE Stock project</a> on <a href="https://www.pexels.com/" rel="nofollow noopener" target="_blank">Pexels</a>]]

<h2>Your rent can count</h2>

<p>In my video I said that if you&rsquo;ve got solid rental history, you might qualify even if your score isn&rsquo;t picture perfect. Here&rsquo;s how that actually works.</p>

<p>DU can use your rent payment history to <strong>positively</strong> supplement its credit assessment, when at least one borrower has been renting for at least 12 months, paying $300 a month or more, and has no mortgage on their credit report, a limited credit history, or no credit score. <a href="https://selling-guide.fanniemae.com/sel/b3-2-03/risk-factors-evaluated-du" target="_blank" rel="noopener">[2]</a></p>

<p>If your rent isn&rsquo;t on your credit report, DU can find it in 12 months of your bank statement data. Your lender enters the rent you pay, gets a verification report of your account, and confirms it&rsquo;s the account you pay rent from. <a href="https://selling-guide.fanniemae.com/sel/b3-2-03/risk-factors-evaluated-du" target="_blank" rel="noopener">[2]</a> So if you rent, pay it from a bank account in your name, the same way every month. That paper trail is working for you.</p>

<h2>And your utilities?</h2>

<p>I also said &ldquo;you pay your utilities on time.&rdquo; Here&rsquo;s where that fits. For a borrower <strong>without a credit score</strong>, Fannie Mae lets the lender build a nontraditional credit history, and utilities are on the list: electricity, gas, water, phone, TV and internet. So are things like car insurance, renter&rsquo;s insurance and cell phone payments. <a href="https://selling-guide.fanniemae.com/sel/b3-5.4-02/number-and-types-nontraditional-credit-references" target="_blank" rel="noopener">[3]</a></p>

<p>DU will say when that&rsquo;s needed, and then at least two credit references are required for each borrower. <a href="https://selling-guide.fanniemae.com/sel/b3-5.4-02/number-and-types-nontraditional-credit-references" target="_blank" rel="noopener">[3]</a> So keep those bills current. They can matter more than you think.</p>

<h2>Before you talk to a lender, do this</h2>

<ul>
<li><strong>Pull your reports.</strong> You can get free credit reports at <a href="https://www.annualcreditreport.com" target="_blank" rel="noopener">AnnualCreditReport.com</a> or by calling (877) 322-8228. <a href="https://www.consumerfinance.gov/ask-cfpb/how-do-i-get-a-free-copy-of-my-credit-reports-en-5/" target="_blank" rel="noopener">[6]</a></li>
<li><strong>Don&rsquo;t obsess over one number.</strong> The CFPB says it straight: &ldquo;You do not have just &lsquo;one&rsquo; credit score.&rdquo; <a href="https://www.consumerfinance.gov/ask-cfpb/what-is-a-credit-score-en-315/" target="_blank" rel="noopener">[5]</a></li>
<li><strong>Fix what&rsquo;s wrong.</strong> If something on your report is an error, explain in writing what&rsquo;s wrong and why, with copies of your proof, to the credit reporting company and to the company that reported it. <a href="https://www.consumerfinance.gov/ask-cfpb/how-do-i-dispute-an-error-on-my-credit-report-en-314/" target="_blank" rel="noopener">[7]</a></li>
<li><strong>Hold off on new credit.</strong> No new cards and no new car while you&rsquo;re getting ready to buy.</li>
</ul>

<h2>Where Andrew comes in</h2>

<p>This is exactly why I send my clients to my lender partner, Andrew Channell with NEXA. As I said in my video about him, he and his team have &ldquo;access to over 200 banks and wholesale lenders,&rdquo; and &ldquo;he won&rsquo;t even run your credit until he knows the numbers make sense.&rdquo; When the rules get more flexible, you want somebody who knows where that flexibility actually lives.</p>

<p>If your score has been the thing holding you back, don&rsquo;t decide for yourself that you can&rsquo;t buy. <a href="/contact">Get with me so you can get with Andrew.</a> And if you already own and you&rsquo;re wondering what your house is worth before your next move, <a href="/home-value">start here</a>.</p>

<p>So that&rsquo;s Fannie Mae&rsquo;s 620 change, explained.</p>

<p><em>This article explains how Fannie Mae&rsquo;s published guidelines generally work. It isn&rsquo;t a loan offer, a rate quote or financial advice, and it doesn&rsquo;t guarantee that anyone will qualify. Erika K. Page is a real estate agent with Axen Realty, not a lender. Andrew &ldquo;Drew&rdquo; Channell is a Senior Loan Officer, NMLS #1920676, with NEXA (company NMLS #1660690). You are free to choose any lender.</em></p>

<h2>Sources</h2>

<ol>
<li>Fannie Mae Selling Guide, <a href="https://selling-guide.fanniemae.com/sel/b3-5.1-01/general-requirements-credit-scores" target="_blank" rel="noopener">B3-5.1-01, General Requirements for Credit Scores</a> (April 22, 2026 version; lists Announcement SEL-2025-09 of November 5, 2025).</li>
<li>Fannie Mae Selling Guide, <a href="https://selling-guide.fanniemae.com/sel/b3-2-03/risk-factors-evaluated-du" target="_blank" rel="noopener">B3-2-03, Risk Factors Evaluated by DU</a> (February 5, 2025 version).</li>
<li>Fannie Mae Selling Guide, <a href="https://selling-guide.fanniemae.com/sel/b3-5.4-02/number-and-types-nontraditional-credit-references" target="_blank" rel="noopener">B3-5.4-02, Number and Types of Nontraditional Credit References</a> (February 4, 2026 version).</li>
<li>Fannie Mae, <a href="https://www.fanniemae.com/about-us" target="_blank" rel="noopener">About us</a>.</li>
<li>Consumer Financial Protection Bureau, <a href="https://www.consumerfinance.gov/ask-cfpb/what-is-a-credit-score-en-315/" target="_blank" rel="noopener">What is a credit score?</a> (reviewed September 2, 2026).</li>
<li>Consumer Financial Protection Bureau, <a href="https://www.consumerfinance.gov/ask-cfpb/how-do-i-get-a-free-copy-of-my-credit-reports-en-5/" target="_blank" rel="noopener">How do I get a free copy of my credit reports?</a> (reviewed August 28, 2023).</li>
<li>Consumer Financial Protection Bureau, <a href="https://www.consumerfinance.gov/ask-cfpb/how-do-i-dispute-an-error-on-my-credit-report-en-314/" target="_blank" rel="noopener">How do I dispute an error on my credit report?</a> (reviewed September 2, 2026).</li>
<li>Erika K. Page, her &ldquo;Fannie Mae&rdquo; video (November 2025) and &ldquo;Drew&rsquo;s Video&rdquo; (June 2026), her own words. Andrew &ldquo;Drew&rdquo; Channell&rsquo;s title and NMLS numbers as supplied from his business signature, October 2026.</li>
</ol>
HTML,
    ],

    [
        // Researched around her short reel "stairs become the enemy"
        // (intake/transcripts/video/stairs-hidden-enemy): her lines are quoted;
        // every fact is listed with its source in
        // tools/research/when-stairs-become-the-enemy.md. Her "five coming to
        // market" line is left out (a dated listing claim).
        'slug'           => 'when-stairs-become-the-enemy',
        'title'          => 'When stairs become the enemy.',
        'seo_title'      => 'When Stairs Become the Enemy: Making Your Home Safer, or Moving to One Level',
        'seo_desc'       => 'Fix the stairs you have, or move to a home without them? CDC fall-prevention steps, what to plan, and the tax rules worth knowing before you sell.',
        'excerpt'        => 'If you keep living and you get old enough, stairs become the enemy. Here&rsquo;s what the CDC says to fix first, how to think about moving to one level, and the tax rules worth knowing before you sell the family home.',
        'date'           => '2026-10-12',
        'updated'        => '2026-10-12',
        'author'         => 'Erika K. Page',
        'cat'            => 'lifestyle',
        'tags'           => ['aging in place', 'downsizing', 'single-level homes', 'fall prevention', 'selling a home', 'Metro Atlanta'],
        'cover'          => 'assets/photos/17/when-stairs-become-the-enemy-cover.jpg',
        'cover_alt'      => 'Erika K. Page beside the headline: When stairs become the enemy.',
        'cover_credit'   => 'Inset photo by <a href="https://www.pexels.com/@michaelgaultphotos" rel="nofollow noopener" target="_blank">Get Lost Mike</a> on <a href="https://www.pexels.com/" rel="nofollow noopener" target="_blank">Pexels</a>',
        'audio'          => 'assets/audio/when-stairs-become-the-enemy.mp3',
        'audio_note'     => 'A podcast episode on this topic, in Erika’s AI voice. The sources are listed at the end of the article.',
        'guide'          => '',
        'product'        => '',
        'short'          => 'stairs',
        'audio_secs'     => 279,
        'published'      => true,
        'faq' => [
            [
                'q' => 'How common are falls for older adults?',
                'a' => 'The CDC says more than one out of four older people falls each year, and less than half tell their doctor. Falls are the most common cause of traumatic brain injuries for older adults.',
            ],
            [
                'q' => 'How do I make the stairs in my home safer?',
                'a' => 'The CDC&rsquo;s home checklist says to keep objects off the stairs, fix loose or uneven steps, have a light and light switch at the top and bottom, and make sure handrails are on both sides and as long as the stairs.',
            ],
            [
                'q' => 'If I sell my two-story home, will I owe tax on the gain?',
                'a' => 'Maybe not. The IRS lets you exclude up to $250,000 of gain, or $500,000 on a joint return, if you owned the home and lived in it for at least two of the five years before the sale, with some limits. Talk to a tax professional about your situation.',
            ],
            [
                'q' => 'Are there property tax breaks for older homeowners in Georgia?',
                'a' => 'Georgia has state exemptions for homeowners 65 and older, and 62 and older for school taxes, with tight income limits, and the state Constitution lets counties enact their own local homestead exemptions. Ask your county tax commissioner what applies to you.',
            ],
        ],
        'body' => <<<'HTML'
<p>Who knew? Who knew that stairs would become the enemy?</p>

<p>You know, if you keep living and you get old enough, stairs become the enemy. I said that in a video, laughing. And in that same video I said we&rsquo;re listing more homes for people who need a home that doesn&rsquo;t have stairs.</p>

<p>So if the stairs in your house are starting to feel like a problem, you have two choices. Make the stairs you have safer, or move to a home without them. Let me explain both.</p>

<h2>Why it&rsquo;s worth taking seriously</h2>

<p>This isn&rsquo;t just about sore knees. According to the <a href="https://www.cdc.gov/falls/data-research/facts-stats/index.html" target="_blank" rel="noopener">CDC</a>:</p>

<ul>
<li><strong>more than one out of four</strong> older people falls each year, and less than half tell their doctor;</li>
<li>falls are the most common cause of traumatic brain injuries for older adults;</li>
<li>and there are about 4.5 million emergency department visits a year because older people fall.</li>
</ul>

<h2>Choice one: make the stairs you have safer</h2>

<p>The National Institute on Aging says to go through your home <a href="https://www.nia.nih.gov/health/aging-place/aging-place-growing-older-home" target="_blank" rel="noopener">room by room</a> and first correct any immediate dangers, &ldquo;such as loose stair railings and poor lighting,&rdquo; then keep re-checking as your needs change.</p>

<p>The CDC&rsquo;s home fall-prevention checklist, <a href="https://www.cdc.gov/steadi/pdf/patient/customizable/CheckforSafety-Brochure-Final-Customizable-508.pdf" target="_blank" rel="noopener"><em>Check for Safety</em></a>, gets specific about stairs:</p>

<ul>
<li>keep papers, shoes, books and other objects <strong>off the stairs</strong>;</li>
<li>fix loose or uneven steps;</li>
<li>have a <strong>light and a light switch at the top and bottom</strong> of the stairs. You can even get light switches that glow;</li>
<li>make sure carpet is firmly attached to every step, or remove it and use non-slip rubber treads;</li>
<li>and make sure <strong>handrails are on both sides</strong> of the stairs, and as long as the stairs.</li>
</ul>

<p>The same checklist goes past the stairs, too: a lamp you can reach from the bed, a nightlight on the path from the bed to the bathroom, and grab bars next to and inside the tub and next to the toilet.</p>

[[img:assets/photos/17/b17-staircase-handrail-wall-light.jpg|An indoor staircase with a wooden handrail on one side and a light on the wall|Photo by <a href="https://www.pexels.com/@hson" rel="nofollow noopener" target="_blank">HONG SON</a> on <a href="https://www.pexels.com/" rel="nofollow noopener" target="_blank">Pexels</a>]]

<h2>Choice two: a home that&rsquo;s easy on the knees</h2>

<p>Sometimes the better answer is a different house. That&rsquo;s the move I was talking about: a home that doesn&rsquo;t have stairs.</p>

<p>The National Institute on Aging&rsquo;s advice on timing applies here, too: the best time to think about how to age in place is before you need a lot of care.</p>

<p>When you&rsquo;re touring, bring the checklist with you. Look at the steps to get in the front door, the lighting, where the bedroom and the bathroom are, and whether there&rsquo;s room for grab bars where you&rsquo;d need them.</p>

<h2>Now here&rsquo;s the important part: the money side of selling</h2>

<p>If you&rsquo;ve been in your two-story home for years, two rules are worth knowing before you sell.</p>

<p><strong>The federal tax exclusion.</strong> According to the <a href="https://www.irs.gov/taxtopics/tc701" target="_blank" rel="noopener">IRS</a>, if you owned the home and used it as your residence for at least two of the five years before the sale, you may be able to exclude up to $250,000 of the gain from your income, or up to $500,000 if you file a joint return with your spouse. Generally you can&rsquo;t use it if you excluded the gain from another home sale in the two years before. A tax professional can tell you how it applies to you.</p>

<p><strong>Georgia property tax breaks.</strong> The <a href="https://dor.georgia.gov/property-tax-homestead-exemptions" target="_blank" rel="noopener">Georgia Department of Revenue</a> lists a $4,000 exemption from county taxes for homeowners 65 or older, and an exemption from school taxes for homeowners 62 or older. Both have an income limit of $10,000 for the prior year, which is tight. But the state Constitution also lets counties enact their own local homestead exemptions, so ask your county tax commissioner what&rsquo;s available where you&rsquo;re moving. And if you move, check that the homestead exemption is filed on your new home. I explained how in <a href="/blog/georgia-homestead-exemption-explained">Georgia&rsquo;s homestead exemption, explained</a>.</p>

<h2>Get rid of the enemy</h2>

<p>So if you are looking for something that&rsquo;s easy on the knees and you want to get rid of this enemy, <a href="/contact">reach out</a>. And if you want to know what your current home is worth before you decide, <a href="/home-value">start here</a>.</p>

<p>So that&rsquo;s when stairs become the enemy, explained.</p>

<p><em>This article explains general safety guidance and how these rules work. It isn&rsquo;t medical, tax or legal advice. Talk to your doctor about fall risk, a tax professional about selling your home, and your county about property tax exemptions.</em></p>

<h2>Sources</h2>

<ol>
<li>Erika K. Page, &ldquo;stairs become the enemy&rdquo; video, her own words.</li>
<li>Centers for Disease Control and Prevention, <a href="https://www.cdc.gov/falls/data-research/facts-stats/index.html" target="_blank" rel="noopener">Older Adult Falls Data</a> (reviewed September 8, 2026).</li>
<li>Centers for Disease Control and Prevention, <a href="https://www.cdc.gov/steadi/pdf/patient/customizable/CheckforSafety-Brochure-Final-Customizable-508.pdf" target="_blank" rel="noopener">Check for Safety: A Home Fall Prevention Checklist for Older Adults</a> (2018).</li>
<li>National Institute on Aging, <a href="https://www.nia.nih.gov/health/aging-place/aging-place-growing-older-home" target="_blank" rel="noopener">Aging in Place: Growing Older at Home</a> (reviewed October 12, 2023).</li>
<li>Internal Revenue Service, <a href="https://www.irs.gov/taxtopics/tc701" target="_blank" rel="noopener">Topic no. 701, Sale of your home</a> (reviewed September 24, 2026).</li>
<li>Georgia Department of Revenue, <a href="https://dor.georgia.gov/property-tax-homestead-exemptions" target="_blank" rel="noopener">Property Tax Homestead Exemptions</a>.</li>
</ol>
HTML,
    ],

    [
        // Researched around her Gwinnett rollback-rate reel (Dbs_B2MBThx): her
        // lines are quoted; every fact is listed with its source in
        // tools/research/the-rollback-rate-explained.md. Companion to the
        // homestead article, which closed the other half of that reel's promise.
        'slug'           => 'the-rollback-rate-explained',
        'title'          => 'Same tax rate, so why a &ldquo;tax increase&rdquo;?',
        'seo_title'      => 'The Rollback Rate, Explained: Why Georgia Counties Announce a &ldquo;Tax Increase&rdquo;',
        'seo_desc'       => 'Gwinnett kept its 6.95 millage rate and still had to announce a tax increase. What Georgia&rsquo;s rollback rate is, why the law requires it, and what it means for your bill.',
        'excerpt'        => 'Gwinnett County kept its tax rate the same and still had to advertise a property tax increase. Here&rsquo;s the Georgia rule behind the headline, how to read the rollback rate on your own assessment notice, and why your bill can be different from your neighbor&rsquo;s.',
        'date'           => '2026-10-10',
        'updated'        => '2026-10-10',
        'author'         => 'Erika K. Page',
        'cat'            => 'buying',
        'tags'           => ['property taxes', 'rollback rate', 'millage rate', 'Gwinnett County', 'homestead exemption', 'Metro Atlanta'],
        'cover'          => 'assets/photos/17/the-rollback-rate-explained-cover.jpg',
        'cover_alt'      => 'Erika K. Page beside the headline: Same tax rate, so why a &ldquo;tax increase&rdquo;?',
        'cover_credit'   => 'Inset photo by <a href="https://www.pexels.com/@tara-winstead" rel="nofollow noopener" target="_blank">Tara Winstead</a> on <a href="https://www.pexels.com/" rel="nofollow noopener" target="_blank">Pexels</a>',
        'audio'          => 'assets/audio/the-rollback-rate-explained.mp3',
        'audio_note'     => 'A podcast episode on this topic, in Erika’s AI voice. The sources are listed at the end of the article.',
        'guide'          => '',
        'product'        => '',
        'short'          => 'rollback',
        'audio_secs'     => 318,
        'published'      => true,
        'faq' => [
            [
                'q' => 'What is the rollback rate in Georgia?',
                'a' => 'It&rsquo;s the tax rate that, applied to this year&rsquo;s property values, would bring in the same revenue last year&rsquo;s values did. The Georgia Department of Revenue describes it as the millage rate that will produce the same total revenue on the current year&rsquo;s new digest.',
            ],
            [
                'q' => 'Why does a county announce a tax increase when the rate didn&rsquo;t change?',
                'a' => 'Under Georgia law (O.C.G.A. 48-5-32.1), if property values rise and the county adopts a rate above the rollback rate, it must notify the public that taxes are being increased: three public hearings, newspaper notices a week before each, and a press release.',
            ],
            [
                'q' => 'Does that mean my own tax bill will go up?',
                'a' => 'Not automatically. Your exemptions and your assessment decide your bill. In Gwinnett, for example, homeowners with the regular homestead exemption also get the Value Offset Exemption, which keeps the county portion of the bill from rising with the property&rsquo;s value when the rate is held steady. It doesn&rsquo;t apply to schools or cities.',
            ],
            [
                'q' => 'Where can I see the rollback rate for my property?',
                'a' => 'On your annual notice of assessment. Taxing authorities that certify one list an Estimated Roll-Back Millage Rate for their general fund next to your net taxable value. The notice isn&rsquo;t a bill, and it tells you the deadline to appeal your value.',
            ],
        ],
        'body' => <<<'HTML'
<p>Is Gwinnett County raising your property taxes?</p>

<p>This summer, Gwinnett announced what most people were calling a property tax increase. But that&rsquo;s not exactly what was happening. Let me explain.</p>

<h2>What happened in Gwinnett</h2>

<p>The county proposed keeping its general fund millage rate right where it was: <strong>6.950 mills</strong>. The commissioners <a href="https://www.gwinnettcounty.com/-/news-events/news-releases/news-details/gwinnett-board-of-commissioners-proposes-holding-general-fund-millage-rate-steady" target="_blank" rel="noopener">lowered it to 6.950 in 2020 and have held it steady since</a>.</p>

<p>So why the headline? Because the county&rsquo;s <a href="https://www.gwinnettcounty.com/documents/d/gwinnett-county/2026-notice-of-property-tax-increase" target="_blank" rel="noopener">official notice</a> had to say this, word for word: the 2026 millage rate &ldquo;will require an increase in property tax by 2.33 percent.&rdquo; Without that &ldquo;increase,&rdquo; the rate would have been no more than <strong>6.792 mills</strong>. Keeping 6.950 was 0.158 mills above that.</p>

<p>The county held three public hearings, two on July 23 and one on August 4. That afternoon, the commissioners <a href="https://www.gwinnettcounty.com/-/news-events/stories/story-details/gwinnett-county-commission-holds-general-fund-millage-rate-steady-1" target="_blank" rel="noopener">voted to keep 6.95 mills</a>, the same rate for the seventh year in a row.</p>

<p>Same rate. Still a &ldquo;tax increase.&rdquo; Here&rsquo;s why.</p>

<h2>The rollback rate, in plain English</h2>

<p>When property values go up, the same tax rate brings in more money. The <strong>rollback rate</strong> is the lower rate that would bring in the same revenue as last year. Gwinnett&rsquo;s <a href="https://www.gwinnettcounty.com/documents/d/gwinnett-county/2025-how-to-read-your-gwinnett-county-annual-notice-of-assessment-pdf-2" target="_blank" rel="noopener">assessors put it this way</a>: it&rsquo;s the tax rate that, applied to current year property values, produces the same amount of tax revenue as the previous year&rsquo;s property values did.</p>

<p>Now here&rsquo;s the rule. Under Georgia law, if property values increase and the county doesn&rsquo;t lower its rate to the rollback rate, it legally has to advertise that as a tax increase, and it has to hold public hearings. The Georgia Department of Revenue&rsquo;s <a href="https://dor.georgia.gov/property-taxpayers-bill-rights" target="_blank" rel="noopener">Property Taxpayer&rsquo;s Bill of Rights</a> (O.C.G.A. 48-5-32.1) spells it out:</p>

<ul>
<li>a rollback rate must be computed that would produce the same total revenue on this year&rsquo;s new digest;</li>
<li>if the county doesn&rsquo;t roll back, it must notify the public that taxes are being increased;</li>
<li><strong>three public hearings</strong>, and one of them must begin between 6:00 and 7:00 in the evening;</li>
<li>a notice in the paper one week before each hearing;</li>
<li>and a press release explaining its intent to increase taxes.</li>
</ul>

<p>It&rsquo;s a transparency rule. Even when the rate stays the same, you get told, and you get a chance to speak.</p>

[[img:assets/photos/17/b15-suburban-neighborhood-aerial.jpg|Rows of suburban homes and lawns seen from above|Photo by <a href="https://www.pexels.com/@alex-hostetler-2159652617" rel="nofollow noopener" target="_blank">Alex Hostetler</a> on <a href="https://www.pexels.com/" rel="nofollow noopener" target="_blank">Pexels</a>]]

<h2>How the math works</h2>

<p>A tax rate in mills is easier than it sounds. According to the <a href="https://dor.georgia.gov/local-government-services/digest-compliance/property-tax-millage-rates" target="_blank" rel="noopener">Department of Revenue</a>, one mill is a tax of one dollar for every $1,000 of <em>assessed</em> value, and in Georgia the assessed value is 40% of the fair market value. The department&rsquo;s own example: a house worth $100,000 has an assessed value of $40,000, and at 25 mills, the tax is $1,000.</p>

<p>Exemptions come off before the rate is applied. That&rsquo;s where your situation starts to separate from your neighbor&rsquo;s.</p>

<h2>Now here&rsquo;s the important part</h2>

<p>A &ldquo;tax increase&rdquo; notice <strong>doesn&rsquo;t automatically mean every homeowner is going to pay more.</strong> If you have a homestead exemption, or depending on how your property is assessed, your situation can be very, very different from your neighbor.</p>

<p>Gwinnett is a good example. Homeowners there who qualify for the regular homestead exemption also get the <strong>Value Offset Exemption</strong>, which the county says keeps the assessed value of the home constant for the <em>county</em> portion of the bill. With the rate held steady, the county tax amount won&rsquo;t go up even if the home&rsquo;s value did. But the county is clear that it applies only to the county government portion, not to schools or cities.</p>

<p>If you don&rsquo;t have your homestead exemption yet, start there. I explained it in <a href="/blog/georgia-homestead-exemption-explained">Georgia&rsquo;s homestead exemption, explained</a>.</p>

<h2>Where to see it for yourself</h2>

<p>You don&rsquo;t have to wait for the headline. Your <strong>annual notice of assessment</strong>, the one that says &ldquo;this is not a bill,&rdquo; now lists an <strong>Estimated Roll-Back Millage Rate</strong> for each taxing authority that certified one, under O.C.G.A. 48-5-306. A taxing authority that doesn&rsquo;t certify one has to show <a href="https://dor.georgia.gov/document/document/pt-306e-annual-notice-assessment-estimated-taxes/download" target="_blank" rel="noopener">its previous year&rsquo;s millage rate and an estimated tax</a> instead. The notice also gives you your deadline to appeal your value: 45 days from the notice date.</p>

<p>In Gwinnett, the August vote cleared the way for tax bills to go out in September, with payments due in November.</p>

<p>So before you panic, and before you decide it&rsquo;s not a good time to buy or sell a home, talk to someone who understands how this actually works. That&rsquo;s why headlines don&rsquo;t tell the whole story. If you&rsquo;re thinking about buying or selling in Metro Atlanta, <a href="/contact">reach out</a>, and if you want to know what your home is worth today, <a href="/home-value">start here</a>.</p>

<p>So that&rsquo;s the rollback rate, explained.</p>

<p><em>This article explains how property tax rates are set in Georgia. It isn&rsquo;t tax or legal advice. Millage rates, exemptions and deadlines differ by county, city and school district, so confirm yours with your county tax assessor and tax commissioner.</em></p>

<h2>Sources</h2>

<ol>
<li>Erika K. Page, Gwinnett property tax video and caption (2026), her own words.</li>
<li>Gwinnett County, <a href="https://www.gwinnettcounty.com/-/news-events/news-releases/news-details/gwinnett-board-of-commissioners-proposes-holding-general-fund-millage-rate-steady" target="_blank" rel="noopener">Board of Commissioners proposes holding general fund millage rate steady</a> (July 14, 2026).</li>
<li>Gwinnett County Board of Commissioners, <a href="https://www.gwinnettcounty.com/documents/d/gwinnett-county/2026-notice-of-property-tax-increase" target="_blank" rel="noopener">Notice of Property Tax Increase</a> (2026).</li>
<li>Gwinnett County, <a href="https://www.gwinnettcounty.com/-/news-events/stories/story-details/gwinnett-county-commission-holds-general-fund-millage-rate-steady-1" target="_blank" rel="noopener">Gwinnett County Commission holds general fund millage rate steady</a> (August 4, 2026).</li>
<li>Gwinnett County Tax Assessors&rsquo; Office, <a href="https://www.gwinnettcounty.com/documents/d/gwinnett-county/2025-how-to-read-your-gwinnett-county-annual-notice-of-assessment-pdf-2" target="_blank" rel="noopener">How to read your Gwinnett County annual notice of assessment</a> (2025).</li>
<li>Georgia Department of Revenue, <a href="https://dor.georgia.gov/property-taxpayers-bill-rights" target="_blank" rel="noopener">Property Taxpayer&rsquo;s Bill of Rights</a>.</li>
<li>Georgia Department of Revenue, <a href="https://dor.georgia.gov/local-government-services/digest-compliance/property-tax-millage-rates" target="_blank" rel="noopener">Property Tax Millage Rates</a>.</li>
<li>Georgia Department of Revenue, <a href="https://dor.georgia.gov/document/document/pt-306e-annual-notice-assessment-estimated-taxes/download" target="_blank" rel="noopener">Form PT-306E, annual notice of assessment</a> (revised April 2025).</li>
</ol>
HTML,
    ],

    [
        // Researched around two of her own videos: "Drew's Video" (her lender
        // partner, quoted) and "convincing a stranger to loan you money". Every
        // other fact is listed with its source in
        // tools/research/your-bank-is-not-your-best-option-for-a-mortgage.md.
        // Part of the twice-a-month NEXA / Andrew series (see CLAUDE.md).
        'slug'           => 'your-bank-is-not-your-best-option-for-a-mortgage',
        'title'          => 'Your bank is not your best option for a mortgage.',
        'seo_title'      => 'Bank vs. Mortgage Broker: Why Your Bank Isn&rsquo;t Your Only Option for a Home Loan',
        'seo_desc'       => 'A bank offers one menu. A broker can shop many lenders for you. How the two differ, what shopping does to your credit, and why a loan file takes patience.',
        'excerpt'        => 'When you walk into your bank for a home loan, they can offer you what they have. A mortgage broker goes shopping for you. Here&rsquo;s the difference, what shopping around does to your credit score, and the lender partner I send my clients to.',
        'date'           => '2026-10-10',
        'updated'        => '2026-10-10',
        'author'         => 'Erika K. Page',
        'cat'            => 'buying',
        'tags'           => ['mortgage', 'mortgage broker', 'home loan', 'credit score', 'first-time buyer', 'Metro Atlanta'],
        'cover'          => 'assets/photos/17/your-bank-is-not-your-best-option-for-a-mortgage-cover.jpg',
        'cover_alt'      => 'Erika K. Page beside the headline: Your bank is not your best option for a mortgage.',
        'cover_credit'   => 'Inset photo by <a href="https://www.pexels.com/@introspectivedsgn" rel="nofollow noopener" target="_blank">Erik Mclean</a> on <a href="https://www.pexels.com/" rel="nofollow noopener" target="_blank">Pexels</a>',
        'audio'          => 'assets/audio/your-bank-is-not-your-best-option-for-a-mortgage.mp3',
        'audio_note'     => 'A podcast episode on this topic, in Erika’s AI voice. The sources are listed at the end of the article.',
        'guide'          => '',
        'product'        => '',
        'short'          => 'lender',
        'audio_secs'     => 294,
        'published'      => true,
        'faq' => [
            [
                'q' => 'What is the difference between a mortgage lender and a mortgage broker?',
                'a' => 'According to the Consumer Financial Protection Bureau, a lender is a financial institution that makes direct loans, while a broker does not lend money &mdash; you can use a broker to find different lenders or mortgage loans. Some institutions operate as both, so ask whether a broker is involved in your loan.',
            ],
            [
                'q' => 'Will shopping with more than one lender hurt my credit score?',
                'a' => 'The CFPB says multiple credit checks from mortgage lenders are recorded on your credit report as a single inquiry, as long as the last check is within 45 days of the first. An inquiry typically has a small negative effect, and shopping around is usually still worth it.',
            ],
            [
                'q' => 'How does a mortgage broker or loan officer get paid?',
                'a' => 'Usually through a loan-specific fee or commission, paid by you or by the lender. Before you work with one, the CFPB says to make sure you understand their fees and who pays them.',
            ],
            [
                'q' => 'Do I have to use the lender my agent recommends?',
                'a' => 'No. You are free to choose any lender, and the CFPB&rsquo;s advice is to always shop around for the best loan terms and the lowest interest rates and fees, whether you use a broker or a direct lender.',
            ],
        ],
        'body' => <<<'HTML'
<p>Your bank is not your best option for a mortgage. Let me say it again. Your bank is not your best option for your mortgage.</p>

<p>That&rsquo;s how I opened a video about my lender partner. Let me explain.</p>

<h2>One menu, or a personal shopper</h2>

<p>When you walk into your bank for a home loan, they can only offer you what they have. One menu, one set of rates &mdash; that&rsquo;s it.</p>

<p>A mortgage broker works differently. <strong>They go shopping for you.</strong> Think of it like your own personal shopper.</p>

<p>The <a href="https://www.consumerfinance.gov/ask-cfpb/what-is-the-difference-between-a-mortgage-lender-and-a-mortgage-broker-en-130/" target="_blank" rel="noopener">Consumer Financial Protection Bureau</a> draws the same line:</p>

<ul>
<li>a <strong>lender</strong> is a financial institution that makes direct loans;</li>
<li>a <strong>broker</strong> does not lend money &mdash; you can use a broker to find different lenders or mortgage loans;</li>
<li>and some financial institutions operate as both, so ask whether a broker is involved in your loan.</li>
</ul>

<p>Brokers get paid, too. Loan officers and brokers usually earn <a href="https://www.consumerfinance.gov/ask-cfpb/how-does-a-mortgage-loan-officer-or-broker-get-paid-en-132/" target="_blank" rel="noopener">a loan-specific fee or commission</a>, paid either by you or by the lender. Before you work with one, make sure you understand their fees and who pays them.</p>

<h2>My lender partner</h2>

<p>My lender partner &mdash; my go-to guy &mdash; is <strong>Andrew &ldquo;Drew&rdquo; Channell</strong>, a Senior Loan Officer with NEXA (NMLS #1920676). In my video I put it this way: he and his team have access to over 200 banks and wholesale lenders. That means he finds the product that fits your situation. Customized &mdash; not one-size-fits-all.</p>

<p>One thing to know about the company: it now goes by NEXA Lending. Its CEO <a href="https://www.housingwire.com/articles/nexa-mortgage-rebrands-to-nexa-lending-but-its-not-a-pivot-to-retail/" target="_blank" rel="noopener">told HousingWire</a> in October 2025 that NEXA now funds about 60% of its loans itself, as a correspondent lender. That&rsquo;s exactly why the CFPB&rsquo;s advice &mdash; ask whether a broker is involved in your loan &mdash; is a good one, whoever your lender is.</p>

[[img:assets/photos/17/b13-couple-reviewing-loan-documents.jpg|A couple reviewing loan documents together at their kitchen table|Photo by <a href="https://www.pexels.com/@ron-lach" rel="nofollow noopener" target="_blank">Ron Lach</a> on <a href="https://www.pexels.com/" rel="nofollow noopener" target="_blank">Pexels</a>]]

<h2>Your credit score while you shop</h2>

<p>Here&rsquo;s something most people don&rsquo;t know. Andrew won&rsquo;t even run your credit until he knows the numbers make sense. No unnecessary hits to your score while you&rsquo;re trying to buy a home &mdash; and that&rsquo;s the kind of lender you need in your corner.</p>

<p>And when it&rsquo;s time to compare lenders, don&rsquo;t be afraid to shop. The <a href="https://www.consumerfinance.gov/ask-cfpb/what-exactly-happens-when-a-mortgage-lender-checks-my-credit-en-2005/" target="_blank" rel="noopener">CFPB</a> explains that multiple credit checks from mortgage lenders are recorded on your credit report as a single inquiry, as long as the last check is within 45 days of the first. An inquiry typically has a small negative effect, and shopping around is usually still worth it.</p>

<h2>Now here&rsquo;s the important part: give your loan some grace</h2>

<p>When I connect you to my vendors, and especially to financing, and your file is a difficult one, I&rsquo;m going to need you to have some patience. Here&rsquo;s how I want you to think about it.</p>

<p>Me, the lender, the attorney, the appraiser, and sometimes the inspector &mdash; <strong>we have to convince a stranger to loan you hundreds of thousands of dollars.</strong> They don&rsquo;t know you. They barely know us. So don&rsquo;t be frustrated when they ask for documentation and proof. I&rsquo;m willing to bet you couldn&rsquo;t get your brother, your neighbor or your coworker to loan you fifty dollars.</p>

<p>But I&rsquo;m going to work hard, and so are all my vendors, to get that loan to you so you can get the thing you want.</p>

<h2>Shop, compare, then choose</h2>

<p>Whether you use a broker or a direct lender, the CFPB&rsquo;s advice is the same: always shop around for the best loan terms and the lowest interest rates and fees. And about Andrew? I&rsquo;m not telling you what I think. I&rsquo;m telling you what I know.</p>

<p>If you want to talk to my go-to guy, <a href="/contact">get with me so you can get with Andrew</a>. And if you&rsquo;re already a homeowner wondering what your house is worth before your next move, <a href="/home-value">start here</a>.</p>

<p>So that&rsquo;s choosing a mortgage lender, explained.</p>

<p><em>This article explains how mortgage shopping generally works. It isn&rsquo;t a loan offer, a rate quote or financial advice. Erika K. Page is a real estate agent with Axen Realty, not a lender. Andrew &ldquo;Drew&rdquo; Channell is a Senior Loan Officer, NMLS #1920676, with NEXA (company NMLS #1660690). You are free to choose any lender.</em></p>

<h2>Sources</h2>

<ol>
<li>Erika K. Page, &ldquo;Drew&rsquo;s Video&rdquo; (June 2026) and her video on convincing a stranger to loan you money, her own words.</li>
<li>Consumer Financial Protection Bureau, <a href="https://www.consumerfinance.gov/ask-cfpb/what-is-the-difference-between-a-mortgage-lender-and-a-mortgage-broker-en-130/" target="_blank" rel="noopener">What is the difference between a mortgage lender and a mortgage broker?</a> (reviewed December 11, 2024).</li>
<li>Consumer Financial Protection Bureau, <a href="https://www.consumerfinance.gov/ask-cfpb/how-does-a-mortgage-loan-officer-or-broker-get-paid-en-132/" target="_blank" rel="noopener">How does a mortgage loan officer or broker get paid?</a> (reviewed January 7, 2025).</li>
<li>Consumer Financial Protection Bureau, <a href="https://www.consumerfinance.gov/ask-cfpb/what-exactly-happens-when-a-mortgage-lender-checks-my-credit-en-2005/" target="_blank" rel="noopener">What exactly happens when a mortgage lender checks my credit?</a> (reviewed August 28, 2026).</li>
<li>HousingWire, <a href="https://www.housingwire.com/articles/nexa-mortgage-rebrands-to-nexa-lending-but-its-not-a-pivot-to-retail/" target="_blank" rel="noopener">NEXA Mortgage rebrands to NEXA Lending, but it&rsquo;s not a pivot to retail</a> (October 2, 2025).</li>
<li>Andrew &ldquo;Drew&rdquo; Channell&rsquo;s professional details (title, individual and company NMLS numbers) as supplied from his business signature, October 2026.</li>
</ol>
HTML,
    ],

    [
        // Researched around her open-house reels (Dbt77Dkuk44, co-hosted with
        // Evelyn; DbziGayiEgQ, Smyrna, used for one line only). Evelyn's words are
        // credited to Evelyn. Every other fact is listed with its source in
        // tools/research/the-open-house-is-a-sales-event.md. The audio is a podcast
        // episode from tools/podcast/<slug>.txt (hence audio_note).
        'slug'           => 'the-open-house-is-a-sales-event',
        'title'          => 'The open house is a sales event.',
        'seo_title'      => 'The Open House Is a Sales Event: How to Host and Prepare in Metro Atlanta',
        'seo_desc'       => 'Who really comes to an open house, how a host works the room, what sellers put away first, and why everybody is welcome. An agent explains, with sources.',
        'excerpt'        => 'Neighbors come to peek and value their own house. Buyers come to picture living there. Everybody&rsquo;s welcome &mdash; but an open house is a sales event, and knowing who&rsquo;s there for the right reason changes how you work the room.',
        'date'           => '2026-10-09',
        'updated'        => '2026-10-09',
        'author'         => 'Erika K. Page',
        'cat'            => 'selling',
        'tags'           => ['open house', 'selling a home', 'home showing', 'seller safety', 'fair housing', 'Metro Atlanta'],
        'cover'          => 'assets/photos/17/the-open-house-is-a-sales-event-cover.jpg',
        'cover_alt'      => 'Erika K. Page beside the headline: The open house is a sales event.',
        'cover_credit'   => 'Inset photo by <a href="https://www.pexels.com/@curtis-adams-1694007" rel="nofollow noopener" target="_blank">Curtis Adams</a> on <a href="https://www.pexels.com/" rel="nofollow noopener" target="_blank">Pexels</a>',
        'audio'          => 'assets/audio/the-open-house-is-a-sales-event.mp3',
        'audio_note'     => 'A podcast episode on this topic, in Erika’s AI voice. The sources are listed at the end of the article.',
        'guide'          => '',
        'product'        => '',
        'short'          => 'open-house',
        'audio_secs'     => 404,
        'published'      => true,
        'faq' => [
            [
                'q' => 'Who actually comes to an open house?',
                'a' => 'Buyers, and often neighbors. In a neighborhood people want to be in, many visitors are neighbors stopping by to see the home or to get a sense of what their own house is worth. They are always welcome &mdash; they are just different from motivated buyers, and a good host knows the difference.',
            ],
            [
                'q' => 'What should I put away before an open house?',
                'a' => 'The National Association of REALTORS&reg; consumer guide says to lock up jewelry, important and sensitive documents, firearms and prescription medications, and to put away anything that reveals personal details: family photos, calendars, mail, computer logins and Wi-Fi passwords.',
            ],
            [
                'q' => 'Can an open house host treat some visitors differently?',
                'a' => 'No. The federal Fair Housing Act protects people from discrimination when they are buying or renting a home, on the basis of race, color, national origin, religion, sex, familial status and disability. Telling a buyer from a curious neighbor is about why someone came, never about who they are. Everyone gets the same welcome.',
            ],
            [
                'q' => 'How does a host keep an open house safe?',
                'a' => 'NAR&rsquo;s open-house guidance suggests working with a buddy and checking in with the office, asking for identification, never giving out garage or door codes, limiting the number of people inside, and planning to end the open house with a colleague.',
            ],
        ],
        'body' => <<<'HTML'
<p>&ldquo;This is a sales event.&rdquo;</p>

<p>That&rsquo;s how Evelyn summed it up. Evelyn has been a successful realtor for decades, across several states, and she recently joined our company. We co-hosted a mega open house together, and afterward I asked her what stood out the most. What would she pick up?</p>

<p>Her answer: &ldquo;Know who&rsquo;s there for the right reason.&rdquo; And then: &ldquo;We love everybody. Everybody&rsquo;s welcome. But this is a sales event.&rdquo; She said she learned that from me that day. So even an agent with 30 years of experience can learn some new things.</p>

<p>Both halves of what she said matter &mdash; the welcome and the sale. Let me explain.</p>

<h2>Who walks through the door</h2>

<p>In highly desirable neighborhoods, many visitors are simply neighbors stopping by to see the home or estimate the value of their own property. Evelyn put it this way: learn to recognize &ldquo;those people who are just coming to look and peek and value their own house versus coming in to buy the house themselves.&rdquo;</p>

<p>So on any open house day, you&rsquo;ll get two kinds of visitors:</p>

<ul>
<li><strong>The neighbor</strong>, who wants to see inside and get a feel for what their own house might be worth.</li>
<li><strong>The buyer</strong>, who came because they might actually live there.</li>
</ul>

<p>The neighbors are always welcome. But they&rsquo;re different from motivated buyers. <strong>Understanding that difference changes how you work the room</strong>, and it helps you focus your time where it matters most.</p>

[[img:assets/photos/17/b11-agent-welcoming-open-house-visitor.jpg|A real estate agent welcoming a visitor at the front door of a home|Photo by <a href="https://www.pexels.com/@rdne" rel="nofollow noopener" target="_blank">RDNE Stock project</a> on <a href="https://www.pexels.com/" rel="nofollow noopener" target="_blank">Pexels</a>]]

<h2>Working the room</h2>

<p>A sales event means the house has something to sell, so lead with it. On a tour, I point out the special and unique things about that particular home. As I said at an open house in Smyrna, this might be the exact thing somebody&rsquo;s looking for.</p>

<p>Watch your words, too. On that same tour, I caught myself using the old name for the main bedroom and corrected it right there on camera: &ldquo;big owner&rsquo;s suite. We&rsquo;ve changed the language.&rdquo;</p>

<p>And plan for safety. Evelyn and I hosted that open house together, and that&rsquo;s exactly what the National Association of REALTORS&reg; recommends in its <a href="https://www.nar.realtor/open-houses" target="_blank" rel="noopener">open-house guidance</a>:</p>

<ul>
<li>work with a buddy, and check in with your office;</li>
<li>ask for identification;</li>
<li>don&rsquo;t give out garage or door codes;</li>
<li>limit the number of people in the house;</li>
<li>and plan to end the open house with a colleague. NAR notes that the end of an open house is often the most dangerous time for an agent working alone.</li>
</ul>

<h2>Before the day: the seller&rsquo;s part</h2>

<p>Sellers, your job starts before the first visitor. NAR&rsquo;s <a href="https://www.nar.realtor/the-facts/consumer-guide-home-selling-tips-for-privacy-and-safety" target="_blank" rel="noopener">consumer guide on privacy and safety</a> is plain about it:</p>

<ul>
<li><strong>Lock up</strong> jewelry, important and sensitive documents, firearms and prescription medications. A small lockbox or safe is worth having with visitors coming and going.</li>
<li><strong>Put away anything personal</strong> &mdash; family photos, visible calendars, mail, computer logins, Wi-Fi passwords. Even diplomas, awards or books can give away more than you realize.</li>
<li><strong>Think about photos.</strong> Buyers can wander and take pictures. You can ask your agent to add a &ldquo;No Photography&rdquo; note in the MLS and put up polite signs in the house.</li>
</ul>

<p>Then get the house ready to be seen. NAR&rsquo;s <a href="https://www.nar.realtor/the-facts/consumer-guide-seller-checklist-15-things-to-do-before-every-showing" target="_blank" rel="noopener">seller checklist</a> includes making the beds and putting things away, clearing the kitchen and bath counters, organizing the refrigerator (buyers will open it), not cooking anything with a strong smell in the hours before, opening the window treatments and turning on <em>all</em> the lights &mdash; and taking your pets with you.</p>

<h2>Now here&rsquo;s the important part: everybody&rsquo;s welcome</h2>

<p>&ldquo;We love everybody. Everybody&rsquo;s welcome.&rdquo; That&rsquo;s more than good manners. It&rsquo;s the law.</p>

<p>The federal <a href="https://www.hud.gov/helping-americans/fair-housing-act-overview" target="_blank" rel="noopener">Fair Housing Act</a> protects people from discrimination when they are renting or buying a home, on the basis of race, color, national origin, religion, sex, familial status and disability. The U.S. Department of Justice notes that it applies to <a href="https://www.justice.gov/crt/fair-housing-act-1" target="_blank" rel="noopener">direct providers of housing, such as landlords and real estate companies</a>.</p>

<p>So <strong>&ldquo;know who&rsquo;s there for the right reason&rdquo; is about <em>why</em> someone came &mdash; never about <em>who</em> they are.</strong> The curious neighbor and the serious buyer both get the same warm welcome at the door. The difference is only in where you spend your time.</p>

<h2>Why it pays to treat it like a sale</h2>

<p>The people on the other side of the deal usually come with professional help. In NAR&rsquo;s <a href="https://www.nar.realtor/press-releases/first-time-home-buyer-share-falls-to-historic-low-of-21-median-age-rises-to-40" target="_blank" rel="noopener">2025 Profile of Home Buyers and Sellers</a>, which covers sales from July 2024 to June 2025, 88% of buyers used an agent or broker, and 91% of sellers used an agent &mdash; equal to the highest share on record.</p>

<p>An open house is one afternoon of that work. Done right, it&rsquo;s organized, it&rsquo;s safe, it&rsquo;s welcoming to everyone &mdash; and it&rsquo;s focused on finding the person who wants to live there.</p>

<p>And if you&rsquo;re the neighbor who stopped by to peek and value your own house? That&rsquo;s fine. You don&rsquo;t have to wait for the next open house on your street &mdash; <a href="/home-value">ask me what your home is worth</a>. If you&rsquo;re getting ready to sell in Metro Atlanta, <a href="/contact">let&rsquo;s talk</a>.</p>

<p>So that&rsquo;s the open house, explained.</p>

<p><em>This article explains how open houses generally work. It isn&rsquo;t legal advice. For a question about fair housing, see HUD&rsquo;s resources or talk to an attorney, and ask your agent about the safety plan for your own open house.</em></p>

<h2>Sources</h2>

<ol>
<li>Erika K. Page, open-house videos and captions, her own account; Evelyn&rsquo;s words are hers, as she said them on camera.</li>
<li>National Association of REALTORS&reg;, <a href="https://www.nar.realtor/open-houses" target="_blank" rel="noopener">Open Houses</a> (marketing and safety resources).</li>
<li>National Association of REALTORS&reg;, <a href="https://www.nar.realtor/the-facts/consumer-guide-home-selling-tips-for-privacy-and-safety" target="_blank" rel="noopener">Consumer Guide: Home Selling Tips for Privacy and Safety</a>.</li>
<li>National Association of REALTORS&reg;, <a href="https://www.nar.realtor/the-facts/consumer-guide-seller-checklist-15-things-to-do-before-every-showing" target="_blank" rel="noopener">Consumer Guide: Seller Checklist, 15 Things to Do Before Every Showing</a>.</li>
<li>U.S. Department of Housing and Urban Development, <a href="https://www.hud.gov/helping-americans/fair-housing-act-overview" target="_blank" rel="noopener">Fair Housing Act overview</a>.</li>
<li>U.S. Department of Justice, Civil Rights Division, <a href="https://www.justice.gov/crt/fair-housing-act-1" target="_blank" rel="noopener">The Fair Housing Act</a> (updated June 22, 2023).</li>
<li>National Association of REALTORS&reg;, <a href="https://www.nar.realtor/press-releases/first-time-home-buyer-share-falls-to-historic-low-of-21-median-age-rises-to-40" target="_blank" rel="noopener">2025 Profile of Home Buyers and Sellers, press release</a> (November 4, 2025).</li>
</ol>
HTML,
    ],

    [
        // Researched around her nine restaurant-space-hunt reels: her checklist and
        // lines are quoted; the permits are sourced in
        // tools/research/leasing-a-restaurant-space.md. The audio is a podcast
        // episode from tools/podcast/<slug>.txt (hence audio_note), recorded 9 Oct.
        'slug'           => 'leasing-a-restaurant-space',
        'title'          => 'Leasing a restaurant space? Fall in love with the deal, not the space.',
        'seo_title'      => 'Leasing a Restaurant Space in Metro Atlanta: What to Check Before You Sign',
        'seo_desc'       => 'Zoning, parking, the back door, traffic counts, the health plan review and the lease terms to negotiate before you sign for a restaurant space in Metro Atlanta.',
        'excerpt'        => 'A beautiful space with the wrong zoning, no parking or a raw shell can cost you the business before it opens. Here is what to check before you sign &mdash; from the site itself to the permits that decide when you can open.',
        'date'           => '2026-10-07',
        'updated'        => '2026-10-07',
        'author'         => 'Erika K. Page',
        'cat'            => 'investing',
        'tags'           => ['commercial real estate', 'restaurant space', 'commercial lease', 'zoning', 'small business', 'Metro Atlanta'],
        'cover'          => 'assets/photos/17/leasing-a-restaurant-space-cover.jpg',
        'cover_alt'      => 'Erika K. Page beside the headline: Leasing a restaurant space? Fall in love with the deal, not the space.',
        'cover_credit'   => 'Inset photo by <a href="https://www.pexels.com/@introspectivedsgn" rel="nofollow noopener" target="_blank">Erik Mclean</a> on <a href="https://www.pexels.com/" rel="nofollow noopener" target="_blank">Pexels</a>',
        'audio'          => 'assets/audio/leasing-a-restaurant-space.mp3',
        'audio_note'     => 'A podcast episode on this topic, in Erika’s AI voice. The sources are listed at the end of the article.',
        'guide'          => '',
        'product'        => '',
        'short'          => 'restaurant-space',
        'audio_secs'     => 453,
        'published'      => true,
        'faq' => [
            [
                'q' => 'Do I need health department approval before I build out a restaurant?',
                'a' => 'Yes. Fulton County&rsquo;s Board of Health, for example, says plans and equipment specifications must be submitted for review and approval before construction is started &mdash; and a food service permit is required before you operate. Your county&rsquo;s environmental health office runs the process.',
            ],
            [
                'q' => 'If the last tenant was a restaurant, can I use their permit?',
                'a' => 'No. Food service permits are not transferable to a new owner or a new location, and a change of ownership goes through a plan review of its own. A space that was a restaurant can still save you a lot of build-out &mdash; but the paperwork starts fresh.',
            ],
            [
                'q' => 'How do I check traffic counts for a location?',
                'a' => 'Georgia DOT publishes them. Its free traffic data tool lets you search by address or road name and shows the Annual Average Daily Traffic for counting stations along the road.',
            ],
            [
                'q' => 'What should I negotiate in a restaurant lease?',
                'a' => 'More than the rent: the free rent period, build-out, the tenant improvement allowance, renewal options, signage rights, and who is responsible for major systems like HVAC and electrical. Have an attorney review the lease before you sign.',
            ],
        ],
        'body' => <<<'HTML'
<p>Stop falling in love with the space. Fall in love with the deal.</p>

<p>This summer I&rsquo;ve been looking at commercial spaces with a client who is opening a West African and West Indian restaurant &mdash; Forest Park, East Point, Clayton County, out between Conyers and Lithonia. Some of those spaces were beautiful. Some of them were completely wrong for her. And the beautiful ones were not always the right ones.</p>

<p>Because the perfect space can still be the wrong space. Let me explain.</p>

<h2>Look past the four walls</h2>

<p>When I tour a space for a client, I&rsquo;m checking zoning, parking, visibility, the kitchen infrastructure and the true monthly occupancy cost. We&rsquo;re not just shopping for a building. <strong>We&rsquo;re protecting the business before it ever opens.</strong></p>

<h3>Zoning comes first</h3>

<p>I&rsquo;ve had spaces sent to me on a busy frontage, in a good strip mall &mdash; and they simply weren&rsquo;t zoned for a restaurant. As the <a href="https://www.sba.gov/counseling/launch-your-business/" target="_blank" rel="noopener">U.S. Small Business Administration</a> puts it, zoning ordinances can restrict or entirely ban specific kinds of businesses in an area, and the place to check is your city or county planning office.</p>

<p>Can you get a zone change? You definitely can. But it just takes time, and when you&rsquo;re launching a business, time is money. Check the zoning before you fall for the space.</p>

<h3>Parking can make or break it</h3>

<p>The food can be amazing, but if it&rsquo;s a pain to park, people won&rsquo;t keep coming. Parking affects your customers, your employees and your delivery drivers. At one strip mall, a little sign that said &ldquo;Additional parking&rdquo; in the rear moved that space up my list.</p>

<h3>The back door matters</h3>

<p>In a house, nobody sees your back door. In a restaurant, <strong>for a lot of your vendors, your back door is the front door.</strong> Deliveries, product, trucks, staff &mdash; walk around back and see how it actually works before you sign.</p>

<h3>Traffic and neighbors tell a story</h3>

<p>For a restaurant, you want people moving past your door. Traffic counts make a huge difference, and you don&rsquo;t have to guess: Georgia DOT publishes them in its <a href="https://gdottrafficdata.drakewell.com/publicmultinodemap.asp" target="_blank" rel="noopener">traffic data tool</a>, where you can search by address or road name and see the <a href="https://www.dot.ga.gov/DriveSmart/Data/Documents/TrafficCounts/TrafficCountsApp-Cheatsheet.pdf" target="_blank" rel="noopener">annual average daily traffic</a> for the road.</p>

<p>Then look at the neighbors. One space we saw was three doors down from a Mexican restaurant, and in the time I waited for my client, I watched people coming in and out. That tells you people already come to that center for food &mdash; and for more than burgers and fries. Look at what is planned nearby, too; at one building in East Point, there was information posted about a redevelopment plan for the area.</p>

[[img:assets/photos/17/b5-restaurant-kitchen-hoods.jpg|A restaurant kitchen with the hoods, shelving and equipment already in place|Photo by <a href="https://www.pexels.com/@orlovamaria" rel="nofollow noopener" target="_blank">Maria Orlova</a> on <a href="https://www.pexels.com/" rel="nofollow noopener" target="_blank">Pexels</a>]]

<h2>Cheap and affordable are not the same thing</h2>

<p>The first space we found for my client was ready to go &mdash; kitchen equipment and all. Then she asked me to find something less expensive, and I did: thousands of dollars a month cheaper. We walked in, and it was completely raw.</p>

<p>She wanted to open in 30 days. There is no time for a build-out in 30 days. So now I&rsquo;m looking, on purpose, for spaces that already have the kitchen equipment, the grease trap, the hoods and the electrical in place. That may cost more per month &mdash; but sometimes paying more gets you open faster, and making money faster.</p>

<h2>Now here is the important part: the permits decide when you open</h2>

<p>A restaurant doesn&rsquo;t open when the lease is signed. It opens when the inspectors say so.</p>

<ul>
<li><strong>The health department reviews your plans first.</strong> In Fulton County, for example, the <a href="https://www.fultoncountyga.gov/-/media/Departments/Board-of-Health/Environmental-Health/Restaurant-Inspection/Link-List-Items/Starting-A-Food-Service-Business.pdf" target="_blank" rel="noopener">Board of Health</a> requires plans and equipment specifications to be submitted and approved <em>before construction is started</em>, and you need a food service permit to operate.</li>
<li><strong>A former restaurant doesn&rsquo;t hand you its permit.</strong> Food service permits are not transferable to a new person or a new location, and a change of ownership gets its own plan review.</li>
<li><strong>The grease trap is part of it.</strong> In Gwinnett, the <a href="https://gnrhealth.com/wp-content/uploads/2024/08/Food-Service-Plan-Review-Requirements-fillable-3-11-16-JER.pdf" target="_blank" rel="noopener">health department&rsquo;s plan review</a> wants the grease trap drawn on the plumbing plans for county approval. Inside the City of Atlanta, <a href="https://atlantawatershed.org/grease-management-3/" target="_blank" rel="noopener">every food service facility needs a grease permit</a> from the Department of Watershed Management.</li>
<li><strong>The building has to be cleared to open.</strong> In Gwinnett, a <a href="https://www.gwinnettcounty.com/static/departments/fire_emergency/pdf/obtaining_fire_permit.pdf" target="_blank" rel="noopener">final inspection and certificate of occupancy</a> must be obtained before occupying or conducting business in any commercial building, and permits are required for a change of use or a new tenant. Your city or county has its own version.</li>
<li><strong>And the rest:</strong> Fulton&rsquo;s Board of Health reminds applicants they also need a business license, a liquor license if they will serve alcohol, and zoning approval &mdash; and to check whether the site is on public sewer or a septic system.</li>
</ul>

<p>Every one of those takes time. A space that already passed those steps as a restaurant, with the right equipment in place, can be worth paying more for.</p>

<h2>Negotiate the whole deal</h2>

<p>Finding the space is only part of the job. The commercial lease has to support the business. When I negotiate for a client, it&rsquo;s not just the base rent. It&rsquo;s:</p>

<ul>
<li>the free rent period;</li>
<li>the build-out and the tenant improvement allowance;</li>
<li>renewal options;</li>
<li>signage rights;</li>
<li>and who is responsible for the major systems, like the HVAC and the electrical.</li>
</ul>

<p>Ask how the rent is calculated, too &mdash; depending on the lease type, part of the monthly rent can be based on your sales. Because a great-looking space with a bad lease? Still a bad deal.</p>

<p>Business owners know how to do a whole bunch of stuff. This is one where you want professional help &mdash; a commercial agent on the space and the terms, and an attorney on the lease before you sign. If you&rsquo;re looking for restaurant or retail space in Metro Atlanta, <a href="/contact">tell me what you need</a>.</p>

<p>So that&rsquo;s leasing a restaurant space, explained.</p>

<p><em>This article is general information, not legal advice. Permits and requirements differ by city and county &mdash; confirm them with your local planning, building and health departments, and have an attorney review any commercial lease before you sign.</em></p>

<h2>Sources</h2>

<ol>
<li>Erika K. Page, restaurant-space-hunt videos and captions (August&ndash;September 2026), her own account.</li>
<li>U.S. Small Business Administration, <a href="https://www.sba.gov/counseling/launch-your-business/" target="_blank" rel="noopener">Launch your business: pick your business location</a>.</li>
<li>Fulton County Board of Health, Environmental Health Services, <a href="https://www.fultoncountyga.gov/-/media/Departments/Board-of-Health/Environmental-Health/Restaurant-Inspection/Link-List-Items/Starting-A-Food-Service-Business.pdf" target="_blank" rel="noopener">Basic Requirements for Opening a Food Service Establishment</a>.</li>
<li>Gwinnett, Newton &amp; Rockdale County Health Departments, <a href="https://gnrhealth.com/wp-content/uploads/2024/08/Food-Service-Plan-Review-Requirements-fillable-3-11-16-JER.pdf" target="_blank" rel="noopener">Food Service Plan Review Requirements</a>.</li>
<li>City of Atlanta Department of Watershed Management, <a href="https://atlantawatershed.org/grease-management-3/" target="_blank" rel="noopener">Grease Management</a>.</li>
<li>Gwinnett County Fire and Emergency Services, <a href="https://www.gwinnettcounty.com/static/departments/fire_emergency/pdf/obtaining_fire_permit.pdf" target="_blank" rel="noopener">Obtaining Fire Permits and a Fire Certificate of Occupancy</a>.</li>
<li>Georgia Department of Transportation, <a href="https://gdottrafficdata.drakewell.com/publicmultinodemap.asp" target="_blank" rel="noopener">Traffic Analysis and Data Application (TADA)</a> and its <a href="https://www.dot.ga.gov/DriveSmart/Data/Documents/TrafficCounts/TrafficCountsApp-Cheatsheet.pdf" target="_blank" rel="noopener">quick reference guide</a> (updated September 2025).</li>
</ol>
HTML,
    ],

    [
        // Researched around her own video: her four points are hers (quoted from
        // what-i-wish-i-knew-about-relocating); every other fact is listed with
        // its source in tools/research/relocating-to-metro-atlanta.md. The audio
        // is a podcast episode from tools/podcast/<slug>.txt (hence audio_note).
        'slug'           => 'relocating-to-metro-atlanta',
        'title'          => 'Moving to Metro Atlanta? What I wish I’d known first.',
        'seo_title'      => 'Moving to Metro Atlanta: What to Know Before You Relocate',
        'seo_desc'       => 'Atlanta is 29 counties, not one city. Commutes, school districts, pollen season and the 30-day license and car rules, with the official places to check.',
        'excerpt'        => 'I moved from Chicago to Atlanta over 27 years ago. Here is what I wish somebody had told me before I headed south &mdash; and the official places to check everything before you pick a neighborhood.',
        'date'           => '2026-10-06',
        'updated'        => '2026-10-06',
        'author'         => 'Erika K. Page',
        'cat'            => 'lifestyle',
        'tags'           => ['relocating to Atlanta', 'moving to Georgia', 'Metro Atlanta', 'commute', 'school districts', 'new residents'],
        'cover'          => 'assets/photos/17/relocating-to-metro-atlanta-cover.jpg',
        'cover_alt'      => 'Erika K. Page beside the headline: Moving to Metro Atlanta? What I wish I’d known first.',
        'cover_credit'   => 'Inset photo by <a href="https://www.pexels.com/@ivan-s" rel="nofollow noopener" target="_blank">Ivan S</a> on <a href="https://www.pexels.com/" rel="nofollow noopener" target="_blank">Pexels</a>',
        'audio'          => 'assets/audio/relocating-to-metro-atlanta.mp3',
        'audio_note'     => 'A podcast episode on this topic, in Erika’s AI voice. The sources are listed at the end of the article.',
        'guide'          => '',
        'product'        => '',
        'short'          => 'moving-to-atlanta',
        'audio_secs'     => 342,
        'published'      => true,
        'faq' => [
            [
                'q' => 'How long do I have to get a Georgia driver&rsquo;s license after I move?',
                'a' => 'Thirty days from becoming a Georgia resident, according to the Department of Driver Services. If your old license is current, you surrender it and pass a vision exam, and you bring two proofs of your Georgia address from separate sources.',
            ],
            [
                'q' => 'When do I have to register my car in Georgia?',
                'a' => 'Within 30 days of moving, at your county tag office &mdash; and you need your Georgia license first. New residents pay a one-time title ad valorem tax of 3% of the vehicle&rsquo;s fair market value, according to the Department of Revenue.',
            ],
            [
                'q' => 'How many counties are in Metro Atlanta?',
                'a' => 'The metro area the federal government measures &mdash; Atlanta-Sandy Springs-Roswell &mdash; is 29 counties. Each has its own government, its own property taxes and, in most cases, its own school district.',
            ],
            [
                'q' => 'When is pollen season in Atlanta?',
                'a' => 'Spring is the big one &mdash; the trees. Atlanta&rsquo;s official count comes from Atlanta Allergy &amp; Asthma, and for tree pollen anything from 1,500 up is &ldquo;extremely high.&rdquo; The record, 14,801, was set on March 29, 2025.',
            ],
        ],
        'body' => <<<'HTML'
<p>I moved from Chicago to Atlanta over 27 years ago. And here is what I wish somebody had told me before I picked up and headed south.</p>

<p>Not the brochure version. The things that actually decide whether you love it here or spend your first year wondering why you came. Let me explain.</p>

<h2>1. Atlanta is not just one city</h2>

<p>When people say &ldquo;Atlanta,&rdquo; they usually mean the whole region. And the region is big. The metro area the federal government measures &mdash; officially Atlanta-Sandy Springs-Roswell &mdash; is <a href="https://www.bls.gov/regions/southeast/news-release/areaemployment_atlanta.htm" target="_blank" rel="noopener">29 counties</a>, each with its own government.</p>

<p>As I put it in my video: it&rsquo;s all Atlanta, it&rsquo;s connected, but it&rsquo;s kind of disconnected. <strong>Your commute, your social life, your schools, your home price, even how often you actually go into the city can depend on the neighborhood or the suburb you choose.</strong></p>

<p>Schools are the clearest example. In most of Metro Atlanta your school district follows the county line &mdash; but some cities run their own: <a href="https://www.atlantapublicschools.us/" target="_blank" rel="noopener">Atlanta Public Schools</a>, <a href="https://www.csdecatur.net/" target="_blank" rel="noopener">City Schools of Decatur</a>, <a href="https://www.marietta-city.org/" target="_blank" rel="noopener">Marietta City Schools</a> and <a href="https://www.bufordcityschools.org/" target="_blank" rel="noopener">Buford City Schools</a>. Two homes a few minutes apart can belong to different districts. Check the address, not the zip code, and look up the school&rsquo;s numbers in the state&rsquo;s own <a href="https://goews.georgia.gov/report-card" target="_blank" rel="noopener">school report cards</a>. And if you have kids, the State of Georgia&rsquo;s advice is to <a href="https://georgia.gov/moving-georgia" target="_blank" rel="noopener">start enrolling as early as possible</a>.</p>

<p>Property taxes work the same way: county by county, with exemptions you have to apply for. I explained that in <a href="/blog/georgia-homestead-exemption-explained">Georgia&rsquo;s homestead exemption, explained</a> &mdash; read it before you buy.</p>

<h2>2. Traffic is real, but distance isn&rsquo;t the only issue</h2>

<p>This is the one people underestimate. <strong>Ten miles can take 15 minutes or an hour.</strong> It depends on which way you&rsquo;re going and when.</p>

<p>On average, a commute in the metro area takes <a href="https://censusreporter.org/profiles/31000US12060-atlanta-sandy-springs-roswell-ga-metro-area/" target="_blank" rel="noopener">32.4 minutes each way</a>, according to the Census Bureau&rsquo;s 2024 survey. But averages hide the real story, which is your route.</p>

<p>Transit helps &mdash; in the right places. MARTA, the region&rsquo;s rail system, serves <a href="https://myfiles.dot.ga.gov/Intermodal/Transit/Profile%20Sheets/MARTA.pdf" target="_blank" rel="noopener">Fulton, DeKalb and Clayton counties</a>. If you&rsquo;re planning to live somewhere else and ride a train, check what actually reaches you first.</p>

<p>So choose your home based on how you actually plan to live. Before you fall in love with a house, drive the route you&rsquo;ll really drive &mdash; on a weekday, at the time you&rsquo;d really leave.</p>

[[img:assets/photos/17/b4-atlanta-skyline-dusk.jpg|The Midtown Atlanta skyline at dusk, seen over rooftops|Photo by <a href="https://www.pexels.com/@connorscottmcmanus/" rel="nofollow noopener" target="_blank">Connor Scott McManus</a> on <a href="https://www.pexels.com/" rel="nofollow noopener" target="_blank">Pexels</a>]]

<h2>3. The weather is beautiful &mdash; but nobody warned me about the pollen</h2>

<p>Atlanta turns yellow every spring. And if you never had allergies before, you might find you have them after you move here.</p>

<p>Atlanta&rsquo;s official pollen count comes from <a href="https://www.atlantaallergy.com/pollen_counts" target="_blank" rel="noopener">Atlanta Allergy &amp; Asthma</a>, the only counting station in the area certified by the National Allergy Bureau. It measures the grains of pollen in a cubic meter of air over 24 hours, and for tree pollen, anything from 1,500 up is &ldquo;extremely high.&rdquo; On March 29, 2025, the count hit <a href="https://www.atlantanewsfirst.com/2025/03/29/atlanta-shatters-pollen-count-record-rising-far-above-extreme-standards/" target="_blank" rel="noopener">14,801</a> &mdash; a record.</p>

<p>If you&rsquo;re moving with someone who has allergies or asthma, plan for spring before it gets here.</p>

<h2>4. The opportunity is real</h2>

<p>Now here is the important part, and the part I wish I had known most. <strong>Atlanta is an entrepreneurial city.</strong> Atlanta gave me room to grow professionally, build a business, raise my family and create a completely different lifestyle.</p>

<p>So after 27 years, I can honestly say moving here changed my life. But choosing the right area matters.</p>

<h2>Before you move: the first 30 days</h2>

<p>Georgia gives new residents a short clock on a few things. According to the State of Georgia&rsquo;s <a href="https://georgia.gov/moving-georgia" target="_blank" rel="noopener">moving guide</a>:</p>

<ul>
<li><strong>Your driver&rsquo;s license &mdash; within 30 days.</strong> The <a href="https://dds.georgia.gov/georgia-licenses-ids-and-permits/new-license-id-or-permit/new-georgia-residents-and-out-state/how" target="_blank" rel="noopener">Department of Driver Services</a> says that if your current license is valid, you surrender it and pass a vision exam. Bring two proofs of your Georgia address, from separate sources.</li>
<li><strong>Your car &mdash; within 30 days, after the license.</strong> You register at your county tag office, and you need the Georgia license first. New residents pay a one-time title tax of <a href="https://dor.georgia.gov/motor-vehicles/vehicle-registration-license-plates/new-georgia" target="_blank" rel="noopener">3% of the vehicle&rsquo;s fair market value</a>, according to the Department of Revenue. Budget for it.</li>
<li><strong>Voting takes care of itself.</strong> Georgia registers you to vote automatically when you apply for your driver&rsquo;s license.</li>
<li><strong>Bought a home?</strong> If you own it and live in it on January 1, file your homestead exemption by April 1.</li>
</ul>

<h2>Choosing the right area</h2>

<p>If you take one thing from this: don&rsquo;t pick &ldquo;Atlanta.&rdquo; Pick the part of Atlanta that fits how you&rsquo;ll actually live &mdash; your commute, your schools, your budget and your goals. Start with the <a href="/atlanta-metro">Metro Atlanta overview</a>, and if you want help figuring out which community fits you, <a href="/contact">tell me about your move</a>.</p>

<p>So that&rsquo;s moving to Metro Atlanta, explained.</p>

<p><em>This article is general information, not legal or tax advice. Rules and deadlines can change &mdash; confirm the details with the agency linked for each one.</em></p>

<h2>Sources</h2>

<ol>
<li>Erika K. Page, &ldquo;What I wish I knew about relocating&rdquo; (video), her own account.</li>
<li>U.S. Bureau of Labor Statistics, <a href="https://www.bls.gov/regions/southeast/news-release/areaemployment_atlanta.htm" target="_blank" rel="noopener">Atlanta area employment, technical note</a> (July 30, 2025) &mdash; the 29 counties.</li>
<li>U.S. Census Bureau, American Community Survey 2024 1-year, via <a href="https://censusreporter.org/profiles/31000US12060-atlanta-sandy-springs-roswell-ga-metro-area/" target="_blank" rel="noopener">Census Reporter: Atlanta-Sandy Springs-Roswell metro area</a> &mdash; mean travel time to work.</li>
<li>Georgia Department of Transportation, <a href="https://myfiles.dot.ga.gov/Intermodal/Transit/Profile%20Sheets/MARTA.pdf" target="_blank" rel="noopener">MARTA agency profile</a>.</li>
<li>School districts: <a href="https://www.atlantapublicschools.us/" target="_blank" rel="noopener">Atlanta Public Schools</a>, <a href="https://www.csdecatur.net/" target="_blank" rel="noopener">City Schools of Decatur</a>, <a href="https://www.marietta-city.org/" target="_blank" rel="noopener">Marietta City Schools</a>, <a href="https://www.bufordcityschools.org/" target="_blank" rel="noopener">Buford City Schools</a>; Governor&rsquo;s Office of Education and Workforce Strategy, <a href="https://goews.georgia.gov/report-card" target="_blank" rel="noopener">Report Card</a>.</li>
<li>Atlanta Allergy &amp; Asthma, <a href="https://www.atlantaallergy.com/pollen_counts" target="_blank" rel="noopener">Pollen counts</a>.</li>
<li>Atlanta News First, <a href="https://www.atlantanewsfirst.com/2025/03/29/atlanta-shatters-pollen-count-record-rising-far-above-extreme-standards/" target="_blank" rel="noopener">Atlanta shatters pollen count record</a> (March 29, 2025).</li>
<li>State of Georgia, <a href="https://georgia.gov/moving-georgia" target="_blank" rel="noopener">Moving to Georgia</a>.</li>
<li>Georgia Department of Driver Services, <a href="https://dds.georgia.gov/georgia-licenses-ids-and-permits/new-license-id-or-permit/new-georgia-residents-and-out-state/how" target="_blank" rel="noopener">Transfer Out-of-State Driver&rsquo;s License/ID</a>.</li>
<li>Georgia Department of Revenue, <a href="https://dor.georgia.gov/motor-vehicles/vehicle-registration-license-plates/new-georgia" target="_blank" rel="noopener">New Georgia residents: vehicles</a>.</li>
</ol>
HTML,
    ],

    [
        // Researched, not transcribed: every fact is listed with its source in
        // tools/research/georgia-homestead-exemption-explained.md, and the audio is
        // a podcast episode from tools/podcast/<slug>.txt rather than this text read
        // aloud (hence audio_note).
        'slug'           => 'georgia-homestead-exemption-explained',
        'title'          => 'Georgia’s homestead exemption, explained, and why 2027 matters.',
        'seo_title'      => 'Georgia Homestead Exemption Explained: Deadline, the 2026 HOME Act and 2027',
        'seo_desc'       => 'Who qualifies for Georgia\'s homestead exemption, the April 1 deadline, and how the 2026 HOME Act makes the inflation cap on home assessments apply everywhere.',
        'excerpt'        => 'If you own the home you live in and haven&rsquo;t filed for a homestead exemption, you are paying property tax on a break you never claimed. And from 2027, filing does even more: Georgia&rsquo;s new law makes the cap on how fast your home&rsquo;s taxable value can grow apply everywhere.',
        'date'           => '2026-10-05',
        'updated'        => '2026-10-05',
        'author'         => 'Erika K. Page',
        'cat'            => 'buying',
        'tags'           => ['homestead exemption', 'property taxes', 'HOME Act', 'HB 581', 'Georgia real estate', 'Metro Atlanta'],
        'cover'          => 'assets/photos/17/georgia-homestead-exemption-explained-cover.jpg',
        'cover_alt'      => 'Erika K. Page beside the headline: Georgia’s homestead exemption, explained.',
        'cover_credit'   => 'Inset photo by <a href="https://www.pexels.com/@abstrakt-xxcellence-studios-987642" rel="nofollow noopener" target="_blank">Abstrakt Xxcellence Studios</a> on <a href="https://www.pexels.com/" rel="nofollow noopener" target="_blank">Pexels</a>',
        'audio'          => 'assets/audio/georgia-homestead-exemption-explained.mp3',
        'audio_note'     => 'A podcast episode on this topic, in Erika’s AI voice. The sources are listed at the end of the article.',
        'guide'          => '',
        'product'        => '',
        'short'          => 'homestead',
        'audio_secs'     => 390,
        'published'      => true,
        'faq' => [
            [
                'q' => 'When is the Georgia homestead exemption deadline?',
                'a' => 'April 1 for the current tax year. The Georgia Department of Revenue says you can now also apply up to the end of the 45-day window to appeal your assessment notice &mdash; but don&rsquo;t plan around the late window. File by April 1.',
            ],
            [
                'q' => 'I bought my home in 2026. When do I file?',
                'a' => 'You have to own and live in the home on January 1 of the tax year. If you closed any time in 2026, you file for the 2027 tax year, by April 1, 2027.',
            ],
            [
                'q' => 'Do I have to file every year?',
                'a' => 'No. Once approved, most homestead exemptions renew automatically as long as you keep living in the home under the same ownership. If that changes &mdash; you move out, or rent it &mdash; you are expected to tell your county.',
            ],
            [
                'q' => 'Does the HOME Act mean my property taxes can&rsquo;t go up?',
                'a' => 'No. It limits how fast the taxable value of your homestead can grow, to the rate of inflation. It doesn&rsquo;t cap tax rates or total collections, and improvements you add are counted on top. Your bill can still change.',
            ],
        ],
        'body' => <<<'HTML'
<p>If you own the home you live in and you have never filed for a homestead exemption, you are paying property tax on a break you never claimed.</p>

<p>A while back I made a video about a Gwinnett County headline that said property taxes were going up. And I said something I want to come back to: <em>&ldquo;If you have a homestead exemption, or depending on how your property is assessed, your situation can be very, very different from your neighbor.&rdquo;</em> Then I promised you a video on homestead exemptions.</p>

<p>This is that video, in writing This is that &mdash; and the timing is better than I planned, because Georgia just changed the rules. Let me explain.mdash; and it comes at a good moment, because Georgia just changed the rules. Let me explain.</p>

<h2>What a homestead exemption is</h2>

<p>A homestead exemption takes part of your home&rsquo;s value off the table before your property tax is calculated. Georgia has a basic statewide exemption of <a href="https://dor.georgia.gov/property-tax-homestead-exemptions" target="_blank" rel="noopener">$2,000 from county and school taxes</a>, and there are larger ones for homeowners 62 and older, 65 and older, disabled veterans and surviving spouses of service members &mdash; several with income limits.</p>

<p>On top of that, counties and cities offer their own. That is why two neighbors in identical houses can get very different tax bills. One filed. One didn&rsquo;t.</p>

<h2>Who qualifies</h2>

<p>According to the <a href="https://georgia.gov/apply-homestead-exemption" target="_blank" rel="noopener">State of Georgia</a>, four things have to be true:</p>

<ul>
<li>You owned the home on <strong>January 1</strong> of the tax year.</li>
<li>It is your legal residence for all purposes.</li>
<li>You actually live in it.</li>
<li>You are not claiming a homestead exemption on any other property, in Georgia or in any other state.</li>
</ul>

<p>That January 1 date matters more than people think. It is the date that decides which year you can file for.</p>

<h2>When and where to file</h2>

<p>Applications are due by <strong>April 1</strong> for the current tax year. The <a href="https://dor.georgia.gov/property-tax-homestead-exemptions" target="_blank" rel="noopener">Department of Revenue</a> says you can now also apply up to the end of the 45-day window to appeal your assessment notice &mdash; but treat that as a safety net, not a plan.</p>

<p>You don&rsquo;t file with the state. You file with your county&rsquo;s tax office, and each county has its own application and its own list of documents. Typically that means your deed if the county records haven&rsquo;t caught up yet, a Georgia driver&rsquo;s license and your vehicle registration showing the address. In Metro Atlanta, start here: <a href="https://fultonassessor.org/exemptions/" target="_blank" rel="noopener">Fulton</a>, <a href="https://dekalbtaxga.gov/property-tax/exemptions/" target="_blank" rel="noopener">DeKalb</a>, <a href="https://www.cobbtax.gov/property/exemptions.php" target="_blank" rel="noopener">Cobb</a> and <a href="https://www.gwinnetttaxcommissioner.com/property-tax/homestead-exemption" target="_blank" rel="noopener">Gwinnett</a>.</p>

<p>And here is the good news: you usually only do this once. Once approved, most homestead exemptions renew automatically every year as long as you keep living in the home under the same ownership.</p>

[[img:assets/photos/17/b8-southern-porch-home.jpg|A Southern bungalow with rocking chairs on the front porch|Photo by <a href="https://www.pexels.com/@curtis-adams-1694007" rel="nofollow noopener" target="_blank">Curtis Adams</a> on <a href="https://www.pexels.com/" rel="nofollow noopener" target="_blank">Pexels</a>]]

<h2>The 2024 change: a cap on how fast your value can grow</h2>

<p>In 2024 the state passed HB 581, which created what everyone calls the <strong>floating homestead exemption</strong>. The idea is simple once you see it. The county starts from your home&rsquo;s assessed value in a base year. Each year that base is allowed to grow by inflation &mdash; the Consumer Price Index, according to the <a href="https://dor.georgia.gov/media/35186/download" target="_blank" rel="noopener">Department of Revenue</a> &mdash; and anything your assessed value rises above that is exempt.</p>

<p>In plain terms: if the market pushes your home&rsquo;s value up faster than inflation, the extra doesn&rsquo;t get taxed.</p>

<p>But there was a catch. Local governments were allowed to opt out &mdash; and a lot of them did. The <a href="https://taxfoundation.org/blog/georgia-property-tax-reform/" target="_blank" rel="noopener">Tax Foundation</a> counted 68% of school districts and 30% of counties. Here in Metro Atlanta, the Fulton, Gwinnett, Cobb and DeKalb school systems all opted out, and so did the Gwinnett and Cobb county governments. So depending on where you lived, you may have had the cap on part of your bill and not the rest.</p>

<h2>Now here is the important part: the 2026 HOME Act</h2>

<p>On May 11, 2026, Governor Kemp signed <a href="https://gov.georgia.gov/document/2026-signed-legislation/sb-33/download" target="_blank" rel="noopener">Senate Bill 33</a>, the Homeownership Opportunity and Market Equalization Act &mdash; the HOME Act. As <a href="https://www.wabe.org/georgia-governor-signs-income-property-tax-reduction-laws/" target="_blank" rel="noopener">WABE reported</a>, it prevents local governments and school districts from opting out of that inflation cap. The bill itself says it makes the statewide base-year homestead exemption &ldquo;mandatory for all political subdivisions.&rdquo;</p>

<p>Analyses of the law, such as <a href="https://www.ownwell.com/blog/georgia-home-act-senate-bill-33-property-taxes" target="_blank" rel="noopener">this one from Ownwell</a>, expect it to apply everywhere from the <strong>2027</strong> tax year. Your county tax office can confirm how it applies to your bill.</p>

<p>And this is the part I don&rsquo;t want anybody to miss. The cap is not automatic for every house. Under the law, <strong>you only get it if you have filed for a homestead exemption</strong>. And for a new owner, the starting point &mdash; the base year &mdash; is the assessed value for the year before your exemption is first granted.</p>

<p>So if you closed on a home any time in 2026, you own it on January 1, 2027. File by <strong>April 1, 2027</strong>, and your cap starts from your 2026 value. Wait, and you start the clock later, from a higher number.</p>

<h2>What it doesn&rsquo;t do</h2>

<p>Let&rsquo;s be clear about the limits, because headlines won&rsquo;t be.</p>

<ul>
<li><strong>It caps the growth of your home&rsquo;s taxable value, not your tax bill.</strong> Tax rates are still set locally, and the law <a href="https://gbpi.org/sine-die-2026-georgia-rejects-property-tax-caps-and-adds-major-investments-for-gbpi-priorities-eight-year-income-tax-package-threatens-outlook/" target="_blank" rel="noopener">does not cap overall property tax collections</a>.</li>
<li><strong>Improvements count on top.</strong> Add a room or a pool and that added value goes on your assessment outside the cap.</li>
<li><strong>It is tied to you living there.</strong> If you move out or the home stops being your residence, you are expected to tell the county.</li>
</ul>

<p>The law also lets counties ask voters for a new 1% local sales tax dedicated to homestead relief. According to the <a href="https://www.gacities.com/articles/the-2026-session-in-review-what-passed-what-didnt-and-what-it-means" target="_blank" rel="noopener">Georgia Municipal Association</a>, that vote can&rsquo;t happen before November 2027.</p>

<h2>What to do this week</h2>

<ul>
<li><strong>Check that you are filed.</strong> Look up your property on your county&rsquo;s tax site. If it doesn&rsquo;t show a homestead exemption, you&rsquo;re not getting one.</li>
<li><strong>Bought in 2026?</strong> Put April 1, 2027 on your calendar now and have your deed, license and registration ready.</li>
<li><strong>Turning 62 or 65, a veteran, or a surviving spouse?</strong> Ask your county which extra exemptions you qualify for. They don&rsquo;t apply themselves.</li>
</ul>

<p>Your homestead exemption is the reason two identical houses on the same street can carry two very different tax bills. Make sure yours is on file.</p>

<p>So that&rsquo;s the homestead exemption, explained.</p>

<p><em>This article explains how the exemption works; it isn&rsquo;t tax or legal advice. Exemptions and deadlines are set by your county &mdash; confirm the details for your property with your county tax office.</em></p>

<h2>Sources</h2>

<ol>
<li>State of Georgia, <a href="https://georgia.gov/apply-homestead-exemption" target="_blank" rel="noopener">Apply for a Homestead Exemption</a> (updated April 2026).</li>
<li>Georgia Department of Revenue, <a href="https://dor.georgia.gov/property-tax-homestead-exemptions" target="_blank" rel="noopener">Property Tax Homestead Exemptions</a>.</li>
<li>Georgia Department of Revenue, <a href="https://dor.georgia.gov/media/35186/download" target="_blank" rel="noopener">Informational Bulletin 2025-01: Overview of Floating Homestead Exemption and the Annual Inflationary Index Rate</a> (January 22, 2025).</li>
<li>Senate Bill 33 (2026), <a href="https://gov.georgia.gov/document/2026-signed-legislation/sb-33/download" target="_blank" rel="noopener">Homeownership Opportunity and Market Equalization Act of 2026</a>, as signed.</li>
<li>WABE, <a href="https://www.wabe.org/georgia-governor-signs-income-property-tax-reduction-laws/" target="_blank" rel="noopener">Georgia governor signs income, property tax reduction laws</a> (May 11, 2026).</li>
<li>Tax Foundation, <a href="https://taxfoundation.org/blog/georgia-property-tax-reform/" target="_blank" rel="noopener">Localities Opt Out of Georgia&rsquo;s New Homestead Tax Exemption</a> (2025).</li>
<li>Georgia Budget and Policy Institute, <a href="https://gbpi.org/sine-die-2026-georgia-rejects-property-tax-caps-and-adds-major-investments-for-gbpi-priorities-eight-year-income-tax-package-threatens-outlook/" target="_blank" rel="noopener">Sine Die 2026</a>.</li>
<li>Georgia Municipal Association, <a href="https://www.gacities.com/articles/the-2026-session-in-review-what-passed-what-didnt-and-what-it-means" target="_blank" rel="noopener">The 2026 Session in Review</a> (April 7, 2026).</li>
<li>Ownwell, <a href="https://www.ownwell.com/blog/georgia-home-act-senate-bill-33-property-taxes" target="_blank" rel="noopener">What Georgia&rsquo;s HOME Act (Senate Bill 33) Means for Your Property Taxes</a>.</li>
<li>County tax offices: <a href="https://fultonassessor.org/exemptions/" target="_blank" rel="noopener">Fulton</a>, <a href="https://dekalbtaxga.gov/property-tax/exemptions/" target="_blank" rel="noopener">DeKalb</a>, <a href="https://www.cobbtax.gov/property/exemptions.php" target="_blank" rel="noopener">Cobb</a>, <a href="https://www.gwinnetttaxcommissioner.com/property-tax/homestead-exemption" target="_blank" rel="noopener">Gwinnett</a>.</li>
</ol>
HTML,
    ],

    [
        'slug'           => 'cash-is-king-if-you-really-mean-cash',
        'title'          => 'Cash is king, if you really mean cash.',
        'seo_title'      => 'Cash Is King in Real Estate — If You Really Mean Cash',
        'seo_desc'       => 'Paying cash and want a discount? On closing day every offer is cash to the seller. What makes a cash offer worth more, and why contingencies cancel it out.',
        'excerpt'        => '&ldquo;I&rsquo;m paying cash &mdash; can I get a discount?&rdquo; Yes, you can. If you truly mean cash. Here is what a seller actually gets from a cash offer, and why most of them are not as attractive as the buyer thinks.',
        'date'           => '2026-10-02',
        'updated'        => '2026-10-02',
        'author'         => 'Erika K. Page',
        'cat'            => 'buying',
        'tags'           => ['cash offer', 'contingencies', 'buying a home', 'investors', 'Georgia real estate'],
        'cover'          => 'assets/photos/17/cash-is-king-if-you-really-mean-cash-cover.jpg',
        'cover_alt'      => 'Erika K. Page beside the headline: Cash is king, if you really mean cash.',
        'cover_credit'   => 'Inset photo by <a href="https://www.pexels.com/@a-darmel" rel="nofollow noopener" target="_blank">Alena Darmel</a> on <a href="https://www.pexels.com/" rel="nofollow noopener" target="_blank">Pexels</a>',
        'audio'          => 'assets/audio/cash-is-king-if-you-really-mean-cash.mp3',
        'guide'          => '',
        'product'        => '',
        'short'          => 'cash-is-king',
        'audio_secs'     => 231,
        'published'      => true,
        'faq' => [
            [
                'q' => 'Can I get a discount for paying cash?',
                'a' => 'You can ask, and a seller may say yes &mdash; if the offer is truly cash: as-is, without contingencies, and able to close quickly. A cash offer that still needs an appraisal, an inspection and thirty days gives the seller very little they would not get from a buyer with a loan.',
            ],
            [
                'q' => 'How fast can a cash buyer close in Georgia?',
                'a' => 'In my experience, about seven to ten days, because that is how long it takes for title to clear in Atlanta and across Georgia. A cash buyer who needs thirty days is closing on a financed buyer&rsquo;s timeline.',
            ],
            [
                'q' => 'Should I waive my inspection and appraisal to make my cash offer stronger?',
                'a' => 'That is the trade a strong cash offer makes, and it moves the risk from the seller to you. Decide it with your agent, knowing what you are giving up &mdash; never just to win the house.',
            ],
        ],
        'body' => <<<'HTML'
<p>Cash is king. You have heard that before.</p>

<p>And cash <em>is</em> king &mdash; if you really mean cash.</p>

<p>In 24+ years of real estate, I have had this conversation more times than I can count. Somebody is interested in a property. Sometimes it is an investor, sometimes a traditional buyer. And they say: <strong>&ldquo;Well, I&rsquo;m buying with cash, and cash is king. So because I&rsquo;m buying with cash, can I get this discount, that discount, this percentage off?&rdquo;</strong> Usually a significant one.</p>

<p>My answer is always the same. Cash is king, and yes, you can &mdash; if you truly mean cash.</p>

<p>Let me explain.</p>

<h2>On closing day, every offer is cash</h2>

<p>Here is what most buyers miss. Whether you are paying with your own money or with some type of loan, on closing day it is all cash to the seller. The lender&rsquo;s money arrives at the closing table the same as yours would. The seller does not get paid in anything else.</p>

<p>So the cash itself is not what a seller is paying you a discount for. If they are going to take less, they are taking less in exchange for something else &mdash; and that something is <strong>certainty and speed.</strong></p>

[[img:assets/photos/17/a1-closing-table-signing.jpg|A buyer signing paperwork at a closing table|Photo by <a href="https://www.pexels.com/@kampus" rel="nofollow noopener" target="_blank">Kampus Production</a> on <a href="https://www.pexels.com/" rel="nofollow noopener" target="_blank">Pexels</a>]]

<h2>When cash is nothing fancy</h2>

<p>Many times people say cash, but there are a lot of contingencies attached to the cash.</p>

<p>A contingency is a condition in the contract &mdash; something that has to happen, or not happen, before the buyer is bound to close. The common ones are:</p>

<ul>
<li><strong>An appraisal contingency.</strong> If the property appraises for less than the price, the buyer can renegotiate or walk away.</li>
<li><strong>An inspection contingency.</strong> The buyer inspects, and can ask for repairs or credits, or walk away.</li>
<li><strong>A long closing date.</strong> Thirty days or more, which is the same timeline a buyer with a loan usually needs.</li>
</ul>

<p>Every one of those is a perfectly normal thing for a buyer to want. But look at it from the seller&rsquo;s side. If you are coming with cash, but you have an appraisal contingency, an inspection contingency and you need thirty days to close &mdash; <strong>there is nothing fancy about that.</strong> There is nothing about that offer that a financed buyer could not also give them.</p>

<p>Yes, it might be cash. But it is not a glowing benefit. And a seller is not going to take a significant discount for something that is not a benefit.</p>

<h2>What &ldquo;really cash&rdquo; sounds like</h2>

<p>Now here is the important part. When somebody really means cash, it sounds like this:</p>

<blockquote><p>&ldquo;Erika, I want to buy your property. Cash. As-is. And I can close in seven to ten days.&rdquo;</p></blockquote>

<p>As-is means the buyer is taking the property in the condition it is in, without coming back for repairs. Seven to ten days, because in my experience that is how long it takes for title to clear in Atlanta and across the state of Georgia. A buyer who can close that fast is not waiting on anything but the title work.</p>

<p>That offer is free of the conditions that make a sale uncertain. That is what cash being king actually means &mdash; and that is the kind of offer a seller may well accept less for.</p>

<h2>Before you give anything up</h2>

<p>One word of caution, because the strong version of a cash offer works by moving risk. Every contingency you remove is a protection you no longer have. If you skip the appraisal, you carry the risk of overpaying. If you buy as-is, whatever is wrong with the property becomes yours to fix.</p>

<p>Sometimes that is exactly the right trade &mdash; investors make it every day, on purpose, with the numbers in front of them. But make it as a decision with your agent, knowing what you are giving up, and never just to win the house.</p>

<h2>It is not only real estate</h2>

<p>That is the conversation in real estate, but it applies to a lot of other things. &ldquo;I want a discount because I&rsquo;m paying cash.&rdquo; Okay. Then make sure you really mean cash &mdash; free of the strings and conditions that take the value out of it.</p>

<p>Cash is king, but only when you really mean cash.</p>

<p>So that is cash is king, explained.</p>
HTML,
    ],

    [
        'slug'           => 'know-your-house-before-you-list',
        'title'          => 'Nobody should know your house better than you do.',
        'seo_title'      => 'Before You List: Know Your Home Better Than the Buyer',
        'seo_desc'       => 'Your online estimate has never been inside your house, and the buyer\'s inspector will find what you didn\'t. How sellers get there first, on value and condition.',
        'excerpt'        => 'Before you list, two outsiders will tell you about your own house: a website and the buyer&rsquo;s inspector. Neither has ever lived there. Here is how to get there first &mdash; on value, and on condition.',
        'date'           => '2026-10-01',
        'updated'        => '2026-10-01',
        'author'         => 'Erika K. Page',
        'cat'            => 'selling',
        'tags'           => ['selling a home', 'pre-listing inspection', 'home value', 'Metro Atlanta', 'buyer math'],
        'cover'          => 'assets/photos/17/know-your-house-before-you-list-cover.jpg',
        'cover_alt'      => 'Erika K. Page beside the headline: Nobody should know your house better than you do.',
        'cover_credit'   => 'Inset photo by <a href="https://www.pexels.com/@kathleen-austin-kuhn-2152973960" rel="nofollow noopener" target="_blank">Kathleen Austin Kuhn</a> on <a href="https://www.pexels.com/" rel="nofollow noopener" target="_blank">Pexels</a>',
        'audio'          => 'assets/audio/know-your-house-before-you-list.mp3',
        'guide'          => '',
        'product'        => '',
        'short'          => 'before-you-list',
        'audio_secs'     => 314,
        'published'      => true,
        'faq' => [
            [
                'q' => 'Is a pre-listing inspection required?',
                'a' => 'No. It is a choice, not a requirement &mdash; a strategy for finding out about your house before a buyer does, so that pricing and negotiation start from what is true rather than from what somebody else claims.',
            ],
            [
                'q' => 'Will the buyer still get their own inspection?',
                'a' => 'They may, and that is their right. Your report does not replace theirs. What it does is mean that when theirs comes back, very little in it is news to you &mdash; and nothing in it can be inflated without you knowing what it really costs.',
            ],
            [
                'q' => 'How accurate is my online home estimate?',
                'a' => 'Accurate enough to get you in the neighborhood, and no further. It has never been inside your house. On a $400,000 home, being off by just five percent is $20,000 &mdash; which is why a website is a starting point, not a price.',
            ],
            [
                'q' => 'What if the inspection finds something I don&rsquo;t want to fix?',
                'a' => 'Then you price it in, rather than have a buyer discover it and price it in for you. And talk to your agent about disclosure before you decide anything: once you know about a problem, you are expected to treat it as something you know.',
            ],
        ],
        'body' => <<<'HTML'
<p>Before your house sells, two outsiders are going to tell you about it.</p>

<p>The first is a website, with a number. The second is the buyer&rsquo;s inspector, with a report. Neither of them has ever lived there. Neither knows you replaced the roof, or that the water heater has been making that noise for a year. And by the time the second one speaks, you are under contract and on a deadline.</p>

<p>That is the order most sellers do it in. It is the wrong order.</p>

<p>Let me explain.</p>

<h2>The number on the website</h2>

<p>Every home-value site works the same way. An algorithm looks at nearby sales, beds, baths and market trends, and produces a number.</p>

<p>For what it does, it is genuinely useful. It will get you in the neighborhood. <strong>But it has never been inside your house.</strong> It does not know you redid the kitchen. It does not know the roof is new. Public records cannot see what an appraiser or a good agent can see, because they have never walked through the door.</p>

<p>That matters more than it sounds. On a $400,000 home, being off by just five percent is <strong>$20,000</strong>. That is not a rounding error. That is real money, in one direction or the other &mdash; underpriced and you leave it on the table, overpriced and the house sits while everybody else&rsquo;s sells.</p>

<p>An online estimate is a starting point. It is not a professional valuation, and your biggest asset should not be priced off a website alone.</p>

[[img:assets/photos/17/b3-home-for-sale-sign.jpg|A home for sale sign on a front lawn|Photo by <a href="https://www.pexels.com/@thirdman" rel="nofollow noopener" target="_blank">Thirdman</a> on <a href="https://www.pexels.com/" rel="nofollow noopener" target="_blank">Pexels</a>]]

<h2>The report you did not ask for</h2>

<p>Here is how it usually goes. The house is listed. A buyer makes an offer, you accept, and then their inspector walks through with a flashlight and a clipboard. A few days later you get a list.</p>

<p>Now you are negotiating repairs you did not know about, at prices you did not set, against a clock you do not control.</p>

<p>That is why we make sure our sellers have their home inspected <em>before</em> it goes on the market. Same inspection, same flashlight, same list &mdash; just in your hands first. And there are four reasons it changes everything.</p>

<h2>1. You find it first</h2>

<p>Maybe the furnace is not heating the way it should. Maybe the AC is not cooling properly. Maybe there is a slow leak at the back of the sink that nobody has noticed because nobody has looked.</p>

<p>We want to know that ahead of time. <strong>We don&rsquo;t want somebody else telling us about our own house.</strong></p>

<p>A problem you find on your own schedule is a problem. The same problem found by the buyer&rsquo;s inspector, mid-contract, is a bargaining chip &mdash; theirs, not yours.</p>

<h2>2. You price it with confidence</h2>

<p>Say the inspector comes through and everything is beautiful. No problems. Then I can confidently price that house at the higher end of the range, because I know it is problem-free and I can show it.</p>

<p>Now say there are a few issues, and the seller decides not to take care of them. That is a perfectly reasonable decision &mdash; but we know it ahead of time, and we price accordingly. The issue is already in the number instead of becoming a surprise discount later.</p>

<p>Either way, the price comes from what is true about the house rather than from a guess.</p>

<h2>3. Freedom from buyer math</h2>

<p>Buyer math is when a buyer says a repair costs significantly more than it really does. Sometimes it is lack of experience &mdash; they genuinely do not know what a water heater costs. Sometimes it is a negotiation strategy. Either way, the number they give you is not the number it costs.</p>

<p>If you already know about the issue, buyer math stops working, because you already have the real number. And in most cases, taking care of it yourself, with your own contractor at your own price, costs less than the credit a buyer would have asked for.</p>

[[img:assets/photos/17/b2-inspection-checklist.jpg|An inspector holding a completed inspection checklist on a clipboard|Photo by <a href="https://www.pexels.com/@rdne" rel="nofollow noopener" target="_blank">RDNE Stock project</a> on <a href="https://www.pexels.com/" rel="nofollow noopener" target="_blank">Pexels</a>]]

<h2>4. You get to show your work</h2>

<p>When the report comes back clean &mdash; water heater great, furnace great, AC great, the inspector loving the house &mdash; that is not just good news. It is something to promote.</p>

<p>I let buyers know they can buy that house with confidence. And if there were problems here and there, the seller already took care of them. <strong>Here are the receipts. Here is the report.</strong></p>

<p>That changes the tone of the whole sale. The goal is not just to sell your home. It is to sell it with fewer surprises, stronger negotiating power and more confidence on both sides of the table.</p>

<h2>Now here is the important part</h2>

<p>Once you have inspected, you know what you know.</p>

<p>If the report turns up a real problem, it is no longer something you might not have known about. Before you decide whether to fix it, price it in or leave it, talk to your agent about how disclosure works for your sale &mdash; and where it matters, an attorney. That is not a reason to skip the inspection. Not knowing does not make a problem go away; it only means the buyer finds it first.</p>

<p>And a buyer may still order their own inspection. That is their right, and your report does not replace it. What it does is mean that when theirs comes back, very little in it is news to you.</p>

<h2>Value and condition, before anyone else</h2>

<p>Put the two together and the idea is simple. Before the market tells you what your house is worth, get a professional inside it. Before a buyer&rsquo;s inspector tells you what is wrong with it, find out yourself.</p>

<p>Nobody should know your house better than you do.</p>

<p>So that is knowing your house before you list it, explained.</p>
HTML,
    ],

    [
        'slug'           => 'owning-vs-investing-in-real-estate',
        'title'          => 'Owning real estate and investing in it are not the same thing.',
        'seo_title'      => 'Owning Real Estate vs Investing In It',
        'seo_desc'       => 'Most people ask one question about a property. Investors ask four. What it produces, what you can improve, what you control, and what it is worth repositioned.',
        'excerpt'        => 'Most people ask one question about a property: is it a good one? Investors ask four completely different ones &mdash; and the answers, not the photographs, are what decide whether you are owning real estate or investing in it.',
        'date'           => '2026-09-30',
        'updated'        => '2026-09-30',
        'author'         => 'Erika K. Page',
        'cat'            => 'investing',
        'tags'           => ['real estate investing', 'Metro Atlanta', 'property analysis', 'hidden value', 'capital and acquisitions'],
        'cover'          => 'assets/photos/17/owning-vs-investing-in-real-estate-cover.jpg',
        'cover_alt'      => 'Erika K. Page beside the headline: Owning real estate and investing in it are not the same thing.',
        'cover_credit'   => '',
        'audio'          => 'assets/audio/owning-vs-investing-in-real-estate.mp3',
        'guide'          => 'assets/guides/hidden-value-checklist.pdf',
        'product'        => 'hidden-value-checklist',
        'short'          => 'invest',
        'audio_secs'     => 548,
        'published'      => true,
        'faq' => [
            [
                'q' => 'What is the difference between buying a property and investing in one?',
                'a' => 'Buying is a decision about the property. Investing is a decision about what the property <strong>does</strong> &mdash; what it produces, what it costs you to hold, and what it is worth once you have changed something about it. Plenty of people own real estate without ever having made the second decision.',
            ],
            [
                'q' => 'Does this only apply to commercial property?',
                'a' => 'No. The four questions are the same on a single-family rental as on a strip mall &mdash; commercial just makes them impossible to ignore, because the lease puts them in writing. On a house, you have to go looking for the answers yourself.',
            ],
            [
                'q' => 'Is a cheaper property always the better deal?',
                'a' => 'No, and this is the mistake that costs the most. Cheap and affordable are not the same thing. A cheaper property that takes six months of work before it earns anything can easily cost you more than the expensive one that is ready to go &mdash; the question is what you are paying <em>and</em> what you are waiting for.',
            ],
            [
                'q' => 'How do I find out what a property actually produces?',
                'a' => 'Ask for the numbers rather than the story: what it rents for now, what similar places nearby rent for, what the running costs actually are, and what is excluded. If nobody will put those in writing, that is an answer too.',
            ],
        ],
        'body' => <<<'HTML'
<p>There is a real estate lesson hiding inside every Sam&rsquo;s Club, and I am going to use it, because it explains something people get wrong constantly.</p>

<p>Walk through one. Everything in there is about the same three things: buying smart, controlling your costs, and getting more value out of money you were already going to spend. Nobody walks into a warehouse club and falls in love with the lighting.</p>

<p>That is exactly how investors look at real estate. And it is almost never how everybody else looks at it.</p>

<p>Let me explain.</p>

<h2>Most people ask one question. Investors ask four.</h2>

<p>When somebody sends me a listing, the question attached to it is nearly always a version of the same one: <em>is this a good property?</em></p>

<p>It is a fair question. It is also one nobody can answer, because it is missing everything. Good for what? Good at what price? Good compared with what you could do with it?</p>

<p>Here are the four that can be answered:</p>

<ul>
<li><strong>What does it produce?</strong></li>
<li><strong>What can I improve?</strong></li>
<li><strong>What expenses can I control?</strong></li>
<li><strong>What will this be worth after I reposition it?</strong></li>
</ul>

<p>Good investors do not simply buy property. They buy potential, they solve problems, and they create value. That is the difference between owning real estate and investing in it &mdash; and plenty of people who own a rental have never once sat down and answered those four questions about it.</p>

<p>So let us take them one at a time.</p>

<h2>1. What does it produce?</h2>

<p>Not what it is worth. What it <em>produces</em> &mdash; what comes in, how reliably, and from whom.</p>

<p>Commercial real estate is useful here because it refuses to let you avoid the question. I have spent a good part of this year hunting a space for a client opening a West African and West Indian restaurant, and in commercial the production question is written into the lease in black and white. Sometimes the rent is not even a fixed number: depending on the lease type, the monthly rent is based on the receipts &mdash; on how much business you actually do.</p>

<p>Residential hides it better, but the question does not go away. A house you live in produces shelter. A house you rent produces income, on a schedule, from a particular kind of tenant, with a particular likelihood of showing up. Those are not the same asset even if they are the same floor plan.</p>

<p>And production is about location in a way that has nothing to do with whether you would want to live there. Standing on Jonesboro Road in Clayton County looking at a strip mall, the thing I am watching is the traffic. Traffic counts make an enormous difference in retail. This is one of those times where you <em>want</em> traffic &mdash; which is the opposite of what almost everybody wants from a home.</p>

[[img:assets/photos/17/a9-room-under-renovation.jpg|A room part-way through renovation, with a stepladder and tiling in progress|Photo by <a href="https://www.pexels.com/@valentin-ivantsov-2154772556" rel="nofollow noopener" target="_blank">Valentin Ivantsov</a> on <a href="https://www.pexels.com/" rel="nofollow noopener" target="_blank">Pexels</a>]]

<h2>2. What can I improve?</h2>

<p>This is the question that separates a property you are stuck with from a property you can do something about.</p>

<p>Everyone online wants to show you the pristine, turnkey luxury flip. But when you buy the glamour, you are paying a premium for somebody else&rsquo;s aesthetic &mdash; their renovation, their profit, their taste, at retail. When you buy the thing other people cannot see past, you are acquiring raw, untapped equity.</p>

<p>The catch is that not all of it is improvable, and telling the difference is the whole skill. Outdated, unloved and badly presented are cheap to fix and suppress the price far more than they suppress the value. Water, movement, systems and anything that needs permits and specialists are the opposite: they often look like almost nothing and they can run open-ended once somebody opens the wall.</p>

<p>The mistake is never buying an ugly property. It is buying <em>the wrong ugly</em>.</p>

<p>Whatever you are looking at, the improvement question needs a real number attached, not a feeling &mdash; and a real number comes from somebody who will put their name on it, in writing, having actually been inside.</p>

<h2>3. What expenses can I control?</h2>

<p>Here is where I watch people lose money, and it is rarely on the purchase price.</p>

<p>In commercial I check zoning, parking, visibility, the kitchen infrastructure, and the true monthly occupancy cost &mdash; because a space can look perfect, be completely clean, be entirely ready to go, and still be completely wrong for the business. We are not just shopping for a building. We are protecting the business before it ever opens.</p>

<p>That phrase, <strong>true monthly occupancy cost</strong>, is the one worth taking home. Not the rent. Not the mortgage payment. Everything it actually costs to hold the thing for a month, including the parts nobody advertises.</p>

<p>Some of that you can control and some of it you cannot, and knowing which is which before you commit is most of the job. Zoning is a good example. You can get a zoning amendment. You can get a zoning change. But it takes time &mdash; and time is an expense, even when nobody sends you an invoice for it.</p>

<p>Which brings me to the part people find hardest to hear.</p>

<h2>Sometimes things are cheap for a reason</h2>

<p>Earlier this year my client asked me, reasonably, to find something less expensive. I did. It was four thousand dollars a month cheaper than the space we had looked at first, in a beautiful historic downtown area, with a gorgeous door.</p>

<p>It was also completely raw. The first space had been ready to go &mdash; there were still eggs in the cooler. This one had nothing.</p>

<p>She walked in and got overwhelmed, and that led to the conversation that mattered. I asked her how soon she wanted to be open. Thirty days. Well &mdash; you do not have time for a build-out. You need something that is ready to go, and something that is ready to go is not cheap.</p>

<p>So we changed what we were looking for: kitchen equipment in place, grease trap done, hoods done, electrical already there. It will be more per month. And what I told her is the thing I would tell anybody: <strong>the money you put up front is how fast you capture your return.</strong></p>

<p>Cheap and affordable are not the same thing. A lower number attached to a longer wait is not a saving, it is a loan you are making to yourself at a terrible rate.</p>

<h2>4. What is it worth after you reposition it?</h2>

<p>The first three questions are about the property as it stands. This one is about the property as you intend to leave it, and it is the only one that turns work into equity rather than into expense.</p>

<p>When you buy value that other people cannot see, you are buying potential, equity and future cash flow. That is a different purchase from buying a finished thing, and it gets judged differently: not by how it photographs on the day you sign, but by the gap between what it produces now and what it will produce once you have done the work &mdash; minus honestly what the work costs.</p>

<p>If that gap is not there, it is not a deal. It is just a house with a lower price on it.</p>

[[img:assets/photos/17/a8-calculator-property-numbers.jpg|Hands working through property figures with a calculator and a stack of documents|Photo by <a href="https://www.pexels.com/@mikhail-nilov" rel="nofollow noopener" target="_blank">Mikhail Nilov</a> on <a href="https://www.pexels.com/" rel="nofollow noopener" target="_blank">Pexels</a>]]

<h2>Now here is the important part</h2>

<p>Everyone wants the beautiful, turnkey property. The polished flip. The perfect listing. And the biggest opportunities usually do not look impressive &mdash; they are outdated, poorly presented, overlooked, sometimes downright ugly. That is exactly why they are opportunities.</p>

<p>The best investors do not fall in love with appearances. <strong>They fall in love with the numbers.</strong></p>

<p>That is not a personality trait. It is a habit, and it is learnable. It is four questions, asked in order, every single time, including the times the property is charming and you already want it.</p>

<h2>A slow market and a selective one are different things</h2>

<p>Everybody keeps saying the market is slow. Honestly, that is not what I am seeing.</p>

<p>What is really happening is that the market is getting more <em>selective</em>. The deals that make sense are still moving. The properties that are overpriced, over-hyped or poorly positioned are the ones sitting. People look at the ones sitting and conclude nothing is happening.</p>

<p>In capital and acquisitions this is when things get interesting, because the gap between good deals and bad deals becomes abundantly clear. When everything moves, the four questions feel optional. When only some things move, they are the whole difference.</p>

<p>So I am selective in a selective market. Strategy matters far more than speed.</p>

<h2>And if you are not an investor at all</h2>

<p>Ask them anyway.</p>

<p>You do not need a portfolio to benefit from knowing what a property produces, what you could improve, what it really costs to hold each month, and what it would be worth if you did the work. That is just knowing what you are buying. The only difference between you and an investor, on this particular point, is that the investor writes the answers down.</p>

<p>One caution, and I mean it. None of this replaces an inspector, an engineer, a contractor&rsquo;s written quote, or a conversation with somebody who knows the street. It is a way of thinking, not an appraisal. Ask the four questions &mdash; then go and get the real answers from people qualified to give them.</p>

<p>So that is the difference between owning real estate and investing in it, explained.</p>
HTML,
    ],

    [
        'slug'           => 'closing-costs-in-georgia-explained',
        'title'          => 'Closing costs in Georgia, explained.',
        'seo_title'      => 'Closing Costs in Georgia, Explained',
        'seo_desc'       => 'Closing costs are not your down payment. What they actually cover, who pays what in Georgia, why your number differs from your neighbor\'s, and how to avoid surprises.',
        'excerpt'        => 'Closing costs are the most misunderstood number in a home purchase. Here is what they actually cover, who pays what in Georgia, and the four habits that keep the closing table calm.',
        'date'           => '2026-09-29',
        'updated'        => '2026-10-13',
        'author'         => 'Erika K. Page',
        'cat'            => 'buying',
        'tags'           => ['closing costs', 'Georgia real estate', 'first-time buyers', 'Metro Atlanta', 'title insurance'],
        'cover'          => 'assets/photos/17/closing-costs-in-georgia-explained-cover.jpg',
        'cover_alt'      => 'Erika K. Page beside the headline: Closing costs in Georgia, explained.',
        'cover_credit'   => '',
        'audio'          => 'assets/audio/closing-costs-in-georgia-explained.mp3',
        // The text was re-dated on 13 Oct 2026 ("right now" -> "this summer"); the
        // recording from 29 Sep still says "most recent", so the note dates it.
        'audio_note'     => 'The article read aloud in Erika’s AI voice, recorded on September 29, 2026. The market figures in it are from July 2026.',
        'guide'          => 'assets/guides/closing-costs-101.pdf',
        // The product this guide is. The article's offer card takes its cover,
        // name and page count from that record, and the product's own page links
        // back to this article — one field, so the two cannot disagree.
        'product'        => 'closing-costs-101',
        'short'          => 'closing-costs-101',
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

<h2>Where Metro Atlanta stood this summer</h2>

<p>None of this is theory, and the market you are negotiating in decides how much of it you can ask for.</p>

<p>The <a href="https://atlantarealtors.com/resources/news/atlanta-realtors-market-brief-july-2026" rel="nofollow noopener" target="_blank">Atlanta REALTORS&reg; Market Brief</a> for July 2026, covering the eleven-county metro and compiled from FMLS data, put the median sale price at <strong>$445,000</strong>, up 2.1% on the year. There were <strong>20,863 active listings</strong> and a <strong>4.7-month supply</strong>, and homes were averaging <strong>24 days on market</strong>.</p>

<p>Read that as a buyer for a second. Four to five months of supply is a balanced market, not a frenzy. Twenty-four days is not a house selling before you can see it. In a market like that, asking a seller to contribute toward your closing costs is a normal conversation rather than an insult &mdash; and in a market like the one we had three years ago, it wasn&rsquo;t. Markets move month to month, so check the latest numbers before you negotiate.</p>

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
