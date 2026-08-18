<?php

namespace App\Services;

use App\Models\Rewind;
use App\Services\Ai\Anthropic;

/**
 * Writes the retrospective for a Rewind.
 *
 * A reading and a rewind are opposites and the prompts have to be, or the
 * rewind comes out sounding like another manifestation piece.
 *
 * A reading is present tense, imagined, and describes a life the person has
 * not lived yet. A rewind is past tense and strictly factual: it may only use
 * what the person actually wrote in the reflection, because they are the only
 * source for what really happened. Every invented detail in a reading is the
 * point; a single invented detail here would be the app telling someone their
 * own history wrong.
 */
class RewindWriter
{
    public function __construct(private Anthropic $anthropic) {}

    public function write(Rewind $rewind): void
    {
        $text = $this->anthropic->write(
            $this->system(),
            $this->user($rewind),
            $rewind->user,
            'rewind',
        );

        $rewind->forceFill([
            'narrative' => trim($text),
            'state'     => 'ready',
            'failure_reason' => null,
        ])->save();
    }

    private function system(): string
    {
        return <<<'PROMPT'
        You write Rewinds: short retrospectives about something that has already
        happened in a person's life, assembled from notes they wrote themselves.

        NON-NEGOTIABLE RULES

        1. Past tense throughout. This already happened. Nothing is arriving,
           unfolding or manifesting.
        2. First person — "I". These are their own notes read back to them.
           Never write their name, never "she", "he" or "they" meaning them.
        3. Invent nothing. Use only what is in the notes below. If they did not
           say how something felt, do not decide for them. If a detail is
           missing, write around it — never fill the gap. This is their history,
           and getting it wrong is worse than leaving it thin.
        4. Between 200 and 350 words, in prose paragraphs separated by blank
           lines. No headings, no lists, no preamble — begin with the first
           sentence itself.
        5. Follow the shape of what happened: where it started, what turned,
           what it cost, where it landed. If they recorded a redirection — it
           arrived as something other than they asked for — that is the centre
           of the piece.
        6. Name the people they named, exactly as written, and only those.
        7. No moral, no lesson, no "and that is when I realised". If they wrote
           down what they learned, state it as plainly as they did and stop.

        VOICE

        Plain and unhurried. Short declarative sentences. This is someone
        telling a friend what happened, not a memoir.

        Avoid "journey", "manifest" and its relatives, "abundance", "alignment",
        "grateful for this beautiful". Never use an exclamation mark.

        Output only the piece.
        PROMPT;
    }

    private function user(Rewind $rewind): string
    {
        $desire = $rewind->desire;
        $lines = ['WHAT THEY NAMED'];
        $lines[] = 'The desire: '.$desire->title;

        if (filled($desire->description)) {
            $lines[] = 'How they described it: '.$desire->description;
        }

        $lines[] = 'Named on: '.$desire->created_at->format('j F Y');
        $lines[] = 'How it ended: '.$desire->statusLabel()
            .' — '.(config("escalate.statuses.{$desire->status}.note") ?? '');

        if ($desire->status_changed_at) {
            $lines[] = 'Which took: '.$desire->created_at->diffForHumans($desire->status_changed_at, ['syntax' => true, 'parts' => 2]);
        }

        $lines[] = '';
        $lines[] = 'THEIR NOTES — the only source for what happened';

        foreach ($rewind->answered() as $row) {
            $lines[] = $row['label'].': '.$row['value'];
        }

        $lines[] = '';
        $lines[] = 'Write the Rewind.';

        return implode("\n", $lines);
    }
}
