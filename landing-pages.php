<?php
/**
 * Landing pages that ship with the site (see lib/landing.php).
 *
 * Like posts.php and products.php, these are only the defaults: once a page is
 * saved in the admin (Landing pages), the saved copy wins and edits here to the
 * same address no longer show.
 *
 * A page goes live only when it is published AND has its file (or link). The
 * file is uploaded in the admin, because it is kept outside the public folders.
 */
return [

    // Copied from Erika's Stan Store lead magnet of the same name (20 Oct 2026):
    // title, description, bottom title, button and every question, word for word.
    [
        'slug'         => '1039-mariners-court',
        'title'        => 'Get the 1039 Mariners Court Investor Package',
        'desc'         => '<p><strong>4 Bedrooms | 4 Bathrooms | Approx. 2,261 SF</strong><br><strong>Stone Mountain, GA</strong><br><strong>Three-Level Furnished Investment Opportunity</strong></p>',
        'image'        => '',
        'image_alt'    => '1039 Mariners Ct, Stone Mountain, GA 30083: a furnished three-level townhouse-style condominium',
        'bottom_title' => 'Mariners',
        'cta'          => 'Send Me the Investor Package',
        'fields'       => [
            ['id' => 'mobile', 'type' => 'phone', 'label' => 'Mobile Number', 'required' => true],
            ['id' => 'strategy', 'type' => 'select', 'label' => 'What investment strategy are you considering?', 'required' => true,
             'options' => ['Flip', 'Buy & Hold', 'Executive or mid-term rental', 'Multigenerational or specialty rental', 'Open to multiple strategies']],
            ['id' => 'funding', 'type' => 'select', 'label' => 'How do you plan to fund the purchase?', 'required' => true,
             'options' => ['Cash', 'Hard Money', 'DSCR', 'Conventional or commercial financing', 'Private financing', 'Financing not yet secured']],
            ['id' => 'timeline', 'type' => 'select', 'label' => 'What is your anticipated closing timeline?', 'required' => true,
             'options' => ['7–14 days', '15–21 days', '22–30 days', 'More than 30 days', 'Still evaluating']],
            ['id' => 'pof', 'type' => 'select', 'label' => 'Can you provide current proof of funds or lender approval before scheduling property access?', 'required' => true,
             'options' => ['Yes, immediately', 'Yes, within 24 hours', 'I need financing assistance']],
            ['id' => 'access', 'type' => 'checkbox', 'label' => 'Property Access Agreement', 'required' => true,
             'options' => ['I understand that the property is being offered AS-IS. I agree not to visit the property, contact the occupants or enter the property without a confirmed appointment. I understand that current proof of funds or lender approval is required before property access will be scheduled']],
        ],
        'deliver'      => 'file',
        'file'         => '',
        'file_name'    => '1039 Mariners Merged.pdf',
        'thanks_title' => 'Your investor package is ready.',
        'thanks_text'  => 'Download it below. Keep your proof of funds or lender approval ready: it is required before property access is scheduled.',
        'consent'      => 'By sending this form you agree that Erika K. Page of Axen Realty may contact you about this property by phone, text or email.',
        'email_lead'   => true,
        'crm_on'       => true,
        'crm_source'   => 'Website – 1039 Mariners Ct',
        'crm_tags'     => 'Website, Landing Page, Investor, 1039 Mariners Ct',
        'crm_type'     => 6,
        'indexable'    => false,
        'published'    => false,
        'updated'      => '2026-10-20',
    ],

    // The same package for 1896 Alcovy Bluff Way (the owner, 22 Oct 2026: "make same
    // package as well but don't publish it yet"). Only the address came with the request:
    // the questions, button, consent and thank-you text are the Mariners page's, and the
    // description carries no beds, baths or size until the owner supplies them.
    [
        'slug'         => '1896-alcovy-bluff-way',
        'title'        => 'Get the 1896 Alcovy Bluff Way Investor Package',
        'desc'         => '<p><strong>1896 Alcovy Bluff Way</strong><br><strong>Lawrenceville, GA 30045</strong><br><strong>Investment Opportunity</strong></p>',
        'image'        => '',
        'image_alt'    => '1896 Alcovy Bluff Way, Lawrenceville, GA 30045',
        'bottom_title' => 'Alcovy Bluff',
        'cta'          => 'Send Me the Investor Package',
        'fields'       => [
            ['id' => 'mobile', 'type' => 'phone', 'label' => 'Mobile Number', 'required' => true],
            ['id' => 'strategy', 'type' => 'select', 'label' => 'What investment strategy are you considering?', 'required' => true,
             'options' => ['Flip', 'Buy & Hold', 'Executive or mid-term rental', 'Multigenerational or specialty rental', 'Open to multiple strategies']],
            ['id' => 'funding', 'type' => 'select', 'label' => 'How do you plan to fund the purchase?', 'required' => true,
             'options' => ['Cash', 'Hard Money', 'DSCR', 'Conventional or commercial financing', 'Private financing', 'Financing not yet secured']],
            ['id' => 'timeline', 'type' => 'select', 'label' => 'What is your anticipated closing timeline?', 'required' => true,
             'options' => ['7–14 days', '15–21 days', '22–30 days', 'More than 30 days', 'Still evaluating']],
            ['id' => 'pof', 'type' => 'select', 'label' => 'Can you provide current proof of funds or lender approval before scheduling property access?', 'required' => true,
             'options' => ['Yes, immediately', 'Yes, within 24 hours', 'I need financing assistance']],
            ['id' => 'access', 'type' => 'checkbox', 'label' => 'Property Access Agreement', 'required' => true,
             'options' => ['I understand that the property is being offered AS-IS. I agree not to visit the property, contact the occupants or enter the property without a confirmed appointment. I understand that current proof of funds or lender approval is required before property access will be scheduled']],
        ],
        'deliver'      => 'file',
        'file'         => '',
        'file_name'    => '1896 Alcovy Bluff Way Investor Package.pdf',
        'thanks_title' => 'Your investor package is ready.',
        'thanks_text'  => 'Download it below. Keep your proof of funds or lender approval ready: it is required before property access is scheduled.',
        'consent'      => 'By sending this form you agree that Erika K. Page of Axen Realty may contact you about this property by phone, text or email.',
        'email_lead'   => true,
        'crm_on'       => true,
        'crm_source'   => 'Website – 1896 Alcovy Bluff Way',
        'crm_tags'     => 'Website, Landing Page, Investor, 1896 Alcovy Bluff Way',
        'crm_type'     => 6,
        'indexable'    => false,
        'published'    => false,
        'updated'      => '2026-10-22',
    ],

    // VA services (Erika through her VA, 29 Oct 2026: "she wants to promote VA services as well …
    // a landing/splash page on her website for lead capture and promotion"). The quotes are hers
    // ("You need a VA", 23–24 Oct; her meeting of 27 Oct). The services come from the team's VA
    // services flyer, without its brand (Dot.kode) until Erika confirms how it relates to her, and
    // never NSOL BPO. No prices. The download is the VA Wish List (tools/guides/va-wish-list.html),
    // a public PDF, so the page can go live the moment it is published.
    [
        'slug'         => 'va-services',
        'title'        => 'Need a VA? Get the VA Wish List',
        'desc'         => '<p><strong>&ldquo;My amazing virtual assistant is taking my eventually list, my checklist, my to-do list, and turning it into a to-done list.&rdquo;</strong></p>'
                        . '<p>CPA, medical or dental office, salon, designer, agent, or just busy? Get the free VA Wish List: everything a virtual assistant can take off your plate, from your calendar and follow-up to your social media, video editing and phones. On average $4 to $8 an hour, or by the project.</p>'
                        . '<p>AI is amazing, but AI needs an AI operator. One 10-minute conversation with my VA and you can have 50 things done. Don&rsquo;t wait: it is cost efficient, and it is amazing to just know that things are being done.</p>'
                        . '<p><strong>Prefer to talk? Call (678) 404-1562.</strong></p>',
        // The link preview: a professional VA, not Erika (her ask, 30 Oct 2026). MART PRODUCTION, Pexels 7709287.
        'image'        => 'assets/photos/va/va-at-work.jpg',
        'image_alt'    => 'A virtual assistant at work',
        // The long VA design (lib/landing-va.php), asked for on 29 Oct 2026 after the standard page was rejected.
        'layout'       => 'va',
        // Its own company on its own domain (Erika, 30 Oct 2026); the domain goes here once she picks one.
        'domain'       => '',
        'brand'        => 'VA Services',
        'bottom_title' => 'Get the VA Wish List',
        'cta'          => 'Get the Free VA Wish List',
        'fields'       => [
            ['id' => 'mobile', 'type' => 'phone', 'label' => 'Mobile Number', 'required' => true],
            ['id' => 'who', 'type' => 'select', 'label' => 'What best describes you?', 'required' => true,
             'options' => ['CPA or accounting', 'Medical or dental office', 'Salon owner', 'Fashion designer', 'Real estate agent or team', 'Small business owner', 'Busy professional or parent', 'Something else']],
            ['id' => 'tasks', 'type' => 'checkbox', 'label' => 'What would you hand off first?', 'required' => false,
             'options' => ['Calendar and inbox', 'Lead follow-up and client communication', 'Social media and video editing', 'Marketing support', 'Research and data entry', 'Admin tasks', 'Answering calls and setting appointments', 'Cold calling', 'Medical billing', 'CRM and database', 'Website, profiles and automations', 'A command center for my business', 'Household and personal errands', 'Real estate: listings and transactions']],
            ['id' => 'hours', 'type' => 'select', 'label' => 'How much help do you think you need?', 'required' => true,
             'options' => ['A few hours a week', '10–20 hours a week', '20–40 hours a week', 'Full-time', 'Not sure yet']],
            ['id' => 'start', 'type' => 'select', 'label' => 'When would you like to start?', 'required' => true,
             'options' => ['Right away', 'Within 30 days', 'Just exploring']],
            ['id' => 'notes', 'type' => 'textarea', 'label' => 'Anything else I should know?', 'required' => false],
        ],
        'deliver'      => 'url',
        'url'          => 'https://erikakpage.com/assets/guides/va-wish-list.pdf',
        'file'         => '',
        'file_name'    => 'The VA Wish List',
        'thanks_title' => 'Your VA Wish List is ready.',
        'thanks_text'  => 'Open it below, tick what you would hand off, and we will reach out to talk it through. Can\'t wait? Call (678) 404-1562.',
        'consent'      => 'By sending this form you agree that our team may contact you about virtual assistant and call center services by phone, text or email.',
        'email_lead'   => true,
        'crm_on'       => true,
        'crm_source'   => 'Website – VA Services',
        'crm_tags'     => 'Website, Landing Page, VA Services',
        'crm_type'     => 0,
        'indexable'    => true,
        'published'    => false,
        'updated'      => '2026-10-30',
    ],
];
