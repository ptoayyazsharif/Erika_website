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
];
