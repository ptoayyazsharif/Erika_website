<?php
/**
 * Distillation: one scrubbed chunk -> knowledge items (status='pending').
 * Uses the 'distill' model role with forced JSON output.
 */
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/gemini.php';

const ALLOWED_TOPICS = [
    'prospecting', 'objection-handling', 'listing-presentation', 'buyer-consultation',
    'negotiation', 'pricing', 'marketing', 'follow-up', 'contracts', 'mindset',
    'time-management', 'business-building',
];

const DISTILL_PROMPT = <<<'PROMPT'
You process transcripts from real estate coaching calls led by Erika Page,
an Atlanta-area agent who mentors newer agents. Extract transferable
knowledge as standalone items.

FOR EACH ITEM:

question — the canonical question a newer agent would actually type into a
search box. Rewrite for searchability, not for fidelity to how it was asked
on the call. "So like, what if they, you know, come in way under?" becomes
"How do I handle a lowball offer?"

answer — Erika's answer, in HER words and phrasing. This must stand alone:
someone reading it cold, with no other context, should fully understand it.
Resolve every back-reference. If she says "like I said earlier about
pre-approval," write out what she said earlier. If she says "do that with
this type of seller," name the type.

type — qa | principle | script | story
  qa: a direct question and her answer
  principle: a rule or belief she states unprompted
  script: specific words to say to a client or agent
  story: a deal anecdote that carries a lesson

topics — 1 to 3, ONLY from this list:
prospecting, objection-handling, listing-presentation, buyer-consultation,
negotiation, pricing, marketing, follow-up, contracts, mindset,
time-management, business-building

timestamp_ref — where in this chunk it occurs, if the transcript has
timestamps. Empty string if not.

sensitive — true if it touches contract terms, legal obligations, fair
housing, agency disclosure, or commission structure.

PRESERVE HER VOICE. This is the entire point of the system. Keep her actual
idioms, analogies, and phrasing. Do NOT smooth her into generic coaching
language — if the output could have come from any real estate blog, you
have failed. Better to quote her closely than to paraphrase cleanly.

REDACT. Replace every client name with [CLIENT], every mentee name with
[MENTEE], every brokerage other than Erika's with [BROKERAGE], and every
street address with [ADDRESS]. Keep city and neighborhood names — those
carry real meaning. Never let a specific person's deal details survive
into an item.

SKIP: scheduling, tech problems, small talk, recaps of previous calls,
and anything specific to one mentee's situation that wouldn't help anyone
else.

QUALITY OVER QUANTITY. Twelve sharp items beat forty vague ones. If a
stretch of transcript contains nothing transferable, extract nothing from
it. An empty items array is a valid response.
PROMPT;

function distill_schema(): array {
    return [
        'type' => 'object',
        'properties' => [
            'items' => [
                'type' => 'array',
                'items' => [
                    'type' => 'object',
                    'properties' => [
                        'question'      => ['type' => 'string'],
                        'answer'        => ['type' => 'string'],
                        'type'          => ['type' => 'string', 'enum' => ['qa', 'principle', 'script', 'story']],
                        'topics'        => ['type' => 'array', 'items' => ['type' => 'string']],
                        'timestamp_ref' => ['type' => 'string'],
                        'sensitive'     => ['type' => 'boolean'],
                    ],
                    'required' => ['question', 'answer', 'type', 'topics', 'sensitive'],
                ],
            ],
        ],
        'required' => ['items'],
    ];
}

/**
 * Distill one chunk body.
 * @return array{0:bool,1:array,2:string,3:string}  [ok, items, error, modelUsed]
 */
function distill_chunk(string $body): array {
    [$ok, $data, $err, $model] = gemini_json('distill', DISTILL_PROMPT, $body, distill_schema());
    if (!$ok) return [false, [], $err, $model];
    $items = [];
    foreach (($data['items'] ?? []) as $it) {
        $q = trim((string) ($it['question'] ?? ''));
        $a = trim((string) ($it['answer'] ?? ''));
        if ($q === '' || $a === '') continue;
        $topics = array_values(array_intersect(
            array_map('strval', (array) ($it['topics'] ?? [])),
            ALLOWED_TOPICS
        ));
        $items[] = [
            'question'      => mb_substr($q, 0, 500),
            'answer'        => $a,
            'type'          => in_array($it['type'] ?? '', ['qa', 'principle', 'script', 'story'], true) ? $it['type'] : 'qa',
            'topics'        => $topics,
            'timestamp_ref' => mb_substr((string) ($it['timestamp_ref'] ?? ''), 0, 20),
            'sensitive'     => !empty($it['sensitive']) ? 1 : 0,
        ];
    }
    return [true, $items, '', $model];
}

/** Persist distilled items for a transcript as status='pending'. Returns count. */
function save_items(int $transcriptId, array $items): int {
    $stmt = db()->prepare(
        'INSERT INTO items (transcript_id, type, question, answer, topics, timestamp_ref, `sensitive`, status)
         VALUES (?, ?, ?, ?, ?, ?, ?, "pending")'
    );
    $n = 0;
    foreach ($items as $it) {
        $stmt->execute([
            $transcriptId, $it['type'], $it['question'], $it['answer'],
            json_encode($it['topics']), $it['timestamp_ref'] ?: null, $it['sensitive'],
        ]);
        $n++;
    }
    return $n;
}
