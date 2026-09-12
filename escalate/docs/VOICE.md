# How Escalate speaks

Erika's voice guide, verbatim, followed by where it is enforced in the code.

This is the source of truth. If a string in the app and a line in this file
disagree, the string is wrong.

---

## The guide

Write in clear, conversational, relatable language. Sound like a smart, warm,
encouraging friend who knows the user well.

Use everyday vocabulary, contractions and natural sentence structures. Limited
casual language and occasional subtle humor are welcome when appropriate.

Do not sound mystical, whimsical, esoteric, overly poetic, therapeutic,
corporate, preachy or like a motivational speaker.

Never use complicated language when a simpler phrase communicates the same idea.

Avoid manifestation clichés such as "calling it in," "energetic frequency,"
"highest timeline," "vibrational alignment," "the universe is conspiring,"
"divine timing," or similar language unless the user's explicit language/belief
preference supports that vocabulary.

Do not assume why an event happened. Do not tell users something was destiny, a
sign, divine intervention, universal alignment or meant to be unless the user
has explicitly interpreted it that way.

Default to language about visions, goals, progress, opportunities, choices,
gratitude, reflection, achievements and change.

Respect the user's selected belief/language preference when generating
personalized content.

Encourage without excessive cheerleading. Recognize meaningful progress
naturally.

Ask clear, thoughtful questions when more specificity would improve the user's
experience.

For UI and instructions: prioritize clarity and brevity. Say exactly what the
user needs to know or do.

For personalized stories: prioritize vividness. Create specific, sensory,
emotionally engaging scenes that feel grounded in the user's actual life. Use
concrete details, people, places, routines, conversations and ordinary moments.
Make the future feel believable rather than magically perfect.

Do not confuse vivid with poetic. Specific details are more valuable than
elaborate metaphors.

The desired reaction to a generated story is:

> "I can actually see myself living this."

The desired reaction to the Escalate interface is:

> "This is easy. I know exactly what to do."

The desired emotional result of the overall experience is:

**clear, inspired, present and capable.**

---

## Where this lives in the code

**Generated prose** — `App\Support\Voice` holds the fragments the three writers
share, and `StoryWriter`, `AffirmationWriter` and `RewindWriter` read them. It
exists because the rules were duplicated across those three files and had
already drifted apart: two belief languages were silently missing from the
affirmation prompt, so people who chose them got secular cards.

**Interface copy** lives where it is used — Blade views, controller flash
messages, `App\Support\Quota`, and the label/note pairs in
`config/escalate.php`. There is no central string table and adding one would
not help; the fix for drift is the test below, not indirection.

**`tests/Feature/VoiceTest.php`** asserts the named clichés appear nowhere, and
that every belief language produces its own instruction in all three writers.

---

## Two rules that are easy to get wrong

**The cliché ban is conditional, not absolute.** Somebody who chose *The
universe, energy, alignment* as their belief language asked for that vocabulary,
and withholding it is not neutrality — it is overriding a preference they set
deliberately. The ban applies by default and lifts for the register they chose.
`Voice::faithRule()` is the only place that decision is made.

**Interpretation belongs to the person, not the app.** The status labels —
Manifested, Answered Prayer, Achieved, Redirected, Released — are chosen *by the
user* about their own life, so they are their interpretation and are allowed to
stay exactly as they are. What is not allowed is the app adding one: a note
reading "and it was right" told somebody what an event meant. That is the line.
