<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The screen every session begins on.
 *
 * It used to be the shared "not written yet" placeholder, which meant the
 * first thing anyone saw after signing in was an empty page whose only button
 * said "Back to Today" — pointing at the page they were already standing on.
 *
 * The cost was not the wrong label. Naming a desire and asking for a reading
 * is the entire product, and the only route to it was Desires → Name a desire
 * → open it → Write my reading: four steps, none of them visible from the
 * screen you land on. People could not find the thing the app is for.
 *
 * So Today's job is narrow and it is not a dashboard: say where you are, and
 * put the next action within one tap. What it shows depends on what you have,
 * because "name your first desire" and "your reading is ready" are different
 * moments and deserve different screens.
 */
class TodayController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        // Eager-loaded: this view touches each desire's stories to decide what
        // to offer, and without this that is a query per card.
        $desires = $user->desires()->with('stories')->take(3)->get();

        $latestStory = $user->stories()->with('desire')->where('state', 'ready')->first();

        // The desire most worth acting on: the newest one still without a
        // reading. Nothing to nudge if they all have one.
        $awaiting = $desires->first(fn ($desire) => $desire->stories->isEmpty());

        return view('today', [
            'user'        => $user,
            'desires'     => $desires,
            'latestStory' => $latestStory,
            'awaiting'    => $awaiting,
            'greeting'    => $this->greeting(),
            'totals'      => [
                'desires'   => $user->desires()->count(),
                'readings'  => $user->stories()->where('state', 'ready')->count(),
                'gratitude' => $user->gratitudeEntries()->count(),
            ],
        ]);
    }

    /**
     * Server-side, from the app timezone rather than the browser's.
     *
     * Doing it in JS would be more accurate to where the person actually is,
     * but it would also mean the greeting flickering in after paint on every
     * load. A quiet screen should not flinch when it opens.
     */
    private function greeting(): string
    {
        $hour = (int) now()->format('G');

        return match (true) {
            $hour < 5  => 'Still up',
            $hour < 12 => 'Good morning',
            $hour < 18 => 'Good afternoon',
            default    => 'Good evening',
        };
    }
}
