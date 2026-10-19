<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiEvent;
use App\Models\Invite;
use App\Models\User;
use App\Support\Ceiling;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * What is happening, and what it is costing.
 *
 * Reads ai_events and counts, never user content. That is the whole design
 * constraint on this screen: an administrator supporting someone must be able
 * to see that their generation failed without being able to read what they
 * wrote. Nothing here decrypts a desire, a reading or a gratitude entry, and
 * nothing here should ever be made to.
 */
class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        return view('admin.dashboard', [
            'people' => [
                'total'     => User::count(),
                'active'    => User::whereNotNull('last_login_at')->where('last_login_at', '>=', now()->subDays(7))->count(),
                'suspended' => User::whereNotNull('suspended_at')->count(),
                'comped'    => User::whereNotNull('plan_override')->count(),
            ],
            'invites' => [
                'open'    => Invite::whereNull('claimed_at')->count(),
                'claimed' => Invite::whereNotNull('claimed_at')->count(),
            ],
            'today'    => $this->spend(now()->subDay()),
            'month'    => $this->spend(now()->subDays(30)),
            'ceilings' => collect(['story', 'narration', 'rewind'])->mapWithKeys(fn ($kind) => [
                $kind => [
                    'used'  => Ceiling::used($kind),
                    'limit' => Ceiling::limit($kind),
                ],
            ])->all(),
            'failures' => AiEvent::where('ok', false)
                ->where('created_at', '>=', now()->subDay())
                ->selectRaw('error_code, kind, count(*) as total')
                ->groupBy('error_code', 'kind')
                ->orderByDesc('total')
                ->limit(8)
                ->get(),
        ]);
    }

    /**
     * Calls and cost since a moment.
     *
     * The cost column is reported as-is and labelled as an estimate on the
     * page, because it is: it is computed from a pricing table in the code, and
     * a pricing table drifts. The call counts are exact and are what the
     * ceiling is actually enforced on.
     */
    private function spend($since): array
    {
        $rows = AiEvent::where('created_at', '>=', $since)
            ->selectRaw('kind, count(*) as calls, sum(ok) as ok, sum(cost_microcents) as cost')
            ->groupBy('kind')
            ->get();

        return [
            'by_kind' => $rows,
            'calls'   => (int) $rows->sum('calls'),
            'failed'  => (int) ($rows->sum('calls') - $rows->sum('ok')),
        ];
    }
}
