<?php

namespace Tests\Feature;

use App\Models\GratitudeEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GratitudeTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $email = 'g@escalate.test'): User
    {
        return $this->makeUser($email);
    }

    public function test_an_entry_is_kept_and_encrypted(): void
    {
        $user = $this->user();

        $this->actingAs($user)->post(route('gratitude.store'), [
            'body' => 'THE-PLAINTEXT-ENTRY',
            'tags' => ['People', 'work'],
        ])->assertRedirect(route('gratitude.index'));

        $entry = $user->gratitudeEntries()->first();

        $this->assertSame('THE-PLAINTEXT-ENTRY', $entry->body);
        $this->assertSame(['People', 'work'], $entry->tags);
        $this->assertTrue($entry->for_date->isToday());

        // Ciphertext at rest, but the tag index is plaintext and normalised so
        // the archive can filter on it.
        $raw = \DB::table('gratitude_entries')->first();
        $this->assertStringNotContainsString('THE-PLAINTEXT-ENTRY', $raw->body);
        $this->assertSame('|people|work|', $raw->tag_index);
    }

    public function test_blank_tags_never_survive_to_the_archive(): void
    {
        $user = $this->user();

        // A blank surviving in `tags` renders as an empty chip — a bare circle
        // with no label — while tag_index correctly drops it, so the entry shows
        // a tag that no filter can match.
        $entry = $user->gratitudeEntries()->create([
            'body' => 'A', 'tags' => ['', 'people', '  ', 'people'], 'for_date' => today(),
        ]);

        $this->assertSame(['people'], $entry->fresh()->tags);
        $this->assertSame('|people|', \DB::table('gratitude_entries')->first()->tag_index);
    }

    public function test_search_finds_text_inside_encrypted_bodies(): void
    {
        $user = $this->user();

        $user->gratitudeEntries()->create(['body' => 'ZEBRAFISH in the tank', 'for_date' => today()]);
        $user->gratitudeEntries()->create(['body' => 'QUINCE on the windowsill', 'for_date' => today()->subDay()]);

        // A SQL LIKE could never do this — the column holds ciphertext.
        $this->actingAs($user)->get(route('gratitude.index', ['q' => 'zebrafish']))
            ->assertOk()
            ->assertSee('ZEBRAFISH in the tank')
            ->assertDontSee('QUINCE on the windowsill');
    }

    public function test_tag_filtering_matches_whole_tokens_only(): void
    {
        $user = $this->user();

        $user->gratitudeEntries()->create(['body' => 'A', 'tags' => ['work'], 'for_date' => today()]);
        $user->gratitudeEntries()->create(['body' => 'B', 'tags' => ['homework'], 'for_date' => today()]);

        $html = $this->actingAs($user)->get(route('gratitude.index', ['tag' => 'work']))
            ->assertOk()->getContent();

        // 'homework' contains 'work' as a substring; the pipe delimiters stop it
        // matching. Counting the entry bodies rather than the tag chips, which
        // legitimately contain both words.
        $this->assertStringContainsString('>A</div>', str_replace(["\n", '  '], '', $html));
        $this->assertStringNotContainsString('>B</div>', str_replace(["\n", '  '], '', $html));
    }

    public function test_a_streak_survives_today_not_being_written_yet(): void
    {
        $user = $this->user();

        // Yesterday and the day before, but nothing today.
        $user->gratitudeEntries()->create(['body' => 'A', 'for_date' => today()->subDay()]);
        $user->gratitudeEntries()->create(['body' => 'B', 'for_date' => today()->subDays(2)]);

        // Opening the app in the morning must not read as a broken streak.
        $this->actingAs($user)->get(route('gratitude.index'))
            ->assertOk()
            ->assertSeeInOrder(['>2<', 'days in a row'], false);
    }

    public function test_a_gap_ends_the_streak(): void
    {
        $user = $this->user();

        $user->gratitudeEntries()->create(['body' => 'A', 'for_date' => today()]);
        $user->gratitudeEntries()->create(['body' => 'B', 'for_date' => today()->subDays(3)]);

        $this->actingAs($user)->get(route('gratitude.index'))
            ->assertOk()
            ->assertSeeInOrder(['>1<', 'day in a row'], false);
    }

    public function test_an_entry_cannot_be_linked_to_someone_elses_desire(): void
    {
        $owner = $this->user('owner@escalate.test');
        $stranger = $this->user('stranger@escalate.test');

        $desire = $owner->desires()->create(['title' => 'Not yours', 'status' => 'desired']);

        $this->actingAs($stranger)->post(route('gratitude.store'), [
            'body' => 'Trying to link across accounts',
            'desire_id' => $desire->id,
        ])->assertRedirect();

        // Kept, but silently unlinked. Refusing would confirm the desire exists.
        $entry = $stranger->gratitudeEntries()->first();
        $this->assertNotNull($entry);
        $this->assertNull($entry->desire_id);
    }

    public function test_a_stranger_cannot_read_or_delete_an_entry(): void
    {
        $owner = $this->user('owner@escalate.test');
        $stranger = $this->user('stranger@escalate.test');

        $entry = $owner->gratitudeEntries()->create(['body' => 'SECRET-GRATITUDE', 'for_date' => today()]);

        $this->actingAs($stranger)->get(route('gratitude.index'))
            ->assertOk()
            ->assertDontSee('SECRET-GRATITUDE');

        $this->actingAs($stranger)->delete(route('gratitude.destroy', $entry))->assertNotFound();
        $this->actingAs($stranger)->put(route('gratitude.update', $entry), ['body' => 'hijacked'])->assertNotFound();

        $this->assertSame('SECRET-GRATITUDE', $entry->fresh()->body);
    }
}
