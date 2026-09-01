<?php

namespace App\Support;

/**
 * The words on the public pages, in one place.
 *
 * The application questions were a literal array inside apply.blade.php, which
 * meant every rewording was a code change and a deploy — the wrong shape for
 * marketing copy during a launch week, and the reason Erika asked for them to
 * be editable.
 *
 * Keyed by the column the answer lands in. That is what stops a reworded
 * question from drifting away from the label above the answer it produced:
 * the form and Admin → Applications read the same key rather than each
 * carrying their own copy of the sentence.
 */
class Copy
{
    /** The five, in order, as [column, question, helper|null]. */
    public const FIELDS = ['changing', 'practice', 'tried_apps', 'will_use', 'will_feedback'];

    /** @return array<int, array{0: string, 1: string, 2: ?string}> */
    public static function questions(): array
    {
        return array_map(fn (string $field) => [
            $field,
            self::question($field),
            self::help($field),
        ], self::FIELDS);
    }

    public static function question(string $field): string
    {
        return (string) config("escalate.copy.q_{$field}");
    }

    /** Null rather than an empty string, so the view can skip the element. */
    public static function help(string $field): ?string
    {
        $help = trim((string) config("escalate.copy.q_{$field}_help"));

        return $help === '' ? null : $help;
    }

    /**
     * The questions as labels, for reading answers back.
     *
     * @return array<string, string> column => question
     */
    public static function labels(): array
    {
        return collect(self::FIELDS)
            ->mapWithKeys(fn (string $field) => [$field => self::question($field)])
            ->all();
    }
}
