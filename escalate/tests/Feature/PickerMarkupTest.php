<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * The custom radio/checkbox patterns and the script that paints them must
 * agree about which class names exist.
 *
 * Erika reported the feelings picker as "none of these buttons worked, I could
 * never take it off of calm". The cause was a mismatch nothing else could
 * catch: the markup uses two wrapper classes, `.option` and `.chip`, and
 * initOptions() in app.js only listened to `.option input`. The chips'
 * checkboxes toggled underneath — the label wraps the input, so the browser
 * did its job — while the visible `is-on` class never changed.
 *
 * That made it worse than dead. Every press really did flip the value, so the
 * state submitted could be the opposite of the state on screen, and an odd
 * number of presses saved the feeling she had been trying to remove.
 *
 * A PHP test cannot click anything, so this asserts the contract instead:
 * every wrapper class used in the views is one the script knows about. It is
 * cheap, and it fails the moment someone adds a third pattern or narrows the
 * selector back.
 */
class PickerMarkupTest extends TestCase
{
    public function test_every_picker_wrapper_in_the_views_is_handled_by_the_script(): void
    {
        $js = file_get_contents(public_path('js/app.js'));

        // The single place the script names its wrappers.
        $this->assertMatchesRegularExpression(
            "/const PICKER = '[^']*\.option[^']*'/",
            $js,
            'app.js no longer declares a PICKER selector covering .option',
        );
        $this->assertMatchesRegularExpression(
            "/const PICKER = '[^']*\.chip[^']*'/",
            $js,
            'app.js stopped handling .chip — the feelings and tag pickers will look inert again.',
        );

        // And every view that hides an input inside a wrapper uses one of them.
        $views = glob(resource_path('views/**/*.blade.php'))
            + glob(resource_path('views/*.blade.php'));

        foreach ($views as $view) {
            $markup = file_get_contents($view);

            preg_match_all('/<label class="([a-z0-9 _-]+)"[^>]*>\s*<input/i', $markup, $matches);

            foreach ($matches[1] as $classes) {
                $classList = preg_split('/\s+/', trim($classes));

                $this->assertNotEmpty(
                    array_intersect($classList, ['option', 'chip']),
                    basename($view).' wraps an input in a label with classes "'.$classes
                        .'", which app.js does not paint. Use .option or .chip.',
                );
            }
        }
    }
}
