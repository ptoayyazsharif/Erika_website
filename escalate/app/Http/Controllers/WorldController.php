<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * My World — the profile that every generated word is built from.
 *
 * Kept as one long form rather than a multi-step wizard on purpose: the brief
 * asks for reflection over productivity, and a person filling this in is
 * thinking, not completing a task. A wizard would hurry them.
 */
class WorldController extends Controller
{
    public function edit(Request $request): View
    {
        $user = $request->user();

        return view('world.edit', [
            'profile' => $user->world(),
            'circle'  => $user->circle,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'preferred_name' => ['nullable', 'string', 'max:60'],
            'city'           => ['nullable', 'string', 'max:80'],
            'life_context'   => ['nullable', 'string', 'max:1200'],
            'anchor'         => ['nullable', 'string', 'max:200'],
            'values'         => ['nullable', 'array', 'max:6'],
            'values.*'       => ['string', 'max:40'],

            // Preference keys are validated against config rather than a
            // hand-written list, so adding a voice or a tone in one place
            // cannot leave a stale whitelist behind here.
            'voice'          => ['required', 'string', 'in:'.implode(',', array_keys(config('escalate.voices')))],
            'theme'          => ['required', 'string', 'in:'.implode(',', array_keys(config('escalate.themes')))],
            'faith_language' => ['required', 'string', 'in:'.implode(',', array_keys(config('escalate.faith_languages')))],
            'default_length' => ['required', 'string', 'in:'.implode(',', array_keys(config('escalate.lengths')))],
            'story_style'    => ['required', 'string', 'in:cinematic,letter,meditative,documentary'],
            'perspective'    => ['required', 'string', 'in:first,second'],
            'tone'           => ['required', 'string', 'in:grounded,tender,assured,reverent,playful'],

            // My Circle arrives as parallel arrays from the repeating field.
            'circle'             => ['nullable', 'array', 'max:12'],
            'circle.*.name'      => ['nullable', 'string', 'max:60'],
            'circle.*.relationship' => ['nullable', 'string', 'max:60'],
            'circle.*.note'      => ['nullable', 'string', 'max:160'],
        ]);

        $profile = $user->world();
        $profile->fill([
            ...collect($data)->except('circle')->all(),
            'values'    => collect($data['values'] ?? [])->filter()->values()->all(),
            'onboarded' => true,
        ])->save();

        $this->syncCircle($user, $data['circle'] ?? []);

        return redirect()
            ->route($request->boolean('then_desire') ? 'desires.create' : 'world.edit')
            ->with('status', 'Your world is saved.');
    }

    /**
     * Set the theme on its own.
     *
     * Separate from the main form so the topbar's quick switch does not have to
     * post the whole of My World. Returns JSON so the switch can apply
     * instantly without a reload, and is a POST rather than a GET because it
     * changes stored state.
     */
    public function theme(Request $request): \Illuminate\Http\JsonResponse
    {
        $data = $request->validate([
            'theme' => ['required', 'string', 'in:'.implode(',', array_keys(config('escalate.themes')))],
        ]);

        $request->user()->world()->update(['theme' => $data['theme']]);

        $meta = config("escalate.themes.{$data['theme']}");

        return response()->json([
            'theme'       => $data['theme'],
            'scheme'      => $meta['scheme'],
            'chrome'      => $meta['chrome'],
            'counterpart' => $meta['counterpart'],
            'label'       => $meta['label'],
        ]);
    }

    /**
     * Replace the circle wholesale.
     *
     * Delete-then-insert rather than diffing: the form is a fixed set of rows,
     * a person's identity here is just their name, and there is nothing else in
     * the app referencing a circle row by id. Diffing would add a foreign-key
     * problem in exchange for nothing.
     */
    private function syncCircle($user, array $rows): void
    {
        $people = collect($rows)
            ->filter(fn ($row) => filled($row['name'] ?? null))
            ->values();

        $user->circle()->delete();

        foreach ($people as $index => $row) {
            $user->circle()->create([
                'name'         => trim($row['name']),
                'relationship' => trim($row['relationship'] ?? '') ?: null,
                'note'         => trim($row['note'] ?? '') ?: null,
                'position'     => $index,
            ]);
        }
    }
}
