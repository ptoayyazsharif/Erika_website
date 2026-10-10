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
