<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\AccessRevoked;
use App\Models\Application;
use App\Support\Mailer;
use App\Support\TesterStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Who was let in, and where they got stuck.
 *
 * Counts only, like every other admin screen. This shows that somebody has
 * written nothing; it never shows what they wrote. The one thing it does read
 * is the applicant's name and address, which are theirs to us already — an
 * application is addressed to the reader, which is why it is one of the two
 * stated exceptions in CLAUDE.md.
 */
class TesterController extends Controller
{
    public function index(): View
    {
        $applications = Application::with(['invite.claimant'])
            ->where('status', Application::SELECTED)
            ->get();

        $userIds = $applications
            ->map(fn (Application $a) => $a->invite?->claimant?->id)
            ->filter()
            ->values()
            ->all();

        // Two grouped queries rather than two per person. Admin\BetaController
        // takes the same approach for the same reason.
        $lastActive = $userIds
            ? DB::table('activity_days')->whereIn('user_id', $userIds)
                ->groupBy('user_id')
                ->pluck(DB::raw('MAX(day)'), 'user_id')->all()
            : [];

        $storyCounts = $userIds
            ? DB::table('stories')->whereIn('user_id', $userIds)
                ->groupBy('user_id')
                ->pluck(DB::raw('COUNT(*)'), 'user_id')->all()
            : [];

        $rows = $applications->map(function (Application $a) use ($lastActive, $storyCounts) {
            $user = $a->invite?->claimant;
            $seen = $user && ! empty($lastActive[$user->id])
                ? Carbon::parse($lastActive[$user->id])
                : null;

            $status = TesterStatus::of($a, $seen, (bool) ($storyCounts[$user?->id] ?? 0));

            return [
                'application' => $a,
                'user'        => $user,
                'status'      => $status,
                'label'       => TesterStatus::label($status),
                'revocable'   => TesterStatus::isRevocable($status),
                'attention'   => TesterStatus::needsAttention($status),
                'stalled'     => (int) ($seen ?? $a->decided_at ?? $a->updated_at)->diffInDays(now()),
                'lastActive'  => $seen,
            ];
        });

        return view('admin.testers', [
            // The ones that want a decision first, then the longest-stalled.
            'rows' => $rows->sortBy([
                fn ($a, $b) => ($b['attention'] <=> $a['attention']),
                fn ($a, $b) => ($b['stalled'] <=> $a['stalled']),
            ])->values(),
        ]);
    }

    /**
     * Take back a seat nobody claimed.
     *
     * The invite is deleted rather than flagged, which frees the address to be
     * invited again later without a second row claiming to be the same seat.
     * The application returns to the waitlist, which is where somebody who has
     * not been let in belongs — not declined, because they were never turned
     * down on their answers.
     */
    public function revoke(Request $request, Application $application): RedirectResponse
    {
        $user = $application->invite?->claimant;
        $status = TesterStatus::of($application, null, false);

        if (! TesterStatus::isRevocable($status)) {
            return back()->withErrors(['tester' => $user
                ? 'They have already signed up. Suspend the account from their user page instead.'
                : 'There is nothing to revoke on that application.']);
        }

        DB::transaction(function () use ($application) {
            $application->invite?->delete();

            $application->forceFill([
                'status'     => Application::WAITLISTED,
                'decided_at' => now(),
                'invite_id'  => null,
            ])->save();
        });

        if (! config('escalate.beta.notify_revoked')) {
            return back()->with('status', 'Seat taken back. Nothing was emailed to them.');
        }

        $sent = Mailer::send($application->email, new AccessRevoked($application));

        return back()->with('status', $sent
            ? "Seat taken back, and {$application->email} has been told."
            : 'Seat taken back — but the email did not send. Check Settings → Mail.');
    }
}
