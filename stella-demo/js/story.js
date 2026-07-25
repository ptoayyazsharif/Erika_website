/* ============================================================
   story.js — talking to the backend, and surviving without it.
   Text and audio are two separate calls on purpose: the words
   land in ~4s and start revealing while the voice is still
   rendering, and neither request is long enough for cPanel to
   cut it off.
   ============================================================ */

const Story = (() => {

  const API = {
    story:  'api/generate-story.php',
    tts:    'api/synthesize.php',
    status: 'api/status.php'
  };

  async function postJSON(url, body, timeoutMs = 120000) {
    const ctrl = new AbortController();
    const timer = setTimeout(() => ctrl.abort(), timeoutMs);
    try {
      const res = await fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(body),
        signal: ctrl.signal
      });
      const text = await res.text();
      let data;
      try { data = JSON.parse(text); }
      catch { throw new Error('Bad response from ' + url + ': ' + text.slice(0, 120)); }
      if (!res.ok) throw new Error(data.error || ('HTTP ' + res.status));
      return data;
    } finally {
      clearTimeout(timer);
    }
  }

  /* ---- endpoints ------------------------------------------ */

  async function generate(profile) {
    try {
      const data = await postJSON(API.story, { profile });
      return data;
    } catch (err) {
      // No PHP (opened as a plain file?) or the server fell over.
      // The demo still has to run, so compose it here.
      return {
        story: localStory(profile),
        source: 'fallback_client',
        model: 'template (browser)',
        note: String(err.message || err),
        words: 0
      };
    }
  }

  async function synthesize(text, voice) {
    try {
      return await postJSON(API.tts, { text, voice });
    } catch (err) {
      return { url: null, mode: 'browser', note: String(err.message || err) };
    }
  }

  async function status() {
    try {
      const res = await fetch(API.status, { cache: 'no-store' });
      if (!res.ok) throw new Error('HTTP ' + res.status);
      return await res.json();
    } catch {
      return { ok: false, llm: 'none', tts: false, offline: true };
    }
  }

  async function clearCache() {
    return postJSON(API.status, { action: 'clear-cache' }, 20000);
  }

  /** The reading left behind by api/prewarm.php, if there is one. */
  async function warmedReading() {
    try {
      const res = await fetch('audio/generated/demo-reading.json', { cache: 'no-store' });
      if (!res.ok) return null;
      const data = await res.json();
      return (data && data.story && data.url) ? data : null;
    } catch { return null; }
  }

  /** Generate + synthesise ahead of time and cache both (slow, runs once). */
  async function prewarm(profile, voice) {
    return postJSON('api/prewarm.php?json=1', { profile, voice }, 300000);
  }

  /* ---- client-side template story -------------------------
     Mirrors fallback_story() in api/lib.php. Keep the two in
     step if you edit the wording.
     -------------------------------------------------------- */

  function localStory(p) {
    const v = (k, d = '') => (p[k] && String(p[k]).trim()) || d;

    const place   = v('success_place', 'my kitchen, early, before anyone else is up');
    const detail  = v('sensory_detail', 'the light coming across the counter');
    const partner = v('partner');
    const people  = v('people');
    const desire  = v('desire', 'the life I kept describing to myself');
    const work    = v('work_vision', 'work that finally looks like me');
    const number  = v('proof_number', 'the number I used to check twice a day');
    const caller  = v('good_news_caller', 'someone on my team, with news');
    const habit   = v('scarcity_habit', 'check the balance before I let myself want anything');
    const where   = v('milestone_location', v('city', 'the coast'));
    const stone   = v('milestone', 'the place we said we would buy one day');
    const spot    = v('spot');
    const city    = v('city', 'this city');

    const up = s => s ? s.charAt(0).toUpperCase() + s.slice(1) : s;

    let peopleLine;
    if (partner && people)   peopleLine = `${up(partner)} is still asleep. Later there will be ${people}, and the noise that comes with them.`;
    else if (partner)        peopleLine = `${up(partner)} is still asleep down the hall.`;
    else if (people)         peopleLine = `Later there will be ${people}, and the noise that comes with them.`;
    else                     peopleLine = 'The house is quiet in the way I used to wish it would be.';

    const spotLine = spot
      ? `Later I will walk to ${spot}, because I can, because the middle of a Tuesday belongs to me now.`
      : 'Later I will walk, because I can, because the middle of a Tuesday belongs to me now.';

    return [
      `It is early. I am in ${place}, and the house has that particular quiet it gets before the day decides what it wants from me. I notice ${detail} the way you notice a thing you own, without ceremony. ${peopleLine} I make coffee. I do not check anything on my phone yet, and that alone would have been unthinkable a few years ago.`,
      `This is the part I want to say plainly: ${desire} is not a plan anymore. It is the address I live at. The work is ${work}, and it runs whether or not I am anxious about it. ${up(number)}. I know the figure without looking, the way you know your own height.`,
      `The phone buzzes on the counter: ${caller}. I read it twice, not because I doubt it, but because I like the shape of it. I write back one line. Then I put the phone face down and finish the coffee while it is still hot, which is its own kind of proof.`,
      `Three years ago, I would have ${habit}. Every single time. I would have run the arithmetic in my head twice before saying yes to anything, and then felt the small tightening in my chest that came with saying it anyway. This morning I did not do that. It did not even occur to me to do it. That is the difference — not the number in the account, but the absence of that reflex.`,
      `In the fall we go back to ${where}, to ${stone}, and the strangest part is how ordinary it feels to say so. There is a drawer there with our things in it. Someone has to remember to have the mail held. That is what having it actually looks like: logistics, and a key that lives on my ring next to the others.`,
      `${spotLine} ${city} looks the way it always looked. I am the thing that changed.`,
      `I rinse the cup and set it in the rack. There is a whole day out there and none of it is urgent. I stand at the window for another minute, because nobody is timing me, and then I go and start.`
    ].join('\n\n');
  }

  /* ---- text shaping --------------------------------------- */

  /** Paragraphs for the reveal. Long ones are split at sentence
   *  boundaries so no single block dumps eight lines at once. */
  function toLines(text, maxWords = 55) {
    const paras = String(text).split(/\n\s*\n/).map(s => s.trim()).filter(Boolean);
    const out = [];
    for (const para of paras) {
      if (countWords(para) <= maxWords) { out.push(para); continue; }
      const sentences = para.match(/[^.!?]+[.!?]+["']?\s*|[^.!?]+$/g) || [para];
      let buf = '';
      for (const s of sentences) {
        if (countWords(buf + s) > maxWords && buf) { out.push(buf.trim()); buf = s; }
        else buf += s;
      }
      if (buf.trim()) out.push(buf.trim());
    }
    return out;
  }

  function countWords(s) { return (String(s).trim().match(/\S+/g) || []).length; }

  /* ---- share links ---------------------------------------- */

  function encodeProfile(profile) {
    const json = JSON.stringify(profile);
    return btoa(unescape(encodeURIComponent(json)))
      .replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
  }

  function decodeProfile(str) {
    try {
      const b64 = str.replace(/-/g, '+').replace(/_/g, '/');
      return JSON.parse(decodeURIComponent(escape(atob(b64))));
    } catch { return null; }
  }

  return { generate, synthesize, status, clearCache, warmedReading, prewarm,
           toLines, countWords, encodeProfile, decodeProfile, localStory };
})();
