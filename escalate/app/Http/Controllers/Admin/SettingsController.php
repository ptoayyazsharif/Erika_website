<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\EmailTemplates;
use App\Support\Settings;
use App\Support\StripeCheck;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The settings screen.
 *
 * Two rules do all the work here, and both live in App\Support\Settings:
 * only allowlisted keys can be written, and secrets are never sent back to the
 * browser. This controller is the thin part.
 */
class SettingsController extends Controller
{
    /** The index: the sections, and nothing else to scroll past. */
    public function index(Request $request): View
    {
        return view('admin.settings.index', ['sections' => Settings::sections()]);
    }

    /** One section's page. An unknown key 404s rather than showing everything. */
    public function edit(Request $request, string $section): View
    {
        abort_unless(array_key_exists($section, Settings::sections()), 404);

        $groups = Settings::groupsFor($section);

        return view('admin.settings', [
            'section'  => $section,
            'meta'     => Settings::sections()[$section],
            'sections' => Settings::sections(),
            'groups'   => $groups,
            'display'  => collect(Settings::keysFor($section))
                ->mapWithKeys(fn ($key) => [$key => Settings::display($key)])
                ->all(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $section = scalar_input($request->input('section')) ?: null;

        /*
         * Only the settings that were ON THE PAGE THAT WAS SAVED.
         *
         * This is not a tidiness measure, it is the whole reason splitting the
         * settings screen is safe. The loop below treats an absent checkbox as
         * "off", which is right when one page carries every checkbox and
         * catastrophic the moment it does not: saving Mail would have switched
         * off invite-only, email confirmation and billing, none of which were
         * on that form, and the only evidence would have been an open beta.
         */
        $schema = array_intersect_key(
            Settings::schema(),
            array_flip(Settings::keysFor($section)),
        );

        /*
         * The posted field names are config keys with dots replaced, because a
         * dot in an input name is array syntax to PHP. Mapping back through the
         * allowlist rather than str_replace'ing the dots back means a field
         * name that does not correspond to a known setting simply has no key to
         * map to — it cannot be turned into a config path by construction.
         */
        $posted = (array) $request->input('settings', []);

        foreach ($schema as $key => $meta) {
            $field = str_replace('.', '__', $key);

            if ($meta['type'] === 'bool' || $meta['type'] === 'mode') {
                // A checkbox that is off sends nothing at all, so absence is
                // meaningful here in a way it is not for the other types.
                Settings::put($key, array_key_exists($field, $posted) ? '1' : '0', $request->user());

                continue;
            }

            if (! array_key_exists($field, $posted)) {
                continue;
            }

            $value = is_scalar($posted[$field]) ? trim((string) $posted[$field]) : '';

            // A blank secret means "leave it alone", not "delete it". The form
            // never renders the current value, so an untouched secret field is
            // always empty — treating that as a deletion would wipe the API key
            // of anyone who saved this page to change a quota.
            if ($meta['type'] === 'secret' && $value === '') {
                continue;
            }

            if ($meta['type'] === 'choice') {
                // The dropdown only offers these, so anything else was hand
                // crafted. Reject rather than coerce: silently picking a
                // neighbouring value is how a mailer ends up set to something
                // nobody chose.
                if (! array_key_exists($value, $meta['options'])) {
                    return back()->withErrors([
                        'settings' => "“{$meta['label']}” has to be one of the offered choices.",
                    ]);
                }

                Settings::put($key, $value, $request->user());

                continue;
            }

            if ($meta['type'] === 'int') {
                if (! is_numeric($value) || (int) $value < 0) {
                    return back()->withErrors([
                        'settings' => "“{$meta['label']}” has to be a whole number, zero or more.",
                    ]);
                }

                $value = (string) (int) $value;
            }

            Settings::put($key, $value, $request->user());
        }

        return redirect()
            ->route('admin.settings.section', ['section' => $section ?? 'look'])
            ->with('status', 'Saved. These take effect immediately.');
    }

    /**
     * Check the Stripe configuration, without changing anything.
     *
     * Read-only on both sides: it retrieves from Stripe and writes nothing
     * there, and it touches no table in this application beyond reading the
     * plans' price ids. Pressing it cannot cost money, cannot create a
     * customer, and cannot alter a single row here.
     */
    public function stripe(Request $request): RedirectResponse
    {
        return back()->with('stripe_check', StripeCheck::run());
    }

    /**
     * Send one real email to the administrator pressing the button.
     *
     * The only honest test of mail is mail that arrives. A configuration that
     * looks right and silently fails is the normal failure here — the app has
     * been reporting "a reset link is on its way" and sending nothing for as
     * long as MAIL_MAILER has said `log`.
     *
     * Sent to the administrator's own address so this cannot be used to send
     * anything to anyone else.
     */
    /**
     * Send one real email to yourself, with the wording as it stands.
     *
     * Sample values stand in for the tokens: the point is to see the sentences
     * and the layout, and building a throwaway Application to render a preview
     * would mean writing to the database to look at a paragraph.
     *
     * It refuses on the `log` mailer for the same reason testMail() does — a
     * test that quietly writes to a file is how password reset came to be
     * broken for weeks without anybody noticing.
     */
    public function testEmail(Request $request, string $key): RedirectResponse
    {
        abort_unless(EmailTemplates::exists($key), 404);

        if (config('mail.default') === 'log') {
            return back()->withErrors(['mail' =>
                '“How mail is sent” is set to “do not send”, so this would be written to the log '
                .'and delivered to nobody. Change it under Settings → Mail first.']);
        }

        $to = $request->user()->email;

        $sample = [
            'name'    => $request->user()->name ?: 'Amara Okafor',
            'email'   => $to,
            'expires' => now()->addDays(30)->format('j F Y'),
            'minutes' => '60',
        ];

        try {
            \Illuminate\Support\Facades\Mail::send(
                'mail.auth-action',
                [
                    'body'   => EmailTemplates::body($key, $sample),
                    'action' => 'Where a button would go',
                    'url'    => config('app.url'),
                ],
                fn ($m) => $m->to($to)->subject('[preview] '.EmailTemplates::subject($key, $sample)),
            );
        } catch (\Throwable $e) {
            return back()->withErrors(['mail' => 'Sending failed: '.\Illuminate\Support\Str::limit($e->getMessage(), 200)]);
        }

        return back()->with('status',
            "Preview of “{$key}” sent to {$to}. Codes and buttons are stand-ins — the real email fills them in.");
    }

    public function testMail(Request $request): RedirectResponse
    {
        $to = $request->user()->email;

        if (config('mail.default') === 'log') {
            return back()->withErrors(['mail' =>
                '“How mail is sent” is set to “do not send”, so this would be written to the log '
                .'and delivered to nobody. Change it to “Send it”, fill in the SMTP details, save, then test.']);
        }

        try {
            \Illuminate\Support\Facades\Mail::raw(
                "This is a test from Escalate.\n\n"
                ."If you are reading it, password reset and email verification will reach people too.\n\n"
                .'Sent '.now()->toDayDateTimeString().' from '.config('app.url'),
                fn ($m) => $m->to($to)->subject('Escalate — mail is working'),
            );
        } catch (\Throwable $e) {
            return back()->withErrors(['mail' => 'Sending failed: '.\Illuminate\Support\Str::limit($e->getMessage(), 200)]);
        }

        return back()->with('status',
            "Handed to the mail server for {$to}. It arriving is the real test — check the inbox, and the spam folder.");
    }

    /** Drop one override and fall back to whatever the server was deployed with. */
    public function reset(Request $request): RedirectResponse
    {
        $key = scalar_input($request->input('key'));

        abort_unless(Settings::isEditable($key), 404);

        Settings::put($key, null, $request->user());

        return back()->with('status', 'Reset to the deployed value.');
    }
}
