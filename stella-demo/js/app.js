/* ============================================================
   app.js — the flow controller.
   landing → quiz → reading → reveal → player → paywall
   ============================================================ */

(() => {
  const $  = (s, r = document) => r.querySelector(s);
  const $$ = (s, r = document) => [...r.querySelectorAll(s)];

  const SCREENS = ['landing', 'quiz', 'reading', 'reveal', 'player', 'paywall'];
  const STORE_KEY = 'manifest.profile.v1';
  const MIN_THEATRE = 6200;      // the reading is non-negotiable
  // How long we'll hold the reveal waiting on the voice. ElevenLabs answers in
  // a few seconds; Gemini takes ~35s for a whole reading, so it gets a longer
  // leash — and pre-warming (api/prewarm.php) makes it instant either way.
  const AUDIO_WAIT = { elevenlabs: 12000, gemini: 20000, browser: 1000 };

  const state = {
    screen: 'landing',
    profile: {},
    index: 0,
    story: '',
    lines: [],
    audioUrl: null,
    audioMode: 'browser',
    voice: 'calm',
    server: null,
    lit: -1,
    revealTimer: null,
    generating: false
  };

  /* ══ screens ══════════════════════════════════════════════ */

  async function go(name, { push = true } = {}) {
    if (name === state.screen) return;
    const from = $(`#screen-${state.screen}`);
    const to   = $(`#screen-${name}`);
    if (!to) return;

    await Anim.outScreen(from);
    if (from) { from.hidden = true; from.style.opacity = ''; }
    to.hidden = false;
    state.screen = name;
    Anim.inScreen(to);

    if (push && location.hash !== '#/' + name) {
      history.pushState({ screen: name }, '', '#/' + name);
    }
    const focusable = to.querySelector('h1, h2, [autofocus]');
    if (focusable) focusable.setAttribute('tabindex', '-1'), focusable.focus({ preventScroll: true });
    window.scrollTo({ top: 0, behavior: 'instant' in window ? 'instant' : 'auto' });
  }

  window.addEventListener('popstate', e => {
    const name = (e.state && e.state.screen) || hashScreen() || 'landing';
    // Going back into a generated screen without a story would show an empty
    // stage, so bounce those to the landing.
    if ((name === 'reveal' || name === 'player') && !state.story) return go('landing', { push: false });
    if (name === 'reading') return go('landing', { push: false });
    go(name, { push: false });
  });

  function hashScreen() {
    const m = location.hash.match(/^#\/(\w+)$/);
    return m && SCREENS.includes(m[1]) ? m[1] : null;
  }

  /* ══ toast ════════════════════════════════════════════════ */

  let toastTimer;
  function toast(msg, ms = 2600) {
    const el = $('#toast');
    el.textContent = msg;
    el.hidden = false;
    requestAnimationFrame(() => el.classList.add('is-shown'));
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => {
      el.classList.remove('is-shown');
      setTimeout(() => { el.hidden = true; }, 400);
    }, ms);
  }

  /* ══ quiz ═════════════════════════════════════════════════ */

  function saveProfile() {
    try { localStorage.setItem(STORE_KEY, JSON.stringify(state.profile)); } catch {}
    syncDebug();
  }

  function loadProfile() {
    try {
      const raw = localStorage.getItem(STORE_KEY);
      if (raw) state.profile = JSON.parse(raw) || {};
    } catch {}
  }

  function paintQuestion(back = false) {
    const stage = $('#q-stage');
    const q = Quiz.QUESTIONS[state.index];
    const old = stage.firstElementChild;

    const node = Quiz.render(state.index, state.profile[q.key] || '', {
      onChange: val => { state.profile[q.key] = val; clearError(); saveProfile(); },
      onAdvance: () => next()
    });

    const mount = () => {
      stage.appendChild(node);
      Anim.questionIn(node, back);
      setTimeout(() => Quiz.focusField(node), 120);
    };

    if (old) Anim.questionOut(old).then(mount); else mount();

    $('#q-index').textContent = state.index + 1;
    $('#q-group').textContent = q.group;
    $('.progress').setAttribute('aria-valuenow', state.index + 1);
    Anim.progressTo($('#progress-fill'), ((state.index + 1) / Quiz.QUESTIONS.length) * 100);

    $('[data-action="prev"]').disabled = state.index === 0;
    $('[data-action="skip"]').hidden = !!q.required;
    $('[data-action="next"]').textContent =
      state.index === Quiz.QUESTIONS.length - 1 ? 'Read my intentions' : 'Continue';
  }

  function showError(msg) {
    const el = $('.q-error', $('#q-stage'));
    if (el) el.textContent = msg;
  }
  function clearError() { showError(''); }

  function next() {
    const q = Quiz.QUESTIONS[state.index];
    const err = Quiz.validate(state.index, state.profile[q.key]);
    if (err) { showError(err); return; }
    clearError();
    if (state.index < Quiz.QUESTIONS.length - 1) {
      state.index++;
      paintQuestion();
    } else {
      startReading();
    }
  }

  function prev() {
    if (state.index === 0) return;
    state.index--;
    paintQuestion(true);
  }

  function skip() {
    const q = Quiz.QUESTIONS[state.index];
    if (q.required) return;
    if (!state.profile[q.key]) state.profile[q.key] = Quiz.DEFAULTS[q.key] || '';
    saveProfile();
    next();
  }

  function startQuiz(fresh) {
    if (fresh) {
      state.profile = {};
      state.index = 0;
      try { localStorage.removeItem(STORE_KEY); } catch {}
    }
    go('quiz');
    paintQuestion();
  }

  /* ══ the reading (loading theatre) ════════════════════════ */

  const THEATRE_LINES = [
    'Reading your intentions…',
    'Listening for the names you gave me…',
    'Finding the ordinary morning inside it…',
    'Setting the table, pouring the coffee…',
    'Putting the numbers where you can see them…',
    'Writing it in present tense…',
    'Almost. This part shouldn’t be rushed…'
  ];

  /** @param {object} [warm] a pre-warmed { story, url } to replay instead of generating */
  async function startReading(warm) {
    if (state.generating) return;
    state.generating = true;

    await go('reading');
    Player.resume();                       // the click that got us here counts as the gesture

    const lineEl = $('#reading-line');
    let li = 0;
    lineEl.textContent = THEATRE_LINES[0];
    const rotate = setInterval(() => {
      li = (li + 1) % THEATRE_LINES.length;
      Anim.readingLine(lineEl, THEATRE_LINES[li]);
    }, 2600);
    Anim.readingProgress($('#reading-fill'), 9);

    const began = Date.now();
    let result = null, audio = null;

    try {
      if (warm) {
        // Already written and already narrated — just play it.
        result = { story: warm.story, source: 'prewarmed', model: warm.engine || 'cache', words: warm.words || 0 };
        state.story = warm.story;
        state.lines = Story.toLines(warm.story);
        syncDebug(result);
        audio = { url: warm.url, mode: 'file', engine: warm.engine || 'cache' };
      } else {
        result = await Story.generate(state.profile);
        state.story = result.story;
        state.lines = Story.toLines(result.story);
        syncDebug(result);

        const engine  = (state.server && state.server.tts_engine) || 'browser';
        const waitFor = AUDIO_WAIT[engine] ?? 12000;
        const ttsPromise = Story.synthesize(result.story, state.voice);
        const capped = new Promise(res => setTimeout(() => res(null), waitFor));
        audio = await Promise.race([ttsPromise, capped]);
        if (!audio) ttsPromise.then(a => lateAudio(a));   // arrived after we gave up waiting
      }
    } catch (err) {
      state.story = Story.localStory(state.profile);
      state.lines = Story.toLines(state.story);
      console.warn('[manifest]', err);
    }

    state.audioUrl  = audio && audio.url ? audio.url : null;
    state.audioMode = audio && audio.mode ? audio.mode : 'browser';

    const waited = Date.now() - began;
    if (waited < MIN_THEATRE) await sleep(MIN_THEATRE - waited);

    clearInterval(rotate);
    Anim.readingDone($('#reading-fill'));
    await sleep(500);

    state.generating = false;
    await beginReveal();
  }

  function lateAudio(a) {
    if (!a || !a.url || state.audioUrl) return;
    state.audioUrl = a.url;
    state.audioMode = 'file';
    $('#player-note').textContent = '';
    // Swap the voice in only if nothing is playing yet — never yank audio out
    // from under a reveal that's already running. If it is, offer the replay.
    if (!Player.isPlaying()) {
      Player.loadVoice(a.url, state.story);
    } else {
      toast('The studio voice is ready — "Read it again" to hear it.', 5000);
    }
  }

  const sleep = ms => new Promise(r => setTimeout(r, ms));

  /* ══ the reveal (sacred) ══════════════════════════════════ */

  async function beginReveal() {
    const box = $('#story-lines');
    box.innerHTML = '';
    state.lit = -1;

    state.lines.forEach(text => {
      const p = document.createElement('p');
      p.textContent = text;
      box.appendChild(p);
    });

    $('.reveal-foot').classList.remove('is-shown');
    await go('reveal');

    await Player.loadVoice(state.audioUrl, state.story);
    await Player.setAmbience(currentAmbience());
    const started = await Player.play();
    if (!started) {
      toast('Tap anywhere to start the voice.', 4000);
      document.addEventListener('pointerdown', () => Player.play(), { once: true });
    }

    scheduleLines();
    // The way out appears once she's had a moment with it.
    clearTimeout(state.revealTimer);
    state.revealTimer = setTimeout(() => $('.reveal-foot').classList.add('is-shown'), 9000);
  }

  /** Lines are lit in proportion to their word count across the
   *  voice's real duration, so the text tracks the narration. */
  function lineSchedule() {
    const counts = state.lines.map(l => Story.countWords(l));
    const total  = counts.reduce((a, b) => a + b, 0) || 1;
    const dur    = Player.duration() || total / 2.35;
    const lead   = 0.35;                          // text lands just before the voice
    let acc = 0;
    return counts.map(c => {
      const at = (acc / total) * dur - lead;
      acc += c;
      return Math.max(0, at);
    });
  }

  let schedule = [];
  function scheduleLines() { schedule = lineSchedule(); }

  Player.onProgress(({ time, duration }) => {
    // reveal: light the lines
    if (state.screen === 'reveal' && schedule.length) {
      if (duration && Math.abs((schedule.at(-1) || 0) - duration) > duration * .6) scheduleLines();
      const box = $('#story-lines');
      const ps = box.children;
      for (let i = 0; i < schedule.length; i++) {
        if (time >= schedule[i] && i > state.lit) {
          if (state.lit >= 0 && ps[state.lit]) Anim.dimLine(ps[state.lit]);
          state.lit = i;
          const p = ps[i];
          if (p) {
            Anim.litLine(p);
            const r = p.getBoundingClientRect();
            if (r.bottom > innerHeight * .72 || r.top < innerHeight * .18) {
              p.scrollIntoView({ behavior: Anim.reduced ? 'auto' : 'smooth', block: 'center' });
            }
          }
        }
      }
      if (state.lit >= schedule.length - 1) $('.reveal-foot').classList.add('is-shown');
    }

    // player: transport UI
    if (state.screen === 'player' || true) {
      const pct = duration ? Math.min(100, (time / duration) * 100) : 0;
      const fill = $('#scrub-fill');
      if (fill) fill.style.width = pct + '%';
      const scrub = $('#scrub');
      if (scrub) { scrub.style.setProperty('--pos', pct + '%'); scrub.setAttribute('aria-valuenow', Math.round(pct)); }
      const now = $('#t-now'), tot = $('#t-total');
      if (now) now.textContent = fmt(time);
      if (tot) tot.textContent = fmt(duration);
      setPlayIcon(Player.isPlaying());
    }
  });

  function fmt(s) {
    s = Math.max(0, Math.floor(s || 0));
    return Math.floor(s / 60) + ':' + String(s % 60).padStart(2, '0');
  }

  // NB: the `hidden` attribute is inert on SVG elements, so the icons are
  // swapped with a class on the button instead.
  function setPlayIcon(playing) {
    const btn = $('[data-action="playpause"]');
    if (!btn) return;
    btn.classList.toggle('is-playing', !!playing);
    btn.setAttribute('aria-label', playing ? 'Pause' : 'Play');
  }

  /* ══ player screen ════════════════════════════════════════ */

  function currentAmbience() {
    const on = $('.chip.is-on');
    return on ? on.dataset.amb : 'cafe';
  }

  async function openPlayer() {
    const name = state.profile.name ? state.profile.name.trim() : '';
    $('#player-title').textContent = name
      ? `A morning in ${name}'s life`
      : "A morning in the life you're building";

    const note = [];
    if (state.audioMode !== 'file') {
      note.push((state.server && state.server.tts_engine === 'gemini')
        ? 'Narrated by your browser — the studio voice was still rendering. Run api/prewarm.php before a demo and it plays instantly.'
        : 'Narrated by your browser — add an ElevenLabs or Gemini key in api/config.php for the studio voice.');
    }
    if (state.server && state.server.llm === 'none') note.push('Story written from the built-in template — add an LLM key to write it fresh each time.');
    $('#player-note').textContent = note.join(' ');

    await go('player');
  }

  function download() {
    const name = (state.profile.name || 'your').trim().replace(/[^\w\- ]/g, '');
    const blob = new Blob([state.story + '\n'], { type: 'text/plain;charset=utf-8' });
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = `${name || 'manifest'}-reading.txt`;
    document.body.appendChild(a); a.click(); a.remove();
    setTimeout(() => URL.revokeObjectURL(a.href), 4000);
    toast('Saved to your downloads.');
  }

  async function share() {
    const url = location.origin + location.pathname + '?p=' + Story.encodeProfile(state.profile) + '#/landing';
    try {
      await navigator.clipboard.writeText(url);
      toast('Share link copied — it reloads with these answers.');
    } catch {
      prompt('Copy this link:', url);
    }
  }

  /* ══ debug panel ══════════════════════════════════════════ */

  function syncDebug(result) {
    const json = $('#dbg-json');
    if (json) json.textContent = JSON.stringify(state.profile, null, 2);
    const st = $('#dbg-story');
    if (st && state.story) st.textContent = state.story;
    if (result) {
      const s = $('#dbg-status');
      if (s) s.textContent = `${result.source} · ${result.model || '—'} · ${result.words || Story.countWords(result.story)} words${result.note ? ' · ' + result.note : ''}`;
    }
  }

  function openDebug() {
    $('#debug').hidden = false;
    $('#dbg-voice').value = state.voice;
    syncDebug();
  }

  /* ══ wiring ═══════════════════════════════════════════════ */

  function bind() {
    document.addEventListener('click', async e => {
      const btn = e.target.closest('[data-action]');
      if (!btn) return;
      const action = btn.dataset.action;

      switch (action) {
        case 'start':      startQuiz(true); break;
        case 'demo': {
          state.profile = { ...Quiz.DEMO_PROFILE };
          state.index = Quiz.QUESTIONS.length - 1;
          saveProfile();
          // Play the pre-warmed reading if one has been generated — that's the
          // one that starts instantly, in the real voice.
          const warm = await Story.warmedReading();
          startReading(warm || undefined);
          break;
        }
        case 'quiz-exit':  go('landing'); break;
        case 'next':       next(); break;
        case 'prev':       prev(); break;
        case 'skip':       skip(); break;
        case 'to-player':  openPlayer(); break;
        case 'playpause':  Player.toggle(); setPlayIcon(Player.isPlaying()); break;
        case 'loop': {
          const on = Player.setLoop(btn.getAttribute('aria-pressed') !== 'true');
          btn.setAttribute('aria-pressed', on ? 'true' : 'false');
          toast(on ? 'Looping.' : 'Loop off.');
          break;
        }
        case 'reread':
          Player.seek(0);
          state.lit = -1;
          await beginReveal();
          break;
        case 'download':   download(); break;
        case 'share':      share(); break;
        case 'to-paywall': go('paywall'); break;
        case 'mock-pay':   toast('Mock screen — nothing was charged.'); break;
        case 'restart':
          Player.pause();
          state.story = ''; state.lines = []; state.audioUrl = null;
          startQuiz(true);
          break;
        case 'debug-close': $('#debug').hidden = true; break;
        case 'dbg-prefill':
          state.profile = { ...Quiz.DEMO_PROFILE };
          state.index = 0; saveProfile();
          toast("Erika's profile loaded.");
          if (state.screen === 'quiz') paintQuestion();
          break;
        case 'dbg-regen':
          $('#dbg-status').textContent = 'regenerating…';
          startReading();
          break;
        case 'dbg-cache':
          try { const r = await Story.clearCache(); toast(`Cleared ${r.cleared} cached file(s).`); }
          catch (err) { toast('Could not clear cache.'); }
          break;
        case 'dbg-prewarm': {
          const status = $('#dbg-status');
          status.textContent = 'pre-warming… this takes a minute or two, leave the tab open';
          toast('Pre-warming Erika’s reading — takes a minute or two.', 5000);
          try {
            const r = await Story.prewarm(Quiz.DEMO_PROFILE, state.voice);
            status.textContent = r.ok
              ? `warmed · ${r.words} words · ${r.engine} · ${r.seconds}s`
              : `warm failed · ${r.note || 'unknown'}`;
            toast(r.ok ? 'Ready — "Play Erika\'s reading" is instant now.' : 'Warm-up failed, see the panel.', 5000);
          } catch (err) {
            status.textContent = 'warm failed · ' + (err.message || err);
            toast('Warm-up failed.');
          }
          break;
        }
      }
    });

    // ambience chips
    $$('.chip').forEach(chip => {
      chip.addEventListener('click', async () => {
        $$('.chip').forEach(c => { c.classList.remove('is-on'); c.setAttribute('aria-checked', 'false'); });
        chip.classList.add('is-on'); chip.setAttribute('aria-checked', 'true');
        const how = await Player.setAmbience(chip.dataset.amb);
        if (how === 'synth') toast('Synthesised bed — drop an mp3 in audio/ambience/ for the real loop.', 3200);
      });
    });

    // ambience volume
    $('#amb-vol').addEventListener('input', e => Player.setAmbienceVolume(e.target.value / 100));

    // scrubbing
    const scrub = $('#scrub');
    const seekFromEvent = e => {
      const r = scrub.getBoundingClientRect();
      const x = (e.touches ? e.touches[0].clientX : e.clientX) - r.left;
      Player.seek((Math.max(0, Math.min(1, x / r.width))) * (Player.duration() || 0));
    };
    scrub.addEventListener('click', seekFromEvent);
    scrub.addEventListener('keydown', e => {
      const d = Player.duration() || 0;
      if (e.key === 'ArrowRight') { e.preventDefault(); Player.seek(Player.currentTime() + d * .05); }
      if (e.key === 'ArrowLeft')  { e.preventDefault(); Player.seek(Player.currentTime() - d * .05); }
    });

    // voice picker (debug)
    $('#dbg-voice').addEventListener('change', e => {
      state.voice = e.target.value;
      toast(`Voice set to ${state.voice}. Regenerate to hear it.`);
    });

    // keyboard: space toggles playback outside of typing
    document.addEventListener('keydown', e => {
      if (e.target.matches('input, textarea, select')) return;
      if (e.code === 'Space' && (state.screen === 'player' || state.screen === 'reveal')) {
        e.preventDefault(); Player.toggle(); setPlayIcon(Player.isPlaying());
      }
      if (e.key === 'Escape' && !$('#debug').hidden) $('#debug').hidden = true;
    });
  }

  /* ══ boot ═════════════════════════════════════════════════ */

  async function boot() {
    Anim.starfield($('#starfield'));
    bind();
    loadProfile();

    const params = new URLSearchParams(location.search);
    if (params.has('p')) {
      const shared = Story.decodeProfile(params.get('p'));
      if (shared) { state.profile = shared; saveProfile(); toast('Answers loaded from a shared link.'); }
    }
    if (params.get('debug') === '1') openDebug();
    if (params.get('demo') === '1' && !params.has('p')) state.profile = { ...Quiz.DEMO_PROFILE };

    const landing = $('#screen-landing');
    landing.hidden = false;
    Anim.inScreen(landing);
    if (!location.hash) history.replaceState({ screen: 'landing' }, '', '#/landing');

    // Ask the server what's actually configured — it decides whether we get
    // real mp3 ambience and whether the voice comes from ElevenLabs.
    state.server = await Story.status();
    if (state.server && state.server.ambience_files) Player.setAmbienceFiles(state.server.ambience_files);
    if (state.server && state.server.demo_tools === false) $('#debug').hidden = true;
    syncDebug();
  }

  document.addEventListener('DOMContentLoaded', boot);
})();
