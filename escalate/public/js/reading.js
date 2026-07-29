/* ============================================================================
   Escalate — the reading screen
   ----------------------------------------------------------------------------
   Three jobs: wait well, reveal the words, play the voice.

   The waiting matters as much as the result. Generation takes fifteen to
   twenty-five seconds — far too long for a spinner — so the screen rotates
   through lines that describe what is actually happening and fills a bar that
   never quite reaches the end. Polling backs off as it goes so a slow queue
   does not turn into a request storm.

   No secrets pass through here. The audio arrives as a route, not a file path;
   the client never learns which provider, model or voice id produced it.
   ========================================================================= */

(() => {
  'use strict';

  const root = document.querySelector('[data-reading]');
  if (!root) return;

  const $  = s => root.querySelector(s);
  const g  = window.gsap;
  const reduced = matchMedia('(prefers-reduced-motion: reduce)').matches;
  const toast = window.Escalate?.toast ?? (() => {});

  const phases = {
    waiting: $('[data-phase="waiting"]'),
    ready:   $('[data-phase="ready"]'),
    failed:  $('[data-phase="failed"]'),
  };

  const WAITING_LINES = [
    'Listening for the names you gave me…',
    'Finding the ordinary morning inside it…',
    'Setting the table, pouring the coffee…',
    'Putting the numbers where you can see them…',
    'Writing it in present tense…',
    'Looking for the thing you used to do…',
    'Almost. This part shouldn’t be rushed…',
  ];

  /* ── waiting theatre ───────────────────────────────────────────────────── */

  let lineTimer, lineIndex = 0;

  function startWaiting() {
    const line = $('[data-waiting-line]');
    const bar = $('[data-waiting-bar]');
    if (!line) return;

    // 92% over 20s: the bar shows progress without ever promising completion,
    // because we genuinely do not know when the model will finish.
    if (bar) {
      if (g && !reduced) g.fromTo(bar, { width: '4%' }, { width: '92%', duration: 20, ease: 'power1.out' });
      else bar.style.width = '92%';
    }

    lineTimer = setInterval(() => {
      lineIndex = (lineIndex + 1) % WAITING_LINES.length;
      const next = WAITING_LINES[lineIndex];

      if (g && !reduced) {
        g.to(line, { opacity: 0, y: -10, duration: 0.4, onComplete: () => {
          line.textContent = next;
          g.fromTo(line, { opacity: 0, y: 12 }, { opacity: 1, y: 0, duration: 0.7, ease: 'expo.out' });
        }});
      } else {
        line.textContent = next;
      }
    }, 2800);
  }

  function stopWaiting() {
    clearInterval(lineTimer);
    const bar = $('[data-waiting-bar]');
    if (bar && g && !reduced) g.to(bar, { width: '100%', duration: 0.3 });
  }

  function show(phase) {
    Object.entries(phases).forEach(([name, el]) => {
      if (el) el.hidden = name !== phase;
    });
  }

  /* ── polling ───────────────────────────────────────────────────────────
     Interval grows from 1.5s to 6s. A story that is going to arrive arrives
     early; one that is stuck should not be asked about twenty times a minute. */

  let delay = 1500;
  let polls = 0;

  async function poll() {
    if (++polls > 90) {
      toast('This is taking longer than it should. Reload to check again.', 8000);
      return;
    }

    let data;
    try {
      const res = await fetch(root.dataset.stateUrl, {
        headers: { Accept: 'application/json' },
        cache: 'no-store',
      });
      if (!res.ok) throw new Error(String(res.status));
      data = await res.json();
    } catch {
      // A dropped connection is not a failed story. Keep waiting, slower.
      delay = Math.min(delay * 1.6, 8000);
      return setTimeout(poll, delay);
    }

    if (data.state === 'ready') {
      stopWaiting();
      paintStory(data);
      if (data.audio?.url) attachAudio(data.audio);
      else if (data.audio?.state === 'queued' || data.audio?.state === 'rendering') {
        setTimeout(poll, 2500);
      }
      return;
    }

    if (data.state === 'failed') {
      stopWaiting();
      const el = $('[data-failure]');
      if (el && data.reason) el.textContent = data.reason;
      show('failed');
      return;
    }

    delay = Math.min(delay * 1.25, 6000);
    setTimeout(poll, delay);
  }

  /* ── the reveal ────────────────────────────────────────────────────────
     Paragraphs arrive at once but are lit one at a time. Nothing about this is
     load-bearing for comprehension — it is pacing, so a reading feels read
     rather than dumped. */

  function paintStory(data) {
    const box = $('[data-lines]');
    if (box && Array.isArray(data.paragraphs) && data.paragraphs.length) {
      box.innerHTML = '';
      data.paragraphs.forEach(text => {
        const p = document.createElement('p');
        p.textContent = text;
        box.appendChild(p);
      });
    }

    const count = $('[data-word-count]');
    if (count && data.words) count.textContent = data.words;

    show('ready');

    if (g && !reduced && box) {
      g.from(box.children, {
        opacity: 0, y: 14, duration: 0.7, stagger: 0.28, ease: 'expo.out', clearProps: 'all',
      });
    }
  }

  /* ── the player ────────────────────────────────────────────────────────── */

  let audio = null;
  let sleepTimer = null;

  function attachAudio(info) {
    const player = $('[data-player]');
    const narrateBlock = $('[data-narrate-block]');
    if (!player) return;

    player.hidden = false;
    if (narrateBlock) narrateBlock.hidden = true;

    audio = new Audio(info.url);
    audio.preload = 'metadata';

    const playBtn  = $('[data-play]');
    const iconPlay = $('[data-icon-play]');
    const iconPause = $('[data-icon-pause]');
    const bar = $('[data-audio-bar]');
    const elapsed = $('[data-elapsed]');
    const duration = $('[data-duration]');
    const loopBtn = $('[data-loop]');

    const fmt = s => {
      s = Math.max(0, Math.floor(s || 0));
      return `${Math.floor(s / 60)}:${String(s % 60).padStart(2, '0')}`;
    };

    const paintPlaying = () => {
      const playing = !audio.paused && !audio.ended;
      if (iconPlay)  iconPlay.hidden = playing;
      if (iconPause) iconPause.hidden = !playing;
      playBtn?.setAttribute('aria-label', playing ? 'Pause' : 'Play');
    };

    audio.addEventListener('loadedmetadata', () => {
      if (duration && Number.isFinite(audio.duration)) duration.textContent = fmt(audio.duration);
    });

    audio.addEventListener('timeupdate', () => {
      if (!Number.isFinite(audio.duration) || !audio.duration) return;
      if (bar) bar.style.width = `${(audio.currentTime / audio.duration) * 100}%`;
      if (elapsed) elapsed.textContent = fmt(audio.currentTime);
    });

    audio.addEventListener('play', paintPlaying);
    audio.addEventListener('pause', paintPlaying);
    audio.addEventListener('ended', paintPlaying);

    audio.addEventListener('error', () => {
      toast('The narration would not load. Reload the page to try again.', 6000);
    });

    let counted = false;
    audio.addEventListener('play', () => {
      if (counted) return;
      counted = true;
      // Fire and forget: a failed count must never interrupt listening.
      fetch(root.dataset.playedUrl, {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
          Accept: 'application/json',
        },
      }).catch(() => {});
    });

    playBtn?.addEventListener('click', () => {
      if (audio.paused) {
        audio.play().catch(() => toast('Tap once more to start the voice.'));
      } else {
        audio.pause();
      }
    });

    loopBtn?.addEventListener('click', () => {
      const on = loopBtn.getAttribute('aria-pressed') !== 'true';
      loopBtn.setAttribute('aria-pressed', String(on));
      loopBtn.classList.toggle('is-on', on);
      audio.loop = on;
      toast(on ? 'Looping until you stop it.' : 'Loop off.');
    });

    // Sleep timer. Fades out over the last eight seconds rather than cutting,
    // because being snapped out of a reading defeats the point of it.
    root.querySelectorAll('[data-sleep]').forEach(btn => {
      btn.addEventListener('click', () => {
        const minutes = Number(btn.dataset.sleep);
        root.querySelectorAll('[data-sleep]').forEach(b => b.classList.toggle('is-on', b === btn));
        clearTimeout(sleepTimer);
        audio.volume = 1;

        const note = $('[data-sleep-note]');
        if (!minutes) {
          if (note) note.hidden = true;
          return;
        }

        if (note) {
          note.hidden = false;
          note.textContent = `Fading out in ${minutes} minutes.`;
        }

        sleepTimer = setTimeout(() => {
          const step = () => {
            audio.volume = Math.max(0, audio.volume - 0.04);
            if (audio.volume > 0.001) return setTimeout(step, 320);
            audio.pause();
            audio.volume = 1;
            if (note) note.textContent = 'Slept.';
          };
          step();
        }, minutes * 60_000);
      });
    });

    paintPlaying();
  }

  /* ── boot ──────────────────────────────────────────────────────────────── */

  const state = root.dataset.state;

  if (state === 'queued' || state === 'writing') {
    show('waiting');
    startWaiting();
    setTimeout(poll, 1200);
  } else if (state === 'ready') {
    show('ready');
    if (root.dataset.audioUrl) {
      attachAudio({ url: root.dataset.audioUrl });
    } else if (root.dataset.audioState === 'queued' || root.dataset.audioState === 'rendering') {
      setTimeout(poll, 2500);
    }
  }
})();
