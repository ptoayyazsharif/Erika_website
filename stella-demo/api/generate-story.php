<?php
/* POST { ...profile } → { story, source, model, words } */

require_once __DIR__ . '/lib.php';

rate_limit('story');

$input   = read_json_input();
$profile = clean_profile($input['profile'] ?? $input);

if (pv($profile, 'name') === '' && pv($profile, 'desire') === '') {
    fail('Tell me at least a name or a desire to write from.');
}

$result = llm_story($profile);
$source = 'llm';
$model  = $result['model'] ?? '';
$note   = '';

if (!$result['ok']) {
    // Never leave the user staring at an error mid-demo — fall through to the
    // template story and say so in the payload for the debug panel.
    $note   = $result['error'] ?: 'llm_unavailable';
    $source = ($note === 'no_api_key') ? 'fallback' : 'fallback_after_error';
    $story  = fallback_story($profile);
} else {
    $story = tidy_story($result['text']);
    if (word_count($story) < 120) {          // too short to be a reading
        $note   = 'llm_short_output';
        $source = 'fallback_after_error';
        $story  = fallback_story($profile);
    }
}

$payload = [
    'story'  => $story,
    'source' => $source,
    'model'  => $source === 'llm' ? $model : 'template',
    'words'  => word_count($story),
];
if ($note !== '')  $payload['note'] = $note;
if (DEMO_TOOLS)    $payload['prompt'] = story_user_prompt($profile);

json_out($payload);
