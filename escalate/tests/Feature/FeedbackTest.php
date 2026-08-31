<?php

namespace Tests\Feature;

use App\Http\Controllers\FeedbackController;
use App\Models\FeedbackResponse;
use App\Models\User;
use App\Services\AccountEraser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** The day-seven survey, and the one number it produces. */
class FeedbackTest extends TestCase
{
    use RefreshDatabase;

    private function tester(string $email, int $daysAgo): User
    {
        $user = $this->makeUser($email);
        $user->forceFill(['created_at' => now()->subDays($daysAgo)])->save();

        return $user->fresh();
    }

    private function answers(array $overrides = []): array
    {
        return array_merge([
            'disappointment' => FeedbackResponse::VERY,
            'who_for' => 'Someone rebuilding after a hard year.',
            'benefit' => 'Hearing it in my own voice.',
            'improve' => 'Let me reorder the cards.',
        ], $overrides);
    }

    /* ── when it is offered ──────────────────────────────────────────────── */

    public function test_it_is_not_offered_in_the_first_week(): void
    {
        $this->actingAs($this->tester('new@escalate.test', 3))
            ->get(route('today'))
            ->assertOk()
            ->assertDontSee('Would you tell us how it is going?');
    }

    public function test_it_is_offered_once_the_week_is_up(): void
    {
        $this->actingAs($this->tester('week@escalate.test', 8))
            ->get(route('today'))
            ->assertOk()
            ->assertSee('Would you tell us how it is going?');
    }

    public function test_not_now_hides_it_for_that_session_only(): void
    {
        $user = $this->tester('later@escalate.test', 8);

        $this->actingAs($user)->post(route('feedback.defer'))->assertRedirect();
        $this->actingAs($user)->get(route('today'))->assertDontSee('Would you tell us how it is going?');

        // A fresh session is a fresh ask — deferring is "not now", not "never".
        $this->flushSession();

        $this->actingAs($user)->get(route('today'))->assertSee('Would you tell us how it is going?');
    }

    public function test_it_stops_being_offered_once_answered(): void
    {
        $user = $this->tester('done@escalate.test', 8);

        $this->actingAs($user)->post(route('feedback.store'), $this->answers())->assertRedirect(route('today'));

        $this->actingAs($user->fresh())->get(route('today'))->assertDontSee('Would you tell us how it is going?');
    }

    /* ── answering ───────────────────────────────────────────────────────── */

    public function test_an_answer_is_recorded_against_the_person_who_gave_it(): void
    {
        $user = $this->tester('answering@escalate.test', 8);

        $this->actingAs($user)->post(route('feedback.store'), $this->answers());

        $response = FeedbackResponse::firstOrFail();

        $this->assertSame($user->id, $response->user_id);
        $this->assertSame(FeedbackResponse::VERY, $response->disappointment);
        $this->assertSame('Hearing it in my own voice.', $response->benefit);
    }

    /** The killer question is the one thing that must be answered. */
    public function test_the_first_question_is_required_and_the_rest_are_not(): void
    {
        $user = $this->tester('partial@escalate.test', 8);

        $this->actingAs($user)->post(route('feedback.store'), $this->answers(['disappointment' => '']))
            ->assertSessionHasErrors('disappointment');

        $this->assertSame(0, FeedbackResponse::count());

        $this->actingAs($user)->post(route('feedback.store'), [
            'disappointment' => FeedbackResponse::NOT,
        ])->assertSessionHasNoErrors();

        $this->assertSame(1, FeedbackResponse::count());
    }

    /** An answer that was never offered cannot be posted. */
    public function test_a_made_up_answer_is_refused(): void
    {
        $this->actingAs($this->tester('crafty@escalate.test', 8))
            ->post(route('feedback.store'), $this->answers(['disappointment' => 'devastated']))
            ->assertSessionHasErrors('disappointment');

        $this->assertSame(0, FeedbackResponse::count());
    }

    public function test_answering_twice_amends_rather_than_duplicates(): void
    {
        $user = $this->tester('twice@escalate.test', 8);

        $this->actingAs($user)->post(route('feedback.store'), $this->answers());
        $this->actingAs($user->fresh())->post(route('feedback.store'),
            $this->answers(['disappointment' => FeedbackResponse::SOMEWHAT, 'improve' => 'Changed my mind.']));

        $this->assertSame(1, FeedbackResponse::count());
        $this->assertSame(FeedbackResponse::SOMEWHAT, FeedbackResponse::first()->disappointment);
        $this->assertSame('Changed my mind.', FeedbackResponse::first()->improve);
    }

    /** Prose answers are content, and stored like content. */
    public function test_the_written_answers_are_encrypted_at_rest(): void
    {
        $this->actingAs($this->tester('secret@escalate.test', 8))
            ->post(route('feedback.store'), $this->answers(['benefit' => 'A very distinctive benefit.']));

        $raw = DB::table('feedback_responses')->first();

        $this->assertStringNotContainsString('distinctive', $raw->benefit);

        // The scored answer stays readable — the whole measure groups on it.
        $this->assertSame(FeedbackResponse::VERY, $raw->disappointment);
    }

    /* ── the score ───────────────────────────────────────────────────────── */

    public function test_the_score_counts_only_the_very_disappointed(): void
    {
        foreach ([
            ['a@escalate.test', FeedbackResponse::VERY],
            ['b@escalate.test', FeedbackResponse::VERY],
            ['c@escalate.test', FeedbackResponse::SOMEWHAT],
            ['d@escalate.test', FeedbackResponse::NOT],
        ] as [$email, $answer]) {
            $this->actingAs($this->tester($email, 8))
                ->post(route('feedback.store'), $this->answers(['disappointment' => $answer]));
            $this->flushSession();
        }

        $admin = $this->makeUser('fbadmin@escalate.test', 'Admin');
        $admin->forceFill(['role' => 'admin'])->save();

        $this->actingAs($admin->fresh())
            ->withSession(['admin.verified' => true, 'admin.verified_at' => now()->timestamp])
            ->get(route('admin.feedback'))
            ->assertOk()
            ->assertSee('50%')                       // two of four said "very"
            ->assertSee('Hearing it in my own voice.');
    }

    public function test_the_feedback_screen_is_admin_only(): void
    {
        $this->actingAs($this->makeUser('curious@escalate.test'))
            ->get(route('admin.feedback'))
            ->assertNotFound();
    }

    /* ── erasure ─────────────────────────────────────────────────────────── */

    public function test_deleting_an_account_takes_the_answers_and_the_activity_with_it(): void
    {
        $user = $this->tester('leaving@escalate.test', 8);

        $this->actingAs($user)->get(route('today'));
        $this->actingAs($user->fresh())->post(route('feedback.store'), $this->answers());

        $this->assertSame(1, FeedbackResponse::count());
        $this->assertGreaterThan(0, DB::table('activity_days')->where('user_id', $user->id)->count());

        app(AccountEraser::class)->erase($user->fresh());

        $this->assertSame(0, FeedbackResponse::count());
        $this->assertSame(0, DB::table('activity_days')->where('user_id', $user->id)->count());
    }

    /** And the export says so, because "everything we hold" has to mean it. */
    public function test_the_export_includes_the_days_they_opened_the_app(): void
    {
        $user = $this->tester('exporting@escalate.test', 8);

        $this->actingAs($user)->get(route('today'));

        $export = app(AccountEraser::class)->export($user->fresh());

        $this->assertArrayHasKey('days_you_opened_the_app', $export['account']);
        $this->assertContains(now()->toDateString(), $export['account']['days_you_opened_the_app']);
    }
}
