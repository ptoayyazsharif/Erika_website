<?php

namespace App\Support;

use App\Models\User;

/**
 * Which theme a page wears.
 *
 * Two questions, and they have different answers: what a signed-in person
 * chose, and what a stranger gets. The second used to be a hardcoded
 * 'midnight' inside active_theme(), which meant the landing page, the
 * application form and the sign-in screen were all a colour nobody picked and
 * nobody could change without a deploy.
 */
class Theme
{
    public const FALLBACK = 'midnight';

    /** @return array<string, array> every theme that exists */
    public static function all(): array
    {
        return config('escalate.themes', []);
    }

    public static function exists(?string $key): bool
    {
        return $key !== null && array_key_exists($key, self::all());
    }

    /** The theme a guest sees, and the default for anybody without a choice. */
    public static function public(): string
    {
        $key = config('escalate.public_theme');

        return self::exists($key) ? $key : self::FALLBACK;
    }

    /**
     * The themes to offer in the picker.
     *
     * `$current` is always included, whatever the allowlist says. Hiding a
     * theme somebody is actively using would show them a picker where nothing
     * is selected and quietly change their app the next time they saved
     * anything — an administrator narrowing the list should affect what people
     * can move TO, never what they are already on.
     *
     * @return array<string, array>
     */
    public static function offered(?string $current = null): array
    {
        $allowed = array_filter((array) config('escalate.themes_offered', []));

        if ($allowed === []) {
            return self::all();
        }

        $keep = array_flip($allowed);

        if ($current !== null) {
            $keep[$current] = true;
        }

        $offered = array_intersect_key(self::all(), $keep);

        // Never leave somebody with nothing to choose from: a misconfigured
        // allowlist should degrade to the full list, not to an empty screen.
        return $offered === [] ? self::all() : $offered;
    }

    /** What this request should render as. */
    public static function forUser(?User $user): string
    {
        $chosen = $user?->profile?->theme;

        return self::exists($chosen) ? $chosen : self::public();
    }
}
