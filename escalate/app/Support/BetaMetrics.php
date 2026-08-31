<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Erika's five measures, answered from rows the app already keeps.
 *
 * The rule the admin area runs on holds here without exception: this counts,
 * it never reads. Nothing below decrypts a desire, a reading, a gratitude entry
 * or a card. An administrator can see that somebody has written nothing without
 * being able to see what they wrote — see Admin\DashboardController.
 *
 * "Completion" is Erika's own bar, not one invented here: her application form
 * asks whether somebody would realistically use Escalate at least four times
 * during a seven-day test, so completing the test is four active days inside
 * the first seven. Measuring it against a different number would let a tester
 * pass a test they were never asked to take.
 */
class BetaMetrics
{
    /** Erika's bar, from question four of the application form. */
    public const DAYS_TO_COMPLETE = 4;

    public const TEST_LENGTH = 7;

    /**
     * One row per person, with every measure already decided.
     *
     * Built from four aggregate queries rather than a query per person: 25
     * testers today, but a screen that degrades at 200 is a screen somebody
     * stops opening.
     *
     * @param  Collection<int, User>  $people
     */
    public static function for(Collection $people): Collection
    {
        if ($people->isEmpty()) {
            return collect();
        }

        $ids = $people->pluck('id')->all();

        $days = self::activeDays($ids);
        $made = self::whatTheyMade($ids);

        return $people->map(function (User $user) use ($days, $made) {
            $theirDays = $days[$user->id] ?? collect();
            $counts = $made[$user->id] ?? [];

            $joined = $user->created_at?->startOfDay();

            // Days inside their first week. Erika's test is seven days long
            // from the day they arrived, not seven days from today.
            $inFirstWeek = $joined
                ? $theirDays->filter(fn ($day) => $day->gte($joined)
                    && $day->lt($joined->copy()->addDays(self::TEST_LENGTH)))
                : collect();

            return [
                'user'    => $user,
                'joined'  => $joined,
                'days'    => $theirDays->count(),

                // Activation: they got as far as the thing the app is for.
                'activated' => ($counts['stories'] ?? 0) > 0,

                /*
                 * Habit: they came back on a later day than the one they
                 * arrived on. Deliberately "a later day" rather than "the very
                 * next day" — somebody who joins on a Friday evening and
                 * returns on Sunday has formed the habit the measure is after,
                 * and scoring them as a failure would flatter nobody.
                 */
                'returned' => $joined
                    ? $theirDays->contains(fn ($day) => $day->gt($joined))
                    : false,

                /*
                 * Emotional connection: they did something with a reading
                 * rather than only generating one. Listening, keeping a card,
                 * writing gratitude and looking back are the four ways this app
                 * offers of staying with a thing instead of producing another.
                 */
                'connected' => ($counts['narrations'] ?? 0) > 0
                    || ($counts['gratitude'] ?? 0) > 0
                    || ($counts['rewinds'] ?? 0) > 0
                    || ($counts['kept_cards'] ?? 0) > 0,

                // Retention: here in the last week, whenever they joined.
                'retained' => $theirDays->contains(fn ($day) => $day->gte(now()->startOfDay()->subDays(7))),

                // Completion: Erika's four days inside their own seven.
                'completed' => $inFirstWeek->count() >= self::DAYS_TO_COMPLETE,
                'days_in_first_week' => $inFirstWeek->count(),

                'counts' => [
                    'stories'    => $counts['stories'] ?? 0,
                    'narrations' => $counts['narrations'] ?? 0,
                    'gratitude'  => $counts['gratitude'] ?? 0,
                    'rewinds'    => $counts['rewinds'] ?? 0,
                    'cards'      => $counts['cards'] ?? 0,
                ],
            ];
        });
    }

    /** The share of people for whom a measure is true, as a percentage. */
    public static function share(Collection $rows, string $key): int
    {
        if ($rows->isEmpty()) {
            return 0;
        }

        return (int) round($rows->where($key, true)->count() / $rows->count() * 100);
    }

    /* ── the queries ─────────────────────────────────────────────────────── */

    /** @return array<int, Collection> user id => the dates they were here */
    private static function activeDays(array $ids): array
    {
        return DB::table('activity_days')
            ->whereIn('user_id', $ids)
            ->orderBy('day')
            ->get(['user_id', 'day'])
            ->groupBy('user_id')
            ->map(fn ($rows) => $rows->map(fn ($row) => \Illuminate\Support\Carbon::parse($row->day)->startOfDay()))
            ->all();
    }

    /**
     * How much of each thing they have, counted per table.
     *
     * One grouped query per table rather than a subquery per person per table.
     *
     * @return array<int, array<string, int>>
     */
    private static function whatTheyMade(array $ids): array
    {
        $counts = [];

        $sources = [
            'stories'    => ['stories', null],
            'narrations' => ['narrations', null],
            'gratitude'  => ['gratitude_entries', null],
            'rewinds'    => ['rewinds', null],
            'cards'      => ['affirmations', null],
            // Keeping a card is a deliberate act, unlike being given one.
            'kept_cards' => ['affirmations', ['favourite', '=', true]],
        ];

        foreach ($sources as $label => [$table, $where]) {
            $query = DB::table($table)->whereIn('user_id', $ids);

            if ($where) {
                $query->where(...$where);
            }

            foreach ($query->selectRaw('user_id, count(*) as total')->groupBy('user_id')->get() as $row) {
                $counts[$row->user_id][$label] = (int) $row->total;
            }
        }

        return $counts;
    }
}
