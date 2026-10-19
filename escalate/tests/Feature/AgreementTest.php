<?php

namespace Tests\Feature;

use App\Models\Rewind;
use App\Support\Quota;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Places where two halves of the app had drifted apart.
 *
 * None of these is a crash. Each is a screen quietly promising something the
 * code behind it does not do — a box that accepts more than the server will
 * take, an export that leaves out a column it used to read, a limit that
 * cannot see the work already queued against it. They are the failures a
 * per-screen test never finds, because each screen is correct on its own.
 */
class AgreementTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A textarea must not accept more than its validation rule allows.
     *
     * Every Rewind box said maxlength="4000" while the server capped `people`
     * and `gratitude` at 2000 — the two questions most likely to run long,
     * being the list of everyone involved and everything to be thankful for.
     * You typed freely, pressed Save, and were told afterwards.
     */
    public function test_no_rewind_box_accepts_more_than_the_server_will_store(): void
    {
        $limits = [
            'what_happened' => 4000, 'turning_points' => 4000, 'redirections' => 4000,
            'people' => 2000, 'lessons' => 4000, 'gratitude' => 2000,
        ];

        $markup = file_get_contents(resource_path('views/rewinds/edit.blade.php'));

        // The form is rendered from a table of questions; assert the table.
        foreach ($limits as $field => $max) {
            $this->assertMatchesRegularExpression(
                "/'{$field}',[^\]]*, {$max}\]/",
                $markup,
                "The Rewind form's limit for '{$field}' no longer matches the "
                    ."max:{$max} rule in RewindController::store.",
            );
        }

        $this->assertStringContainsString(
            'maxlength="{{ $max }}"',
            $markup,
            'The Rewind textareas stopped using the per-question limit.',
        );
    }

    /**
     * The export is what a subject-access request is answered with, so it may
     * not quietly skip a column.
     *
     * It read `note` — the retired single-detail column — long after details
     * became a list, so every person in My Circle exported with nothing
     * recorded about them.
     */
    public function test_the_export_carries_every_detail_recorded_about_a_person(): void
    {
        $user = $this->makeUser('export-circle@escalate.test');

        $user->circle()->create([
            'name'         => 'Marta',
            'relationship' => 'my sister',
            'notes'        => ['drove the van', 'hates being thanked'],
        ]);

        $export = app(\App\Services\AccountEraser::class)->export($user->fresh());

        $this->assertSame('Marta', $export['circle'][0]['name']);
        $this->assertSame(
            ['drove the van', 'hates being thanked'],
            $export['circle'][0]['details'],
        );
    }

    /**
     * A queued Rewind counts against the daily limit before it lands.
     *
     * Without this the limit is check-then-spend across the queue boundary:
     * rewinds.generate is throttled at twelve an hour against a limit of three
     * a day, and the job re-checks the same counter — which could not see its
     * own queued row. Twelve presses on a slow queue passed both checks twelve
     * times.
     */
    public function test_a_queued_rewind_is_counted_before_it_lands(): void
    {
        $user = $this->makeUser('quota-rewind@escalate.test');
        $limit = Quota::limit($user, 'rewind');

        $this->assertSame(0, Quota::used($user, 'rewind'));

        for ($i = 0; $i < $limit; $i++) {
            $desire = $user->desires()->create(['title' => "Desire {$i}", 'status' => 'desired']);
            $desire->moveTo('manifested');

            $rewind = new Rewind;
            $rewind->forceFill([
                'user_id' => $user->id, 'desire_id' => $desire->id, 'state' => 'queued',
            ])->save();
        }

        $this->assertSame($limit, Quota::used($user->fresh(), 'rewind'));
        $this->assertFalse(Quota::allows($user->fresh(), 'rewind'));
    }

    /**
     * The reading list must still be navigable past the first page.
     *
     * It paginated at twenty and then called $stories->links(), which renders
     * Laravel's default view — styled entirely in Tailwind utility classes.
     * There is no Tailwind here and no build step; the CSS is hand-authored
     * custom properties. So the controls resolved to nothing, and the first
     * person to write a twenty-first reading got a row of bare links under
     * their cards.
     */
    public function test_the_reading_list_paginates_in_this_apps_own_markup(): void
    {
        $user = $this->makeUser('pager@escalate.test');

        foreach (range(1, 21) as $i) {
            $this->makeReadyStory($user, "Reading number {$i}.");
        }

        $response = $this->actingAs($user)->get(route('stories.index'))->assertOk();

        $response->assertSee('Page 1 of 2')
            ->assertSee('Older')
            ->assertSee(route('stories.index', ['page' => 2]), false);

        // The giveaway that the default view is back: Tailwind class names.
        $response->assertDontSee('relative inline-flex items-center', false);
    }

    /**
     * Searching inside a tag must stay inside it.
     *
     * The search box posted `q` alone, so submitting it dropped the tag from
     * the query string — narrowing a filtered view widened it back to
     * everything.
     */
    public function test_searching_within_a_tag_keeps_the_tag(): void
    {
        $user = $this->makeUser('tag-search@escalate.test');

        $user->gratitudeEntries()->create([
            'body' => 'The coffee was good.', 'tags' => ['small things'], 'for_date' => today(),
        ]);

        $this->actingAs($user)
            ->get(route('gratitude.index', ['tag' => 'small things']))
            ->assertOk()
            ->assertSee('name="tag" value="small things"', false);
    }
}
