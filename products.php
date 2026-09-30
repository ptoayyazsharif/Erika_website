<?php
/**
 * Digital products that ship with the site.
 *
 * Same arrangement as posts.php: this file is the default and anything saved in
 * the admin overrides it, so a product can go live by uploading files with no
 * database write. Merged by slug in products_all().
 *
 * TWO RULES THIS LIST EXISTS TO ENFORCE
 *
 * 1. Nothing is advertised unless it is real. A product is only shown on the
 *    site when `published` is true AND `file` points at a PDF that exists.
 *    products_all() enforces the second half, so a half-finished entry cannot
 *    accidentally go live with a dead button. The page this replaced described
 *    six products that did not exist, behind six buttons that opened a mock-up
 *    alert box.
 *
 * 2. No prices. Erika's own material states no price for anything, so any price
 *    here would be invented. These are free, in exchange for an email.
 *
 * The published three are assets Erika has actually promised on camera, so
 * building them closes a promise rather than inventing a claim. The drafts below
 * them are not yet written; they carry a note about what each one still needs.
 */

return [

    /* ---------- live ---------- */

    [
        'slug'      => 'closing-costs-101',
        'title'     => 'Closing Costs 101',
        'aud'       => 'For Buyers',
        'cat'       => 'buyer',
        'prob'      => 'Solves: meeting your closing costs for the first time three days before you sign.',
        'text'      => 'What closing costs actually cover, who pays what in Georgia, the two state taxes nobody warns you about, and a worksheet for your own numbers.',
        'intro'     => 'Closing costs are the most misunderstood number in a home purchase &mdash; not because they are complicated, but because most people meet them for the first time on a settlement statement, days before they are supposed to sign. This is the whole thing, written down, while nothing is on the line.',
        'inside'    => [
            'Why closing costs are not your down payment',
            'The four buckets every fee falls into &mdash; and why prepaids are not fees',
            'Who pays what, and what is genuinely negotiable',
            'The Georgia transfer tax and intangible recording tax, with a worked example',
            'Earnest money and title insurance, explained',
            'A worksheet for your own numbers, and the questions to ask your lender',
        ],
        'faq' => [
            ['q' => 'Is this only for first-time buyers?',
             'a' => 'No. The buckets are the same on your fourth purchase as your first &mdash; but the two Georgia taxes and the owner&rsquo;s title policy catch out plenty of people who have bought before.'],
            ['q' => 'Does it apply outside Georgia?',
             'a' => 'The structure does. The transfer tax, the intangible recording tax and the attorney-conducted closing are specific to Georgia, so those pages are for buying here.'],
        ],
        'cover'     => 'assets/photos/17/a4-closing-costs-guide-cover.jpg',
        'cover_alt' => 'The Closing Costs 101 guide',
        'file'      => 'assets/guides/closing-costs-101.pdf',
        'pages'     => 8,
        'btn'       => 'Get the guide',
        'short'     => 'closing-costs-guide',
        'published' => true,
    ],

    [
        'slug'      => 'hidden-value-checklist',
        'title'     => 'The Hidden-Value Checklist',
        'aud'       => 'For Investors & Value-Hunters',
        'cat'       => 'wealth',
        'prob'      => 'Solves: paying a premium for someone else&rsquo;s taste instead of buying equity.',
        'text'      => 'The checklist Erika uses to tell a house that is genuinely undervalued from one that is merely cheap &mdash; what to look past, what to never look past, and how to price the work.',
        'intro'     => 'When you buy the glamour, you are paying a premium for someone else&rsquo;s aesthetic. When you buy the ugly, you are acquiring raw, untapped equity. This is how to tell which one is in front of you &mdash; and which ugly is the expensive kind.',
        'inside'    => [
            'Cosmetic ugly versus structural ugly &mdash; the difference that decides everything',
            'The eleven things worth looking past, and the six that should stop you',
            '&ldquo;Buyer math&rdquo;: why a repair estimate is a negotiating position, not a fact',
            'Reading a selective market &mdash; why the right deals still move',
            'A scoring sheet for your own walkthrough',
        ],
        'faq' => [
            ['q' => 'Do I need to be an investor to use this?',
             'a' => 'No. It works just as well on the house you intend to live in &mdash; the difference between a home that needs work and a home that needs rescuing is the same either way.'],
            ['q' => 'Does it tell me what renovations cost?',
             'a' => 'It tells you how to get a real number rather than a guess, and what a number should include. Actual costs move with your market and your contractor, so anyone quoting you fixed figures in a PDF is guessing.'],
        ],
        'cover'     => 'assets/photos/17/a5-hidden-value-checklist-cover.jpg',
        'cover_alt' => 'The Hidden-Value Checklist',
        'file'      => 'assets/guides/hidden-value-checklist.pdf',
        'pages'     => 8,
        'btn'       => 'Get the checklist',
        'short'     => 'hidden-value',
        'published' => true,
    ],

    [
        'slug'      => 'landlord-rent-guide',
        'title'     => 'The Landlord&rsquo;s Guide to Government-Backed Rent',
        'aud'       => 'For Landlords',
        'cat'       => 'wealth',
        'prob'      => 'Solves: chasing rent every month instead of designing a system where it shows up.',
        'text'      => 'How the Housing Choice Voucher programme actually works from the landlord&rsquo;s side &mdash; inspections, rent reasonableness, the paperwork, and the myths that cost owners money.',
        'intro'     => 'Professional landlords do not chase rent. They design systems where rent just shows up. Government-backed rent is one of those systems &mdash; and most of what owners believe about it is either out of date or was never true.',
        'inside'    => [
            'What the Housing Choice Voucher programme is, in plain English',
            'How the money actually reaches you, and when',
            'The inspection: what it checks, and what fails it',
            'Rent reasonableness &mdash; how your rent is set, and what you can do about it',
            'Six myths that cost owners money',
            'A readiness checklist before you apply',
        ],
        'faq' => [
            ['q' => 'Does this apply outside Georgia?',
             'a' => 'The programme is federal, so the structure holds anywhere in the United States. The housing authority you deal with, the payment standards and the waiting lists are local &mdash; the guide says where to look those up.'],
            ['q' => 'Do I have to accept vouchers?',
             'a' => 'That depends on where your property is; some jurisdictions require it and some do not. The guide explains the question rather than answering it for your address, because the answer changes by county.'],
        ],
        'cover'     => 'assets/photos/17/a6-landlord-rent-guide-cover.jpg',
        'cover_alt' => 'The Landlord&rsquo;s Guide to Government-Backed Rent',
        'file'      => 'assets/guides/landlord-rent-guide.pdf',
        'pages'     => 8,
        'btn'       => 'Get the guide',
        'short'     => 'landlord-guide',
        'published' => true,
    ],

    /* ---------- drafts: written but not yet produced ----------
     * These stay invisible on the site until someone writes the document and
     * points `file` at it. Each note says what it still needs.
     */

    [
        // She promises this on camera as "our capital and acquisitions matrix"
        // (comment "deals"). Needs her actual buy-box criteria before it can be
        // written honestly — the material on file is thin.
        'slug' => 'capital-acquisitions-matrix', 'title' => 'The Capital &amp; Acquisitions Matrix',
        'aud' => 'For Investors', 'cat' => 'wealth',
        'prob' => 'Solves: judging a deal by feel instead of by criteria.',
        'text' => 'The criteria Erika runs a potential acquisition through before it gets any further.',
        'cover' => 'assets/photos/15/c8-first-rental-blueprint.jpg', 'cover_alt' => 'The Capital & Acquisitions Matrix',
        'file' => '', 'btn' => 'Get the matrix', 'published' => false,
    ],
    [
        // Needs Erika's own listing-preparation process. Her transcripts cover
        // pre-listing inspections and pricing but not a full seller workflow.
        'slug' => 'seller-strategy-toolkit', 'title' => 'The Seller Strategy Toolkit',
        'aud' => 'For Home Sellers', 'cat' => 'seller',
        'prob' => 'Solves: pricing and preparing without guesswork.',
        'text' => 'Checklists, timelines and the preparation framework behind a listing that holds its price.',
        'cover' => 'assets/photos/15/c5-seller-strategy-toolkit.jpg', 'cover_alt' => 'The Seller Strategy Toolkit',
        'file' => '', 'btn' => 'Get the toolkit', 'published' => false,
    ],
    [
        // Thinnest of all: no video covers relocation in any depth, so the
        // neighbourhood detail would have to be invented. Needs her on tape
        // talking about the submarkets first.
        'slug' => 'atlanta-relocation-guide', 'title' => 'The Atlanta Relocation Guide',
        'aud' => 'For Buyers &amp; Relocators', 'cat' => 'buyer',
        'prob' => 'Solves: choosing the right area from far away.',
        'text' => 'Neighbourhood profiles, commutes and a step-by-step plan for buying in Atlanta from out of state.',
        'cover' => 'assets/photos/15/c6-atlanta-relocation-guide.jpg', 'cover_alt' => 'The Atlanta Relocation Guide',
        'file' => '', 'btn' => 'Get the guide', 'published' => false,
    ],
    [
        // Needs her actual scripts. Do not attach the "$400M in career sales"
        // figure to this product's provenance — that claim is not sourced as a
        // product credential.
        'slug' => 'agent-scripts-systems-pack', 'title' => 'Agent Scripts &amp; Systems Pack',
        'aud' => 'For Agents', 'cat' => 'agent',
        'prob' => 'Solves: winning listings without sounding scripted.',
        'text' => 'Listing presentation flow, objection responses and the follow-up systems behind them.',
        'cover' => 'assets/photos/15/c7-agent-scripts-systems.jpg', 'cover_alt' => 'Agent Scripts & Systems Pack',
        'file' => '', 'btn' => 'Get the pack', 'published' => false,
    ],
    [
        // Well-backed by her mindset material, but it is her mindset pillar
        // rather than real estate. "Most-requested talk" is not a sourced claim
        // and should not be used.
        'slug' => 'reinvention-workbook', 'title' => 'The Reinvention Workbook',
        'aud' => 'Mindset / Reinvention', 'cat' => 'mindset',
        'prob' => 'Solves: knowing your next chapter, not just wanting one.',
        'text' => 'The reflection and planning framework from Erika&rsquo;s reinvention talk, as a guided workbook.',
        'cover' => 'assets/photos/15/c9-reinvention-workbook.jpg', 'cover_alt' => 'The Reinvention Workbook',
        'file' => '', 'btn' => 'Get the workbook', 'published' => false,
    ],
    [
        // CAUTION before publishing: this promises "companion slides, worksheets
        // and replays" from workshops, and there is no record of any workshop
        // having taken place. Confirm with Erika that the events and the
        // materials exist before this is ever shown.
        'slug' => 'workshop-materials-library', 'title' => 'Workshop Materials Library',
        'aud' => 'Workshops &amp; Speaking', 'cat' => 'workshop',
        'prob' => 'Solves: continuing the work after the event ends.',
        'text' => 'Companion slides, worksheets and replays for workshop attendees and teams.',
        'cover' => 'assets/photos/15/c10-workshop-materials.jpg', 'cover_alt' => 'Workshop Materials Library',
        'file' => '', 'btn' => 'View the library', 'published' => false,
    ],

];
