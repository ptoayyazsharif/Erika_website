<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\ApplicationController as Applications;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\BetaMetrics;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * How the beta is actually going.
 *
 * The five things Erika's notes say to measure, per tester and in aggregate.
 * Counts only: see App\Support\BetaMetrics, and the rule stated on
 * DashboardController that this screen inherits.
 */
class BetaController extends Controller
{
    public function __invoke(Request $request): View
    {
        // The Founding 25 is the group under test, so it is the default view —
        // but a beta that hides everybody else hides its own control group.
        $cohort = $request->query('cohort', Applications::COHORT);
        $all = $cohort === 'all';

        $people = User::query()
            ->when(! $all, fn ($q) => $q->where('cohort', $cohort))
            ->whereNull('suspended_at')
            ->where('role', '!=', 'admin')
            ->orderBy('created_at')
            ->get();

        $rows = BetaMetrics::for($people);

        return view('admin.beta', [
            'rows'    => $rows->sortByDesc('days')->values(),
            'cohort'  => $all ? 'all' : $cohort,
            'measures' => [
                'Activation'  => ['key' => 'activated', 'blurb' => 'Wrote a first reading'],
                'Habit'       => ['key' => 'returned',  'blurb' => 'Came back on a later day'],
                'Connection'  => ['key' => 'connected', 'blurb' => 'Listened, kept, or looked back'],
                'Retention'   => ['key' => 'retained',  'blurb' => 'Here in the last 7 days'],
                'Completion'  => ['key' => 'completed', 'blurb' => BetaMetrics::DAYS_TO_COMPLETE.'+ days in their first '.BetaMetrics::TEST_LENGTH],
            ],
            'share' => fn (string $key) => BetaMetrics::share($rows, $key),
        ]);
    }
}
