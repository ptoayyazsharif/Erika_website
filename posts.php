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
        'cover_credit'   => 'Inset photo by <a href="https://www.pexels.com/@rdne" rel="nofollow noopener" target="_blank">RDNE Stock project</a> on <a href="https://www.pexels.com/" rel="nofollow noopener" target="_blank">Pexels</a>',
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

[[img:assets/photos/17/a2-suburban-home-for-sale.jpg|A suburban home with a for-sale sign in the front yard|Photo by <a href="https://pixabay.com/users/paulbr75-2938186/" rel="nofollow noopener" target="_blank">paulbr75</a> on <a href="https://pixabay.com/" rel="nofollow noopener" target="_blank">Pixabay</a>]]

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
        'cover_credit'   => 'Inset photo by <a href="https://www.pexels.com/@kampus" rel="nofollow noopener" target="_blank">Kampus Production</a> on <a href="https://www.pexels.com/" rel="nofollow noopener" target="_blank">Pexels</a>',
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
        'updated'        => '2026-09-29',
        'author'         => 'Erika K. Page',
        'cat'            => 'buying',
        'tags'           => ['closing costs', 'Georgia real estate', 'first-time buyers', 'Metro Atlanta', 'title insurance'],
        'cover'          => 'assets/photos/17/closing-costs-in-georgia-explained-cover.jpg',
        'cover_alt'      => 'Erika K. Page beside the headline: Closing costs in Georgia, explained.',
        'cover_credit'   => '',
        'audio'          => 'assets/audio/closing-costs-in-georgia-explained.mp3',
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
