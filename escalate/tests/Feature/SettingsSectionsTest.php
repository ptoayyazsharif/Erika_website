<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

/**
 * Settings split across pages, without switching anything off.
 *
 * Thirteen groups on one page had become daunting to open, which is a
 * reliability problem as much as a design one — a screen nobody wants to
 * scroll is a screen where the wrong field gets changed.
 */
class SettingsSectionsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $user = $this->makeUser('sections@escalate.test', 'Admin');
        $user->forceFill(['role' => 'admin'])->save();

        $this->actingAs($user->fresh())
            ->withSession(['admin.verified' => true, 'admin.verified_at' => now()->timestamp]);

        return $user->fresh();
    }

    /* ── the split itself ────────────────────────────────────────────────── */

    /** Nothing may be stranded: every setting lives on exactly one page. */
    public function test_every_setting_appears_on_exactly_one_section(): void
    {
        $seen = [];

        foreach (array_keys(Settings::sections()) as $section) {
            foreach (Settings::keysFor($section) as $key) {
                $this->assertArrayNotHasKey($key, $seen, "{$key} is on two pages.");
                $seen[$key] = $section;
            }
        }

        $missing = array_diff(array_keys(Settings::schema()), array_keys($seen));

        $this->assertSame([], array_values($missing),
            'Unreachable from any page: '.implode(', ', $missing));
    }

    public function test_the_index_lists_the_sections_and_each_one_opens(): void
    {
        $this->admin();

        $index = $this->get(route('admin.settings'))->assertOk();

        foreach (Settings::sections() as $key => $section) {
            $index->assertSee($section['label']);
            $this->get(route('admin.settings.section', $key))->assertOk()->assertSee($section['label']);
        }
    }

    public function test_an_invented_section_is_a_404(): void
    {
        $this->admin();

        $this->get(route('admin.settings.section', 'nonsense'))->assertNotFound();
    }

    /* ── the one that matters ────────────────────────────────────────────── */

    /**
     * Saving one page must not switch off checkboxes on another.
     *
     * SettingsController treats an ABSENT checkbox as "off" — correct when one
     * page holds every checkbox, catastrophic the moment it does not. Without
     * scoping, saving Mail would have turned off invite-only, email
     * confirmation and billing, and the only evidence would have been an open
     * beta and a provider bill.
     */
    public function test_saving_one_section_leaves_every_other_switch_alone(): void
    {
        Config::set('escalate.beta.invite_only', true);
        Config::set('escalate.beta.require_verification', true);
        Config::set('escalate.billing.enabled', true);

        // Store them as real overrides, the state Erika's app is actually in.
        Settings::put('escalate.beta.invite_only', '1');
        Settings::put('escalate.beta.require_verification', '1');
        Settings::put('escalate.billing.enabled', '1');

        $this->admin();

        // Save the Mail page. It carries no checkbox for any of the three.
        $this->put(route('admin.settings.update'), [
            'section' => 'mail',
            'settings' => ['mail__from__name' => 'Escalate'],
        ])->assertRedirect();

        Settings::flush();
        Settings::apply();

        $this->assertTrue(config('escalate.beta.invite_only'), 'Saving Mail opened up registration.');
        $this->assertTrue(config('escalate.beta.require_verification'), 'Saving Mail switched off email confirmation.');
        $this->assertTrue(config('escalate.billing.enabled'), 'Saving Mail switched off billing.');

        // And it did save the thing it was actually asked to.
        $this->assertSame('Escalate', config('mail.from.name'));
    }

    /** A checkbox on the page being saved still turns off when unticked. */
    public function test_a_checkbox_on_the_saved_page_still_turns_off(): void
    {
        Settings::put('escalate.beta.invite_only', '1');

        $this->admin();

        $this->put(route('admin.settings.update'), [
            'section' => 'access',
            'settings' => [],   // both boxes unticked
        ])->assertRedirect();

        Settings::flush();
        Settings::apply();

        $this->assertFalse(config('escalate.beta.invite_only'));
    }

    /**
     * A posted section cannot reach a key that is not on it.
     *
     * The allowlist is still the boundary; this is the second lock on it.
     */
    public function test_a_field_from_another_section_is_ignored(): void
    {
        Settings::put('escalate.anthropic.key', 'sk-ant-original');

        $this->admin();

        $this->put(route('admin.settings.update'), [
            'section' => 'mail',
            'settings' => ['escalate__anthropic__key' => 'sk-ant-injected'],
        ])->assertRedirect();

        Settings::flush();
        Settings::apply();

        $this->assertSame('sk-ant-original', config('escalate.anthropic.key'));
    }

    /* ── still admin-only ────────────────────────────────────────────────── */

    public function test_the_sections_are_invisible_to_everyone_else(): void
    {
        $this->actingAs($this->makeUser('nosy@escalate.test'));

        $this->get(route('admin.settings'))->assertNotFound();

        foreach (array_keys(Settings::sections()) as $section) {
            $this->get(route('admin.settings.section', $section))->assertNotFound();
        }
    }
}
