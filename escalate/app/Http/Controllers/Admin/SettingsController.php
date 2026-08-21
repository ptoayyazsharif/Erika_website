<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Settings;
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

    /** Drop one override and fall back to whatever the server was deployed with. */
    public function reset(Request $request): RedirectResponse
    {
        $key = (string) $request->input('key');

        abort_unless(Settings::isEditable($key), 404);

        Settings::put($key, null, $request->user());

        return back()->with('status', 'Reset to the deployed value.');
    }
}
