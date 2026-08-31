/* ============================================================================
   Escalate — the affirmation cards screen
   ----------------------------------------------------------------------------
   One job: while a set is being drawn, ask whether it is ready, and reload the
   page when it is.

   Deliberately not a second copy of reading.js. That screen has theatre to
   perform — rotating lines, a progress bar, audio — because a reading takes
   twenty-five seconds and is the thing the person came for. Five short cards
   take a few seconds, so the honest interface is a sentence and a reload.

   Backs off as it goes, so a slow queue becomes a slow poll rather than a
   request storm. Gives up after a couple of minutes rather than polling a dead
   job forever; the page still says what to do without this file ever loading.
   ========================================================================= */

(() => {
  'use strict';

  const root = document.querySelector('[data-cards-poll]');
  if (!root) return;

  const url = root.dataset.cardsPoll;
  if (!url) return;

  let delay = 1500;
  const MAX_DELAY = 6000;
  const GIVE_UP_AT = Date.now() + 120000;

  async function check() {
    if (Date.now() > GIVE_UP_AT) return;

    try {
      const res = await fetch(url, {
        headers: { Accept: 'application/json' },
        credentials: 'same-origin',
      });

      if (!res.ok) throw new Error(String(res.status));

      const data = await res.json();

      // Both endings reload: 'ready' to show the cards, 'failed' to show the
      // reason and the way to try again, both of which are already rendered
      // server-side. Nothing is drawn from the JSON, so there is no second
      // copy of the card markup to keep in step with the Blade template.
      if (data.state === 'ready' || data.state === 'failed') {
        window.location.reload();
        return;
      }
    } catch {
      // A blip is not a reason to stop; the give-up clock still applies.
    }

    delay = Math.min(delay * 1.4, MAX_DELAY);
    setTimeout(check, delay);
  }

  setTimeout(check, delay);
})();
