<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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
    public function edit(Request $request): View
    {
        return view('admin.settings', [
            'groups'  => Settings::editable(),
            'display' => collect(Settings::schema())
                ->keys()
                ->mapWithKeys(fn ($key) => [$key => Settings::display($key)])
                ->all(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $schema = Settings::schema();

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

        return redirect()->route('admin.settings')->with('status', 'Saved. These take effect immediately.');
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
    public function testMail(Request $request): RedirectResponse
    {
        $to = $request->user()->email;

        if (config('mail.default') === 'log') {
            return back()->withErrors(['mail' =>
                'The mailer is set to “log”, so nothing is delivered — it is written to the log instead. '
                .'Set a real mailer above before testing.']);
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
