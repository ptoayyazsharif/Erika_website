<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\User;
use App\Support\Copy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

/**
 * Erika's wording, and her being able to change it without a deploy.
 *
 * Copy that needs a developer to edit is copy that stays wrong for the week it
 * matters most, which is why these strings moved out of the Blade templates.
 */
class CopyTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $user = $this->makeUser('copyadmin@escalate.test', 'Admin');
        $user->forceFill(['role' => 'admin'])->save();

        $this->actingAs($user->fresh())
            ->withSession(['admin.verified' => true, 'admin.verified_at' => now()->timestamp]);

        return $user->fresh();
    }

    /* ── the wording ships as she wrote it ───────────────────────────────── */

    public function test_the_application_form_asks_erikas_questions(): void
    {
        $page = $this->get(route('apply'))->assertOk();

        $page->assertSee('What area of your life are you currently focused on changing, improving or creating something new in?');
        $page->assertSee('Career, money, relationships, health, family, lifestyle, personal growth');
        $page->assertSee('such as journaling, visualization, prayer, meditation or affirmations?');
        $page->assertSee('manifestation, visualization, journaling or personal-development app?');
        $page->assertSee('There are no right answers');
    }

    /** Four and five were left alone, and must stay that way. */
    public function test_the_last_two_questions_are_unchanged(): void
    {
        $page = $this->get(route('apply'))->assertOk();

        $page->assertSee('at least 4 times during a 7-day test?');
        $page->assertSee('candid feedback after the test?');
    }

    /* ── and she can change it ───────────────────────────────────────────── */

    public function test_an_admin_edit_reaches_the_public_page(): void
    {
        $this->admin();

        $this->put(route('admin.settings.update'), ['settings' => [
            'escalate__copy__q_changing' => 'What are you working on right now?',
            'escalate__copy__intro'      => 'A quieter way to plan a life.',
        ]])->assertRedirect();

        \App\Support\Settings::flush();
        \App\Support\Settings::apply();

        $this->get(route('apply'))->assertOk()->assertSee('What are you working on right now?');

        // The landing page belongs to signed-out visitors; an admin is sent to
        // Today, so check it as the stranger it is written for.
        auth()->logout();
        session()->flush();

        $this->get('/')->assertOk()->assertSee('A quieter way to plan a life.');
    }

    /** Clearing a field restores her original wording rather than emptying it. */
    public function test_clearing_an_override_goes_back_to_the_default(): void
    {
        $original = config('escalate.copy.intro');

        $this->admin();

        $this->put(route('admin.settings.update'), ['settings' => [
            'escalate__copy__intro' => 'Something else entirely.',
        ]]);

        $this->put(route('admin.settings.update'), ['settings' => [
            'escalate__copy__intro' => '',
        ]]);

        \App\Support\Settings::flush();
        \App\Support\Settings::apply();

        $this->assertSame($original, config('escalate.copy.intro'));
    }

    /* ── the label above an answer follows the question ──────────────────── */

    public function test_the_admin_reads_an_answer_under_the_question_that_produced_it(): void
    {
        Config::set('mail.default', 'array');

        $this->post(route('apply.store'), [
            'name' => 'A Person', 'email' => 'person@example.test',
            'changing' => 'Moving somewhere quieter.', 'practice' => 'I journal.',
            'tried_apps' => 'One.', 'will_use' => 'Yes.', 'will_feedback' => 'Yes.',
            'agree' => '1',
        ]);

        $this->admin();

        $this->get(route('admin.applications.show', Application::first()))
            ->assertOk()
            ->assertSee(Copy::question('changing'))
            ->assertSee('Moving somewhere quieter.');
    }

    /** The keys line up, so a question can never lose its answer. */
    public function test_every_question_maps_to_a_column_that_exists(): void
    {
        $columns = ['changing', 'practice', 'tried_apps', 'will_use', 'will_feedback'];

        $this->assertSame($columns, Copy::FIELDS);

        foreach (Copy::questions() as [$field, $question, $help]) {
            $this->assertContains($field, $columns);
            $this->assertNotSame('', trim($question), "Question for {$field} is empty.");
        }
    }

    /* ── the outreach message ────────────────────────────────────────────── */

    public function test_the_message_to_send_is_on_the_invites_screen(): void
    {
        $this->admin();

        $this->get(route('admin.invites'))
            ->assertOk()
            ->assertSee('The message to send')
            ->assertSee('private beta testing');
    }
}
