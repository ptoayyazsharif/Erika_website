<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FeedbackResponse;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * What the testers said.
 *
 * The one number on this screen is the share who would be very disappointed to
 * lose it. Forty per cent is the line the original survey draws between a
 * product people would miss and one they would not, and it is shown against
 * that line rather than on its own, because a percentage with nothing to
 * compare it to invites whatever reading the reader already wanted.
 */
class FeedbackController extends Controller
{
    /** The threshold from the original Sean Ellis survey. */
    public const BAR = 40;

    public function __invoke(Request $request): View
    {
        $responses = FeedbackResponse::with('user')->latest()->get();

        $veryDisappointed = $responses->where('disappointment', FeedbackResponse::VERY)->count();

        // Everybody who could have answered, so a low response rate is visible
        // rather than flattering: five enthusiastic replies out of forty
        // testers is a different result from five out of six.
        $eligible = User::whereNull('suspended_at')
            ->where('role', '!=', 'admin')
            ->where('created_at', '<=', now()->subDays(\App\Http\Controllers\FeedbackController::AFTER_DAYS))
            ->count();

        return view('admin.feedback', [
            'responses' => $responses,
            'score'     => $responses->isEmpty() ? null : (int) round($veryDisappointed / $responses->count() * 100),
            'bar'       => self::BAR,
            'answered'  => $responses->count(),
            'eligible'  => $eligible,
            'breakdown' => collect(FeedbackResponse::FEELINGS)
                ->map(fn ($label, $key) => [
                    'label' => $label,
                    'count' => $responses->where('disappointment', $key)->count(),
                ])->values(),
        ]);
    }
}
