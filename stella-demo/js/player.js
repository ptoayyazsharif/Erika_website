/* ============================================================
   player.js — Web Audio mixing.
   Voice and ambience are separate sources routed through their
   own GainNodes: ambience sits at 0.25 and loops, the voice sits
   at 1.0. If no ambience mp3s are installed, the beds are
   synthesised from filtered noise — the demo never ships silent.
   If no TTS key is configured, the browser reads it aloud.
   ============================================================ */

const Player = (() => {
  const voiceEl = document.getElementById('voice');
  const ambEl   = document.getElementById('ambience');

  let ctx = null, voiceSrc = null, ambSrc = null;
  let voiceGain = null, ambGain = null, master = null;

  let ambFiles   = { cafe:false, rain:false, waves:false };
  let ambName    = 'cafe';
  let ambVolume  = .25;
  let synth      = null;      // procedural bed, when no mp3
  let mode       = 'file';    // 'file' | 'speech'
  let speech     = null;      // { utter, words, startedAt, elapsed, playing }
  let looping    = false;
  let listeners  = [];
  let tick       = null;

  /* ---- graph ---------------------------------------------- */

  function ensureCtx() {
    if (ctx) return ctx;
    const AC = window.AudioContext || window.webkitAudioContext;
    if (!AC) return null;
    ctx = new AC();

    master    = ctx.createGain(); master.gain.value = 1;
    voiceGain = ctx.createGain(); voiceGain.gain.value = 1;
    ambGain   = ctx.createGain(); ambGain.gain.value = ambVolume;

    master.connect(ctx.destination);
    voiceGain.connect(master);
    ambGain.connect(master);

    try {
      voiceSrc = ctx.createMediaElementSource(voiceEl);
      voiceSrc.connect(voiceGain);
      ambSrc = ctx.createMediaElementSource(ambEl);
      ambSrc.connect(ambGain);
    } catch {
      // Safari can refuse a second source on the same element — the
      // elements still play, they just bypass the graph.
    }
    return ctx;
  }

  async function resume() {
    ensureCtx();
    if (ctx && ctx.state === 'suspended') { try { await ctx.resume(); } catch {} }
  }

  /* ---- procedural ambience -------------------------------- */

  function noiseBuffer(seconds, kind) {
    const len = Math.floor(ctx.sampleRate * seconds);
    const buf = ctx.createBuffer(1, len, ctx.sampleRate);
    const d = buf.getChannelData(0);
    if (kind === 'brown') {
      let last = 0;
      for (let i = 0; i < len; i++) {
        const white = Math.random() * 2 - 1;
        last = (last + .02 * white) / 1.02;
        d[i] = last * 3.5;
      }
    } else { // pink-ish
      let b0=0,b1=0,b2=0;
      for (let i = 0; i < len; i++) {
        const w = Math.random() * 2 - 1;
        b0 = .99765*b0 + w*.0990460;
        b1 = .96300*b1 + w*.2965164;
        b2 = .57000*b2 + w*1.0526913;
        d[i] = (b0 + b1 + b2 + w*.1848) * .22;
      }
    }
    return buf;
  }

  function stopSynth() {
    if (!synth) return;
    try { synth.stop(); } catch {}
    synth = null;
  }

  /** Build a bed out of noise + filters. Not a sample library —
   *  but at 25% under a voice it reads as café / rain / waves. */
  function buildSynth(name) {
    stopSynth();
    if (!ctx || name === 'none') return;

    const out = ctx.createGain();
    out.gain.value = 0;
    out.connect(ambGain);

    const nodes = [];
    const start = ctx.currentTime;

    const src = ctx.createBufferSource();
    src.buffer = noiseBuffer(4, name === 'waves' ? 'brown' : 'pink');
    src.loop = true;

    const filter = ctx.createBiquadFilter();
    const swell  = ctx.createGain();

    if (name === 'rain') {
      filter.type = 'bandpass'; filter.frequency.value = 1400; filter.Q.value = .6;
      swell.gain.value = .9;
      const hiss = ctx.createBiquadFilter();
      hiss.type = 'highpass'; hiss.frequency.value = 700;
      src.connect(hiss); hiss.connect(filter);
      nodes.push(hiss);
    } else if (name === 'waves') {
      filter.type = 'lowpass'; filter.frequency.value = 520; filter.Q.value = .3;
      swell.gain.value = .55;
      // slow tidal swell: ~9s in, ~9s out
      const lfo = ctx.createOscillator();
      const lfoGain = ctx.createGain();
      lfo.frequency.value = 1 / 11;
      lfoGain.gain.value = .42;
      lfo.connect(lfoGain); lfoGain.connect(swell.gain);
      lfo.start(start);
      nodes.push(lfo, lfoGain);
      src.connect(filter);
    } else { // cafe
      filter.type = 'lowpass'; filter.frequency.value = 820; filter.Q.value = .4;
      swell.gain.value = .8;
      const murmur = ctx.createBiquadFilter();
      murmur.type = 'peaking'; murmur.frequency.value = 320; murmur.gain.value = 6; murmur.Q.value = .8;
      src.connect(murmur); murmur.connect(filter);
      nodes.push(murmur);
    }

    filter.connect(swell); swell.connect(out);
    src.start(start);

    // café gets occasional cup-and-saucer pings
    let clinkTimer = null;
    if (name === 'cafe') {
      const clink = () => {
        if (!ctx) return;
        const o = ctx.createOscillator();
        const g = ctx.createGain();
        o.type = 'triangle';
        o.frequency.value = 900 + Math.random() * 1600;
        const t = ctx.currentTime;
        g.gain.setValueAtTime(0, t);
        g.gain.linearRampToValueAtTime(.05 + Math.random() * .05, t + .006);
        g.gain.exponentialRampToValueAtTime(.0001, t + .32);
        o.connect(g); g.connect(out);
        o.start(t); o.stop(t + .36);
        clinkTimer = setTimeout(clink, 2600 + Math.random() * 7000);
      };
      clinkTimer = setTimeout(clink, 1800 + Math.random() * 3000);
    }

    out.gain.linearRampToValueAtTime(1, start + 1.6);   // fade in, never snap

    synth = {
      stop() {
        clearTimeout(clinkTimer);
        const t = ctx.currentTime;
        try {
          out.gain.cancelScheduledValues(t);
          out.gain.setValueAtTime(out.gain.value, t);
          out.gain.linearRampToValueAtTime(0, t + .5);
        } catch {}
        setTimeout(() => {
          try { src.stop(); } catch {}
          [src, filter, swell, out, ...nodes].forEach(n => { try { n.disconnect(); } catch {} });
        }, 600);
      }
    };
  }

  /* ---- ambience API --------------------------------------- */

  async function setAmbience(name) {
    ambName = name;
    await resume();

    if (name === 'none') {
      stopSynth();
      ambEl.pause(); ambEl.removeAttribute('src'); ambEl.load();
      return 'none';
    }

    if (ambFiles[name]) {                       // real loop installed
      stopSynth();
      ambEl.src = `audio/ambience/${name}.mp3`;
      ambEl.loop = true;
      ambEl.volume = 1;                          // level is set by the GainNode
      try { await ambEl.play(); } catch {}
      return 'file';
    }

    ambEl.pause();
    buildSynth(name);                            // synthesised bed
    return 'synth';
  }

  function setAmbienceVolume(v) {
    ambVolume = Math.max(0, Math.min(1, v));
    if (ambGain) {
      const t = ctx.currentTime;
      ambGain.gain.cancelScheduledValues(t);
      ambGain.gain.setTargetAtTime(ambVolume, t, .08);
    }
  }

  function setAmbienceFiles(map) { ambFiles = Object.assign(ambFiles, map || {}); }

  /* ---- voice ---------------------------------------------- */

  /** url === null → browser speech synthesis. */
  async function loadVoice(url, text) {
    stopSpeech();
    if (url) {
      mode = 'file';
      voiceEl.src = url;
      voiceEl.loop = looping;
      await new Promise(res => {
        const done = () => { voiceEl.removeEventListener('loadedmetadata', done); res(); };
        voiceEl.addEventListener('loadedmetadata', done);
        voiceEl.addEventListener('error', done, { once: true });
        setTimeout(done, 6000);
      });
    } else {
      mode = 'speech';
      voiceEl.removeAttribute('src');
      const words = Story.countWords(text);
      speech = {
        text,
        words,
        duration: Math.max(30, words / 2.35),    // ~140 wpm, slow narration
        elapsed: 0,
        startedAt: 0,
        playing: false
      };
    }
    emit();
  }

  function speakNow(fromSeconds = 0) {
    if (!speech) return false;

    // Start the virtual clock first — the reveal paces off it whether or not
    // the browser can actually speak.
    speech.startedAt = performance.now() / 1000 - fromSeconds;
    speech.playing = true;
    clearTimeout(speech.endTimer);
    speech.endTimer = setTimeout(() => finishSpeech(), Math.max(0, (speech.duration - fromSeconds) * 1000));

    if (!('speechSynthesis' in window)) return false;   // silent, but still paced
    window.speechSynthesis.cancel();
    const u = new SpeechSynthesisUtterance(speech.text);
    const voices = window.speechSynthesis.getVoices();
    const preferred = voices.find(v => /Samantha|Serena|Ava|Google UK English Female|Zira|Female/i.test(v.name) && /en/i.test(v.lang))
                   || voices.find(v => /en-US|en-GB/i.test(v.lang));
    if (preferred) u.voice = preferred;
    u.rate = .88; u.pitch = 1.0; u.volume = 1;
    u.onend = () => finishSpeech();
    speech.utter = u;
    window.speechSynthesis.speak(u);
    return true;
  }

  function finishSpeech() {
    if (!speech) return;
    clearTimeout(speech.endTimer);
    speech.playing = false;
    speech.elapsed = speech.duration;
    emit();
    if (looping) { seek(0); play(); } else stopTick();
  }

  function stopSpeech() {
    if ('speechSynthesis' in window) window.speechSynthesis.cancel();
    if (speech) { clearTimeout(speech.endTimer); speech.playing = false; }
  }

  /* ---- transport ------------------------------------------ */

  /** Resolves false when the browser refused to start the audio, so the
   *  caller can prompt for the tap that unlocks it. */
  async function play() {
    await resume();
    let started = true;
    if (mode === 'file') {
      try { await voiceEl.play(); } catch { started = false; }
    } else if (speech) {
      speakNow(speech.elapsed);
    }
    startTick();
    emit();
    return started;
  }

  function pause() {
    if (mode === 'file') voiceEl.pause();
    else if (speech && speech.playing) {
      speech.elapsed = currentTime();
      stopSpeech();
    }
    stopTick();
    emit();
  }

  function toggle() { return isPlaying() ? (pause(), false) : (play(), true); }

  function isPlaying() {
    return mode === 'file' ? (!voiceEl.paused && !voiceEl.ended) : !!(speech && speech.playing);
  }

  function currentTime() {
    if (mode === 'file') return voiceEl.currentTime || 0;
    if (!speech) return 0;
    return speech.playing
      ? Math.min(speech.duration, performance.now() / 1000 - speech.startedAt)
      : speech.elapsed;
  }

  function duration() {
    if (mode === 'file') return isFinite(voiceEl.duration) ? voiceEl.duration : 0;
    return speech ? speech.duration : 0;
  }

  function seek(seconds) {
    const d = duration() || 0;
    const t = Math.max(0, Math.min(d, seconds));
    if (mode === 'file') voiceEl.currentTime = t;
    else if (speech) {
      speech.elapsed = t;
      if (speech.playing) speakNow(t);          // speech can't scrub; restart from here
    }
    emit();
  }

  function setLoop(on) {
    looping = !!on;
    voiceEl.loop = looping;
    return looping;
  }

  function isLooping() { return looping; }

  /* ---- progress broadcasting ------------------------------ */

  function onProgress(fn) { listeners.push(fn); return () => { listeners = listeners.filter(f => f !== fn); }; }

  function emit() {
    const state = { time: currentTime(), duration: duration(), playing: isPlaying(), mode };
    listeners.forEach(fn => { try { fn(state); } catch {} });
  }

  function startTick() { stopTick(); tick = setInterval(emit, 120); }
  function stopTick()  { clearInterval(tick); tick = null; }

  voiceEl.addEventListener('ended', () => {
    if (!looping) stopTick();
    emit();
  });
  voiceEl.addEventListener('play',  () => { startTick(); emit(); });
  voiceEl.addEventListener('pause', () => { emit(); });

  return {
    resume, loadVoice, play, pause, toggle, seek, setLoop, isLooping,
    isPlaying, currentTime, duration, onProgress,
    setAmbience, setAmbienceVolume, setAmbienceFiles,
    get mode() { return mode; }
  };
})();
