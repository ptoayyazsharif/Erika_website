<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Guards on the service worker's caching rules.
 *
 * These are source assertions rather than behaviour, because the behaviour only
 * exists inside a browser with a registered worker — tests/browser/ proves the
 * real thing. What these buy is that the suite fails fast if the line that
 * caused the outage is ever written again, instead of the mistake riding out to
 * production and pinning every phone that installs the app.
 *
 * The outage, for anyone reading this later: '/js/app.js' and '/css/app.css'
 * were precached by bare path and matched with the query string ignored. The
 * page requests '/js/app.js?v=<content-hash>', so discarding the query turned
 * every request into a hit on the first build ever cached. Nothing evicted it —
 * install() only re-runs when sw.js itself changes — so users stayed on that
 * build through every deploy that followed. Buttons added later simply did not
 * exist in the JavaScript their browser was running.
 */
class ServiceWorkerTest extends TestCase
{
    private function worker(): string
    {
        $path = public_path('sw.js');
        $this->assertFileExists($path, 'The service worker is missing.');

        return file_get_contents($path);
    }

    /**
     * The worker with its comments stripped.
     *
     * Needed because sw.js explains the outage in prose, and the words in that
     * explanation are the words these tests look for. Asserting against the raw
     * file makes the comment describing the bug indistinguishable from the bug
     * — the first version of this test failed on its own documentation.
     */
    private function code(): string
    {
        $source = preg_replace('#/\*.*?\*/#s', '', $this->worker());

        // Not preceded by a colon, so an "https://" inside a string survives.
        return preg_replace('#(?<!:)//.*$#m', '', $source);
    }

    /** The exact mistake, named, so it cannot be reintroduced quietly. */
    public function test_the_worker_never_matches_a_cached_asset_ignoring_its_version(): void
    {
        $this->assertStringNotContainsString(
            'ignoreSearch',
            $this->code(),
            'sw.js matches cached assets with the query string ignored. asset_v() puts the '
            .'content hash in that query, so this serves the first build ever cached to every '
            .'user, for ever — no deploy can reach them again.'
        );
    }

    /**
     * A hashed asset must not be precached by its bare path.
     *
     * Precaching '/js/app.js' stores an entry no page ever asks for — the page
     * asks for '/js/app.js?v=…'. Harmless alone, but it was the entry the
     * version-blind match then served, so the pair is what did the damage.
     */
    public function test_hashed_assets_are_not_precached_by_bare_path(): void
    {
        preg_match('/const SHELL_ASSETS = \[(.*?)\];/s', $this->code(), $matches);
        $this->assertNotEmpty($matches, 'SHELL_ASSETS could not be found in sw.js.');

        foreach (['/js/app.js', '/css/app.css', '/js/gsap.min.js'] as $hashed) {
            $this->assertStringNotContainsString(
                "'{$hashed}'",
                $matches[1],
                "{$hashed} is content-hashed by asset_v(), so precaching it by bare path "
                .'stores a URL nothing requests and invites a version-blind match.'
            );
        }
    }

    /**
     * Every asset the layout loads with ?v= must be handled as versioned.
     *
     * Catches the drift case: someone adds a second stylesheet or script to the
     * layout and it silently falls outside the rule that keeps builds fresh.
     */
    public function test_every_versioned_asset_in_the_layout_is_declared_versioned(): void
    {
        $layout = file_get_contents(resource_path('views/layouts/app.blade.php'));

        preg_match('/const VERSIONED = \[(.*?)\];/s', $this->code(), $declared);
        $this->assertNotEmpty($declared, 'VERSIONED could not be found in sw.js.');

        preg_match_all("/asset_v\('([^']+)'\)/", $layout, $matches);
        $this->assertNotEmpty($matches[1], 'The layout loads nothing through asset_v().');

        foreach (array_unique($matches[1]) as $asset) {
            $this->assertStringContainsString(
                "'/".ltrim($asset, '/')."'",
                $declared[1],
                "The layout loads {$asset} with a content hash, but sw.js does not list it "
                .'in VERSIONED — so a new build of it may never reach an installed client.'
            );
        }
    }

    /** Private routes must stay out of the Cache API entirely. */
    public function test_no_page_or_media_route_is_ever_precached(): void
    {
        preg_match('/const SHELL_ASSETS = \[(.*?)\];/s', $this->code(), $matches);

        foreach (['/today', '/journey', '/stories', '/gratitude', '/rewinds', '/media', '/world'] as $private) {
            $this->assertStringNotContainsString(
                "'{$private}",
                $matches[1],
                "{$private} is in the precache list. Cache Storage is plaintext on disk and "
                .'outlives sign-out, so a journal page must never be written to it.'
            );
        }
    }

    /**
     * The values in My World are typed, not chosen.
     *
     * They were laid out with .options — the class the app uses everywhere for
     * "pick one of these" — which made six bordered boxes, each showing a single
     * word, read as six buttons that ignored every tap.
     */
    public function test_the_values_fields_are_not_dressed_as_a_picker(): void
    {
        $view = file_get_contents(resource_path('views/world/edit.blade.php'));

        preg_match('/What matters most(.*?)<\/div>/s', $view, $matches);
        $this->assertNotEmpty($matches, 'The "What matters most" section could not be found.');

        $this->assertStringNotContainsString('class="options', $matches[1],
            'The values fields use the .options picker class, which makes text inputs look selectable.');
        $this->assertStringContainsString('field-grid', $matches[1]);
        $this->assertStringContainsString('e.g.', $matches[1],
            'The placeholders must read as examples, not as preset values waiting to be tapped.');
    }
}
