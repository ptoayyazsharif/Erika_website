<?php

namespace App\Http\Controllers;

use App\Jobs\NarrateStory;
use App\Jobs\WriteStory;
use App\Models\Desire;
use App\Models\Narration;
use App\Models\Story;
use App\Support\Quota;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Stories: asking for one, waiting for it, reading it, hearing it, editing it.
 *
 * The waiting is the interesting part. Generation takes 15–25 seconds and
 * narration another 5–10, which is far too long to hold a request open on
 * shared hosting. So both are queued and the reveal screen polls `state`.
 *
 * Everything the client is told about a story comes out of this controller, and
 * it is deliberately narrow: no model name, no voice id, no provider hostname.
 * That is an attack-surface decision, not a secrecy one — those values are
 * credentials-adjacent and have no business in a page. Who processes user
 * content is disclosed plainly to users at /privacy, where it belongs.
 */
class StoryController extends Controller
{
    public function index(Request $request): View
    {
        $stories = $request->user()->stories()->with('desire')->paginate(20);

        return view('stories.index', ['stories' => $stories]);
    }

    /** Ask for a reading of a desire. */
    public function store(Request $request, Desire $desire): RedirectResponse
    {
        abort_unless($desire->user_id === $request->user()->id, 404);

        if (! Quota::allows($request->user(), 'story')) {
            return back()->with('status', Quota::message('story'));
        }

        // forceFill because desire_id and state are server-derived: the desire
        // has already been proved to belong to this user, and nothing a client
        // posts should be able to set either one.
        $story = $request->user()->stories()->make();
        $story->forceFill(['desire_id' => $desire->id, 'state' => 'queued'])->save();

        WriteStory::dispatch($story);

        return redirect()->route('stories.show', $story);
    }

    public function show(Request $request, Story $story): View
    {
        $this->mine($request, $story);

        $voice = $request->user()->world()->voice;

        return view('stories.show', [
            'story'     => $story->load('desire'),
            'narration' => $story->narrationFor($voice),
            'voice'     => $voice,
            'remaining' => [
                'story'     => Quota::remaining($request->user(), 'story'),
                'narration' => Quota::remaining($request->user(), 'narration'),
            ],
        ]);
    }

    /**
     * What the reveal screen polls.
     *
     * Returns only what the screen needs to decide what to draw. Note what is
     * absent: no model name, no provider, no voice id, no file path. The audio
     * is referenced by a route, not a location.
     */
    public function state(Request $request, Story $story): JsonResponse
    {
        $this->mine($request, $story);

        $voice = $request->user()->world()->voice;
        $narration = $story->narrationFor($voice);

        return response()->json([
            'state'      => $story->state,
            'words'      => $story->isReady() ? $story->words() : 0,
            'paragraphs' => $story->isReady() ? $story->paragraphs() : [],
            'reason'     => $story->state === 'failed' ? $story->failure_reason : null,
            'audio'      => [
                'state'  => $narration?->state,
                'url'    => $narration?->isReady() ? route('media.narration', $narration) : null,
                'reason' => $narration?->state === 'failed' ? $narration->failure_reason : null,
                'seconds' => $narration?->seconds() ?: $story->estimatedSeconds(),
            ],
        ]);
    }

    /** Render the narration for this story in the user's chosen voice. */
    public function narrate(Request $request, Story $story): RedirectResponse
    {
        $this->mine($request, $story);
        abort_unless($story->isReady(), 404);

        /*
         * The voice can be chosen here, not only in My World.
         *
         * It used to come solely from the profile, which meant changing it was
         * a trip to a settings screen most people never opened — and the one
         * moment you actually have an opinion about the voice is the moment
         * you are about to hear it.
         *
         * Validated against the configured keys rather than trusted, because
         * this value ends up selecting which voice id the server bills against.
         */
        $world = $request->user()->world();

        $validated = $request->validate([
            'voice' => ['nullable', 'string', 'in:'.implode(',', array_keys(config('escalate.voices')))],
        ]);

        $voice = $validated['voice'] ?? $world->voice;

        // Remember it, so the next reading defaults to what they last chose
        // and nobody has to re-pick every time.
        if ($voice !== $world->voice) {
            $world->forceFill(['voice' => $voice])->save();
        }

        $existing = $story->narrationFor($voice);

        // Already rendered, or already on its way. Asking twice must not bill
        // twice, and the button is reachable twice on a slow connection.
        if ($existing && ($existing->isReady() || $existing->isPending())) {
            return back();
        }

        if (! Quota::allows($request->user(), 'narration')) {
            return back()->with('status', Quota::message('narration'));
        }

        NarrateStory::dispatch(Narration::queueFor($story, $voice));

        return back();
    }

    /** Write it again, from the same desire. Counts against the daily quota. */
    public function regenerate(Request $request, Story $story): RedirectResponse
    {
        $this->mine($request, $story);

        if ($story->regenerations >= config('escalate.quotas.regenerations_per_story')) {
            return back()->with('status', 'This reading has been rewritten as many times as it can be. Start a new one if it still is not right.');
        }

        if (! Quota::allows($request->user(), 'story')) {
            return back()->with('status', Quota::message('story'));
        }

        $story->forceFill([
            'state' => 'queued',
            'regenerations' => $story->regenerations + 1,
            'failure_reason' => null,
            // A rewrite abandons the old text, so an edit of the old text would
            // be misleading to keep.
            'edited_body' => null,
        ])->save();

        // The narration belongs to text that no longer exists.
        $story->narrations()->get()->each->delete();

        WriteStory::dispatch($story);

        return redirect()->route('stories.show', $story);
    }

    public function edit(Request $request, Story $story): View
    {
        $this->mine($request, $story);
        abort_unless($story->isReady(), 404);

        return view('stories.edit', ['story' => $story]);
    }

    public function update(Request $request, Story $story): RedirectResponse
    {
        $this->mine($request, $story);

        $data = $request->validate([
            'edited_body' => ['required', 'string', 'min:80', 'max:12000'],
        ]);

        $story->forceFill([
            'edited_body' => trim($data['edited_body']),
            'word_count'  => str_word_count($data['edited_body']),
        ])->save();

        // The audio narrates text that has changed. Drop it rather than let
        // someone hear a version they no longer have on screen.
        $story->narrations()->get()->each->delete();

        return redirect()->route('stories.show', $story)
            ->with('status', 'Saved. The narration was cleared — it read the old words.');
    }

    public function favourite(Request $request, Story $story): RedirectResponse
    {
        $this->mine($request, $story);

        $story->update(['favourite' => ! $story->favourite]);

        return back();
    }

    public function destroy(Request $request, Story $story): RedirectResponse
    {
        $this->mine($request, $story);

        // Delete the narrations through Eloquent first. The database cascade
        // would remove the rows but never fire Narration::booted(), so the mp3
        // — a recording of this story read aloud — would stay on disk with
        // nothing left pointing at it. regenerate() and update() already did
        // this; destroy() was the one path that leaked, and it is the one
        // people use when they actually want something gone.
        $story->narrations()->get()->each->delete();

        $story->delete();

        return redirect()->route('stories.index')->with('status', 'Deleted.');
    }

    /** Records a play. Used by My Journey, and by nothing that spends money. */
    public function played(Request $request, Story $story): JsonResponse
    {
        $this->mine($request, $story);

        $story->forceFill([
            'play_count' => $story->play_count + 1,
            'last_played_at' => now(),
        ])->save();

        return response()->json(['ok' => true]);
    }

    private function mine(Request $request, Story $story): void
    {
        abort_unless($story->user_id === $request->user()->id, 404);
    }
}
