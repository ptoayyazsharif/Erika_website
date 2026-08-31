<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\BetaMetrics;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Erika's five measures.
 *
 * Built around one tester with a known shape, because the failure these guard
 * against is not a crash — it is a number that looks plausible and is wrong,
 * which nobody catches until a decision has been made on it.
 */
class BetaMetricsTest extends TestCase
{
    use RefreshDatabase;

    private function tester(string $email, int $joinedDaysAgo, array $activeDayOffsets): User
    {
        $user = $this->makeUser($email);
        $user->forceFill(['created_at' => now()->subDays($joinedDaysAgo)->startOfDay()])->save();

        foreach ($activeDayOffsets as $offset) {
            DB::table('activity_days')->insert([
                'user_id' => $user->id,
                'day'     => now()->subDays($joinedDaysAgo)->startOfDay()->addDays($offset)->toDateString(),
            ]);
        }

        return $user->fresh();
    }

    /* ── the middleware that feeds it ────────────────────────────────────── */

    public function test_a_visit_records_one_day_however_many_pages_are_opened(): void
    {
        Cache::flush();
        $user = $this->makeUser('visitor@escalate.test');

        $this->actingAs($user)->get(route('today'))->assertOk();
        $this->actingAs($user)->get(route('desires.index'))->assertOk();
        $this->actingAs($user)->get(route('gratitude.index'))->assertOk();

        $this->assertSame(1, DB::table('activity_days')->where('user_id', $user->id)->count());
        $this->assertSame(
            now()->toDateString(),
            DB::table('activity_days')->where('user_id', $user->id)->value('day'),
        );
    }

    public function test_a_signed_out_visitor_records_nothing(): void
    {
        Cache::flush();

        $this->get(route('login'))->assertOk();

        $this->assertSame(0, DB::table('activity_days')->count());
    }

    /**
     * A metrics write must never cost somebody their page.
     *
     * Proved by taking the table away: the middleware should report and carry
     * on, not 500 the app.
     */
    public function test_the_page_still_loads_when_the_write_fails(): void
    {
        Cache::flush();
        $user = $this->makeUser('unlucky@escalate.test');

        DB::statement('DROP TABLE activity_days');

        $this->actingAs($user)->get(route('today'))->assertOk();
    }

    /* ── the measures ────────────────────────────────────────────────────── */

    /** Joined 10 days ago, here on 5 of the first 7, one reading, one narration. */
    public function test_a_known_tester_reads_correctly_on_every_measure(): void
    {
        $user = $this->tester('known@escalate.test', 10, [0, 1, 2, 4, 6, 9]);

        $story = $user->stories()->make();
        $story->forceFill(['state' => 'ready', 'body' => 'x'])->save();
        // Nothing on Narration is fillable, by design — forceFill, as the
        // application does.
        $narration = new \App\Models\Narration;
        $narration->forceFill([
            'story_id' => $story->id, 'user_id' => $user->id,
            'voice' => 'v', 'state' => 'ready',
        ])->save();

        $row = BetaMetrics::for(collect([$user]))->first();

        $this->assertTrue($row['activated'], 'One reading should count as activated.');
        $this->assertTrue($row['returned'], 'They were here on later days than they joined.');
        $this->assertTrue($row['connected'], 'They listened to one.');
        $this->assertTrue($row['retained'], 'They were here yesterday-ish.');
        $this->assertTrue($row['completed'], '5 days inside the first 7 clears the bar of 4.');
        $this->assertSame(5, $row['days_in_first_week']);
        $this->assertSame(6, $row['days']);
    }

    /** Signed up, opened it once, never wrote anything. */
    public function test_somebody_who_only_looked_counts_as_nothing_but_present(): void
    {
        $user = $this->tester('lurker@escalate.test', 10, [0]);

        $row = BetaMetrics::for(collect([$user]))->first();

        $this->assertFalse($row['activated']);
        $this->assertFalse($row['returned']);
        $this->assertFalse($row['connected']);
        $this->assertFalse($row['retained'], 'Their only day was ten days ago.');
        $this->assertFalse($row['completed']);
    }

    /**
     * Completion is counted inside their own first week, not the last seven days.
     *
     * Somebody who joined a month ago and has been here every day since must
     * not be credited with a test they took at a different time — and somebody
     * who was busy in week one and prolific in week three has not completed it.
     */
    public function test_completion_is_measured_in_their_first_week_not_this_one(): void
    {
        // Nothing in the first week; five days recently.
        $late = $this->tester('late@escalate.test', 30, [20, 21, 22, 23, 24]);

        $row = BetaMetrics::for(collect([$late]))->first();

        $this->assertFalse($row['completed'], 'Those days were in week four, not week one.');
        $this->assertSame(0, $row['days_in_first_week']);
        $this->assertTrue($row['retained'], 'They are, however, still here.');
    }

    /** Keeping a card is connection; being handed one is not. */
    public function test_a_kept_card_counts_as_connection_and_an_unkept_one_does_not(): void
    {
        $user = $this->tester('cards@escalate.test', 8, [0]);

        $set = new \App\Models\AffirmationSet;
        $set->forceFill(['user_id' => $user->id, 'for_date' => today(), 'state' => 'ready'])->save();

        $card = new \App\Models\Affirmation;
        $card->forceFill([
            'affirmation_set_id' => $set->id, 'user_id' => $user->id,
            'body' => 'A card.', 'position' => 0, 'favourite' => false,
        ])->save();

        $this->assertFalse(BetaMetrics::for(collect([$user->fresh()]))->first()['connected']);

        $card->forceFill(['favourite' => true])->save();

        $this->assertTrue(BetaMetrics::for(collect([$user->fresh()]))->first()['connected']);
    }

    public function test_the_share_across_a_group_is_a_percentage(): void
    {
        $rows = collect([
            ['activated' => true], ['activated' => true],
            ['activated' => false], ['activated' => false],
        ]);

        $this->assertSame(50, BetaMetrics::share($rows, 'activated'));
        $this->assertSame(0, BetaMetrics::share(collect(), 'activated'));
    }

    /* ── the screen ──────────────────────────────────────────────────────── */

    public function test_the_beta_screen_is_admin_only(): void
    {
        $this->actingAs($this->makeUser('nosy@escalate.test'))
            ->get(route('admin.beta'))
            ->assertNotFound();
    }

    public function test_an_admin_sees_the_measures(): void
    {
        $tester = $this->tester('shown@escalate.test', 9, [0, 1, 2, 3]);
        $tester->forceFill(['cohort' => 'Founding 25'])->save();

        $admin = $this->makeUser('betaadmin@escalate.test', 'Admin');
        $admin->forceFill(['role' => 'admin'])->save();

        $this->actingAs($admin->fresh())
            ->withSession(['admin.verified' => true, 'admin.verified_at' => now()->timestamp])
            ->get(route('admin.beta'))
            ->assertOk()
            ->assertSee('Activation')
            ->assertSee('Completion')
            ->assertSee($tester->name);
    }
}
