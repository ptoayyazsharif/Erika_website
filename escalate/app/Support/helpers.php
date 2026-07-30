<?php

if (! function_exists('asset_v')) {
    /**
     * Asset URL with a content hash appended.
     *
     * There is no build step in this app — CSS and JS are hand-authored and
     * served straight from public/ — so nothing else busts the cache when a
     * file changes. The hash is memoised per request and falls back to the
     * plain path if the file is missing.
     */
    function asset_v(string $path): string
    {
        static $cache = [];

        $path = ltrim($path, '/');

        if (! array_key_exists($path, $cache)) {
            $full = public_path($path);
            $cache[$path] = is_file($full) ? substr(md5_file($full), 0, 10) : null;
        }

        return $cache[$path] === null
            ? asset($path)
            : asset($path).'?v='.$cache[$path];
    }
}

if (! function_exists('status_label')) {
    /** Human label for a Manifestation Archive status key. */
    function status_label(?string $key): string
    {
        return config("escalate.statuses.{$key}.label", 'Desired');
    }
}

if (! function_exists('active_theme')) {
    /**
     * The theme key for the current request.
     *
     * Resolved server-side so it can be rendered straight into
     * <html data-theme>. That is not a nicety: the CSP forbids inline scripts,
     * so the usual trick of a tiny <head> script reading localStorage is not
     * available, and without this every light-theme user would get a flash of
     * dark on first paint.
     */
    function active_theme(): string
    {
        $key = auth()->user()?->profile?->theme;

        return array_key_exists((string) $key, config('escalate.themes'))
            ? $key
            : 'midnight';
    }
}

if (! function_exists('theme_meta')) {
    /** Config for a theme key, falling back to the default. */
    function theme_meta(?string $key = null): array
    {
        $key ??= active_theme();

        return config("escalate.themes.{$key}") ?? config('escalate.themes.midnight');
    }
}
