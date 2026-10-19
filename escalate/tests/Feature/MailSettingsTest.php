<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/** Mail configured from the admin panel, and a test that actually sends. */
class MailSettingsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(string $email = 'mailadmin@escalate.test'): User
    {
        $user = $this->makeUser($email, 'Admin');
        $user->forceFill(['role' => 'admin'])->save();

        $this->actingAs($user->fresh())
            ->withSession(['admin.verified' => true, 'admin.verified_at' => now()->timestamp]);

        return $user->fresh();
    }

    public function test_mail_settings_saved_here_reach_the_mailer(): void
    {
        $this->admin();

        $this->put(route('admin.settings.update'), ['settings' => [
            'mail__default' => 'smtp',
            'mail__mailers__smtp__host' => 'smtp.example.test',
            'mail__mailers__smtp__port' => '587',
            'mail__from__address' => 'hello@escalate.cloud',
        ]])->assertRedirect();

        Settings::flush();
        Settings::apply();

        $this->assertSame('smtp', config('mail.default'));
        $this->assertSame('smtp.example.test', config('mail.mailers.smtp.host'));
        $this->assertSame(587, config('mail.mailers.smtp.port'));
        $this->assertSame('hello@escalate.cloud', config('mail.from.address'));
    }

    /** The SMTP password is a secret: set here, never rendered back. */
    public function test_the_smtp_password_is_never_shown_again(): void
    {
        Config::set('mail.mailers.smtp.password', 'super-secret-smtp-password');

        $this->admin();

        $this->get(route('admin.settings.section', 'mail'))
            ->assertOk()
            ->assertDontSee('super-secret-smtp-password')
            ->assertSee('••••word');
    }

    /**
     * The button refuses while the mailer is `log`.
     *
     * That is the state this deployment has been in all along, and it is the
     * one where "it worked" would be most misleading — the log driver succeeds
     * at everything and delivers nothing.
     */
    public function test_the_test_button_refuses_while_mail_only_goes_to_the_log(): void
    {
        Config::set('mail.default', 'log');

        $this->admin();

        $this->post(route('admin.settings.mail'))->assertSessionHasErrors('mail');
    }

    /** It sends to the administrator's own address, never anywhere else. */
    public function test_the_test_email_goes_only_to_the_admin_pressing_it(): void
    {
        Config::set('mail.default', 'array');

        $admin = $this->admin('sender@escalate.test');

        $this->post(route('admin.settings.mail'))->assertRedirect();

        // Mail::raw() is not a Mailable, so the array transport is what can
        // actually be inspected here rather than Mail::assertSent().
        $sent = app('mailer')->getSymfonyTransport()->messages();

        $this->assertCount(1, $sent);

        $recipients = collect($sent[0]->getOriginalMessage()->getTo())
            ->map(fn ($a) => $a->getAddress());

        $this->assertSame([$admin->email], $recipients->all());
    }

    public function test_the_mail_test_is_admin_only(): void
    {
        $this->actingAs($this->makeUser('notadmin@escalate.test'))
            ->post(route('admin.settings.mail'))
            ->assertNotFound();
    }

    /**
     * How mail is sent is picked from a list, not typed.
     *
     * The two values are `smtp` and `log`, neither of which an administrator
     * would guess, and getting it wrong fails in the worst available way:
     * `log` reports success on every screen and delivers nothing.
     */
    public function test_how_mail_is_sent_is_offered_as_a_dropdown(): void
    {
        $this->admin();

        $page = $this->get(route('admin.settings.section', 'mail'))->assertOk();

        $page->assertSee('name="settings[mail__default]"', false)
            ->assertSee('<option value="smtp"', false)
            ->assertSee('<option value="log"', false);

        // And it is a list, not a text box someone can type anything into.
        $this->assertStringNotContainsString(
            '<input class="input" id="mail__default"',
            $page->getContent(),
        );
    }

    /** A value that was never on the list is refused, not coerced. */
    public function test_a_mailer_that_was_never_offered_is_refused(): void
    {
        Config::set('mail.default', 'smtp');

        $this->admin();

        $this->put(route('admin.settings.update'), ['settings' => [
            'mail__default' => 'sendmail',
        ]])->assertSessionHasErrors('settings');

        Settings::flush();
        Settings::apply();

        $this->assertSame('smtp', config('mail.default'));
    }

    /**
     * A stored value that has since left the list stops applying.
     *
     * Shrinking the options is how a driver gets retired; the row for it can
     * outlive the change, and forcing config() to a mailer Laravel no longer
     * has configured would take the app down on the first send.
     */
    public function test_an_override_that_left_the_list_falls_back(): void
    {
        Config::set('mail.default', 'smtp');

        Settings::put('mail.default', 'log');
        Settings::apply();
        $this->assertSame('log', config('mail.default'));

        // Simulate the option being withdrawn by writing past the allowlist
        // the way a leftover row would read.
        \App\Models\Setting::where('key', 'mail.default')
            ->update(['value' => 'sendmail']);

        Settings::flush();
        Config::set('mail.default', 'smtp');
        Settings::apply();

        $this->assertSame('smtp', config('mail.default'));
    }
}
