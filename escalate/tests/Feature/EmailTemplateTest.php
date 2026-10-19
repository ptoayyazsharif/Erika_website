<?php

namespace Tests\Feature;

use App\Mail\AccessRevoked;
use App\Mail\ApplicationReceived;
use App\Mail\ApplicationSelected;
use App\Models\Application;
use App\Models\Invite;
use App\Models\User;
use App\Support\EmailTemplates;
use App\Support\Settings;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

/**
 * Every email, worded from the admin panel.
 *
 * The two assertions worth the whole design are here: an admin cannot inject
 * markup into somebody's inbox, and an admin cannot delete the invite code by
 * rewording the paragraph above it.
 */
class EmailTemplateTest extends TestCase
{
    use RefreshDatabase;

    private function application(): Application
    {
        $a = new Application;
        $a->forceFill([
            'name' => 'Amara Okafor', 'email' => 'amara@example.test',
            'changing' => 'a', 'practice' => 'b', 'tried_apps' => 'c',
            'will_use' => 'd', 'will_feedback' => 'e',
            'status' => Application::PENDING,
        ])->save();

        return $a->fresh();
    }

    /** A registry entry with no default would send a blank email. */
    public function test_every_email_in_the_registry_has_a_subject_and_a_body(): void
    {
        foreach (EmailTemplates::keys() as $key) {
            $this->assertNotEmpty(config("escalate.emails.{$key}.subject"), "{$key} has no subject.");
            $this->assertNotEmpty(config("escalate.emails.{$key}.body"), "{$key} has no body.");
        }
    }

    /** And each one is reachable from the admin panel, or it is not editable. */
    public function test_every_email_is_on_the_allowlist(): void
    {
        $keys = Settings::keysFor('emails');

        foreach (EmailTemplates::keys() as $key) {
            $this->assertContains("escalate.emails.{$key}.subject", $keys);
            $this->assertContains("escalate.emails.{$key}.body", $keys);
        }
    }

    public function test_an_override_is_what_arrives(): void
    {
        Config::set('escalate.emails.applied.subject', 'We have your application');
        Config::set('escalate.emails.applied.body', 'Hello **{{ name }}**, we read every one.');

        $mail = new ApplicationReceived($this->application());

        $this->assertSame('We have your application', $mail->envelope()->subject);

        $this->assertStringContainsString(
            '<strong>Amara Okafor</strong>',
            (string) EmailTemplates::body('applied', ['name' => 'Amara Okafor']),
        );

        // The rendered mail runs through a CSS inliner, which puts a style
        // attribute on every tag — so the content is asserted there, and the
        // markup on the parser output above.
        $this->assertStringContainsString('we read every one', $mail->render());
    }

    /**
     * The assertion that lets an admin be trusted with a text box at all.
     *
     * Laravel's mail Markdown escapes HTML input and refuses unsafe links, so
     * neither survives — checked against the rendered output rather than taken
     * on the parser's word.
     */
    public function test_an_admin_cannot_put_markup_or_a_javascript_link_in_somebody_s_inbox(): void
    {
        Config::set('escalate.emails.revoked.body',
            "<script>alert('x')</script>\n\n[press me](javascript:alert('x'))\n\n<img src=x onerror=alert(1)>");

        $body = (string) EmailTemplates::body('revoked');

        // Asserted on elements, not substrings: the escaped text legitimately
        // still contains the characters "onerror=", as visible prose. What
        // matters is that no element was created from them.
        $document = new \DOMDocument;
        libxml_use_internal_errors(true);
        $document->loadHTML('<div>'.$body.'</div>');

        $tags = array_map(
            fn ($node) => $node->nodeName,
            iterator_to_array($document->getElementsByTagName('*')),
        );

        $this->assertNotContains('script', $tags);
        $this->assertNotContains('img', $tags);
        $this->assertStringNotContainsString('javascript:', $body);

        // Escaped and shown, rather than silently swallowed — an admin who
        // pastes something odd should be able to see that they did.
        $this->assertStringContainsString('&lt;script&gt;', $body);

        // And it survives into the real email the same way.
        $this->assertStringContainsString('&lt;script&gt;', (new AccessRevoked($this->application()))->render());
    }

    /** A name with Markdown in it is a name, not formatting. */
    public function test_a_token_value_cannot_smuggle_formatting_in(): void
    {
        $a = $this->application();
        $a->forceFill(['name' => '**Bold** <b>Name</b>'])->save();

        Config::set('escalate.emails.applied.body', 'Hello {{ name }}.');

        $html = (new ApplicationReceived($a->fresh()))->render();

        $this->assertStringNotContainsString('<b>Name</b>', $html);
        $this->assertStringContainsString('**Bold**', $html);
    }

    /**
     * The editable/structural split, proved.
     *
     * An admin who deletes every token — indeed the whole paragraph — still
     * sends an invite somebody can use. The code and the button are in the
     * Blade, out of reach.
     */
    public function test_an_emptied_body_still_sends_a_usable_invite(): void
    {
        Config::set('escalate.emails.selected.body', 'Hi.');

        $a = $this->application();
        $invite = Invite::mint($a->email, 'Founding 25', 30);

        $html = (new ApplicationSelected($a, $invite))->render();

        $this->assertStringContainsString($invite->code, $html);
        $this->assertStringContainsString('Set up your account', $html);
        $this->assertStringContainsString(urlencode($invite->code), str_replace('&amp;', '&', $html));
    }

    /* ── the two that had no template at all ─────────────────────────────── */

    public function test_the_password_reset_email_is_worded_from_the_admin_panel(): void
    {
        Config::set('escalate.emails.password_reset.subject', 'Sort your password out');
        Config::set('escalate.emails.password_reset.body', 'Tap below. Expires in {{ minutes }} minutes.');

        $user = $this->makeUser('reset@escalate.test');
        $mail = (new ResetPassword('a-token'))->toMail($user);

        $this->assertSame('Sort your password out', $mail->subject);

        $html = (string) $mail->render();
        $this->assertStringContainsString('Tap below.', $html);
        $this->assertStringContainsString('Reset password', $html);
        // The link is structural: still there whatever the prose says.
        $this->assertStringContainsString('a-token', $html);
    }

    public function test_the_verification_email_is_worded_from_the_admin_panel(): void
    {
        Config::set('escalate.emails.verify_email.subject', 'One tap please');

        $mail = (new \Illuminate\Auth\Notifications\VerifyEmail)
            ->toMail($this->makeUser('verify@escalate.test'));

        $this->assertSame('One tap please', $mail->subject);
        $this->assertStringContainsString('Confirm this address', (string) $mail->render());
    }

    /* ── the screen ──────────────────────────────────────────────────────── */

    public function test_the_section_renders_for_an_admin_and_404s_for_everybody_else(): void
    {
        $admin = $this->makeUser('boss@escalate.test');
        $admin->forceFill(['role' => 'admin'])->save();

        $this->actingAs($admin->fresh())
            ->get(route('admin.settings.section', 'emails'))
            ->assertOk()
            ->assertSee('Email — You are in')
            ->assertSee('See one in a real inbox');

        $this->actingAs($this->makeUser('nosy@escalate.test'))
            ->get(route('admin.settings.section', 'emails'))
            ->assertNotFound();
    }

    /**
     * The destructive one, extended to the new section. Saving Emails must not
     * switch off a checkbox that lives on another page.
     */
    public function test_saving_the_emails_section_switches_nothing_else_off(): void
    {
        $admin = $this->makeUser('boss2@escalate.test');
        $admin->forceFill(['role' => 'admin'])->save();

        Settings::put('escalate.beta.invite_only', '1');

        $this->actingAs($admin->fresh())->put(route('admin.settings.update'), [
            'section'  => 'emails',
            'settings' => ['escalate__emails__applied__subject' => 'Got it'],
        ])->assertRedirect();

        Settings::apply();

        $this->assertTrue((bool) config('escalate.beta.invite_only'));
        $this->assertSame('Got it', config('escalate.emails.applied.subject'));
    }

    public function test_a_preview_refuses_to_pretend_when_mail_is_switched_off(): void
    {
        Config::set('mail.default', 'log');

        $admin = $this->makeUser('boss3@escalate.test');
        $admin->forceFill(['role' => 'admin'])->save();

        $this->actingAs($admin->fresh())
            ->post(route('admin.settings.email-test', 'applied'))
            ->assertSessionHasErrors('mail');
    }

    public function test_a_preview_of_an_email_that_does_not_exist_is_a_404(): void
    {
        $admin = $this->makeUser('boss4@escalate.test');
        $admin->forceFill(['role' => 'admin'])->save();

        $this->actingAs($admin->fresh())
            ->post(route('admin.settings.email-test', 'not-a-template'))
            ->assertNotFound();
    }
}
