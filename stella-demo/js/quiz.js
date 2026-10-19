/* ============================================================
   quiz.js — the fifteen questions.
   Every question exists because it fills a slot in the story.
   Nothing here is decorative; if a slot is empty the story goes flat.
   ============================================================ */

const Quiz = (() => {

  const QUESTIONS = [
    /* ── Setting ─────────────────────────────────────────── */
    { key:'name', group:'Setting', type:'text',
      title:'First, what should I call you?',
      help:'Your first name. It goes in the story exactly as you write it.',
      placeholder:'Erika', required:true, max:40 },

    { key:'city', group:'Setting', type:'text',
      title:'Which city are you in — or moving to?',
      help:'Where this life happens day to day.',
      placeholder:'Atlanta', required:true, max:60 },

    { key:'spot', group:'Setting', type:'text',
      title:'And a spot there you love.',
      help:'A neighbourhood, a restaurant, a walk. Somewhere specific enough that only you would name it.',
      placeholder:'a little wine shop in Buckhead', max:120 },

    /* ── People ──────────────────────────────────────────── */
    { key:'partner', group:'People', type:'text',
      title:'Who is closest to you?',
      help:'A name, or a nickname only you use. Leave it blank if you would rather not.',
      placeholder:'C Arnez B', optional:true, max:60 },

    { key:'people', group:'People', type:'textarea',
      title:'Who else is beside you in this life?',
      help:'Names and how you know them — family, friends, your people. Written however you would say it out loud.',
      placeholder:'my daughter Maya, and Tyler and Liam', optional:true, max:220 },

    /* ── The dream ───────────────────────────────────────── */
    { key:'desire', group:'The dream', type:'textarea',
      title:"What are you manifesting right now?",
      help:'The real one. In your own words, not the polished version.',
      placeholder:'financial freedom and a thriving real estate business',
      required:true, max:260 },

    { key:'area', group:'The dream', type:'choice',
      title:'Which part of life is that?',
      help:'This sets the tone of the reading.',
      options:[
        { value:'money',   label:'Money',   note:'ease, margin' },
        { value:'career',  label:'Career',  note:'craft, standing' },
        { value:'love',    label:'Love',    note:'closeness' },
        { value:'home',    label:'Home',    note:'rooms, roots' },
        { value:'freedom', label:'Freedom', note:'time, space' },
        { value:'health',  label:'Health',  note:'the body' }
      ], required:true },

    { key:'milestone', group:'The dream', type:'text',
      title:'What is the thing that means "I made it"?',
      help:'A specific purchase or milestone. Specific beats grand.',
      placeholder:'a beachfront villa in Costa Rica', required:true, max:140 },

    { key:'milestone_location', group:'The dream', type:'text',
      title:'And where in the world is it?',
      help:'The place that goes with the last answer.',
      placeholder:'Costa Rica', max:80 },

    { key:'proof_number', group:'The dream', type:'text',
      title:'Give me a number that would prove it is working.',
      help:'Counted success feels true. Vague abundance feels fake.',
      placeholder:'47 agents signed this quarter', required:true, max:120 },

    { key:'work_vision', group:'The dream', type:'textarea',
      title:'What does your work look like at its best?',
      help:'The shape of the days — what you are actually building.',
      placeholder:'an AI recruiting arm and a real estate acquisitions business',
      max:260 },

    /* ── Sensory anchors ─────────────────────────────────── */
    { key:'success_place', group:'Sensory anchors', type:'text',
      title:'Where are you when you feel it?',
      help:'The actual room or place. Be plain about it.',
      placeholder:'my home office, laptop open', max:140 },

    { key:'sensory_detail', group:'Sensory anchors', type:'text',
      title:'One small detail from that place.',
      help:'A smell, a sound, an object. The ordinariness is what makes it real.',
      placeholder:'a framed photo from last Thanksgiving', max:140 },

    { key:'good_news_caller', group:'Sensory anchors', type:'textarea',
      title:'Who calls with good news — and what do they say?',
      help:'One line. The kind of message that lands mid-morning.',
      placeholder:'my assistant, telling me the new deal video hit 50k views',
      max:220 },

    /* ── The contrast (the secret sauce) ─────────────────── */
    { key:'scarcity_habit', group:'The contrast', type:'textarea',
      title:"Last one. When you want something you can't have yet, what do you do right now?",
      help:'The small scarcity habit — the tab-comparing, the balance-checking. This is the line the whole reading turns on, so be honest.',
      placeholder:'open three tabs comparing prices before I buy anything',
      required:true, max:260 }
  ];

  /* Pre-filled so the very first run in front of Erika is perfect.
     Never let a live demo start with cold typing. */
  const DEMO_PROFILE = {
    name:'Erika',
    city:'Atlanta',
    spot:'a little wine shop in Buckhead',
    partner:'C Arnez B',
    people:'my daughter Maya, and Tyler and Liam',
    desire:'financial freedom and a thriving real estate business',
    area:'money',
    milestone:'a beachfront villa in Costa Rica',
    milestone_location:'Costa Rica',
    proof_number:'47 agents signed this quarter',
    work_vision:'an AI recruiting arm and a real estate acquisitions business',
    success_place:'my home office, laptop open',
    sensory_detail:'a framed photo from last Thanksgiving',
    good_news_caller:'my assistant, telling me the new deal video hit 50k views',
    scarcity_habit:'open three tabs comparing prices before I buy anything'
  };

  /* ---- rendering ------------------------------------------ */

  function render(index, value, handlers) {
    const q = QUESTIONS[index];
    const node = document.createElement('div');
    node.className = 'q-card';
    node.dataset.key = q.key;

    const num = String(index + 1).padStart(2, '0');
    node.innerHTML = `
      <span class="q-num">${num} <span aria-hidden="true">—</span> ${q.group}</span>
      <h2 class="q-title">${q.title}</h2>
      ${q.help ? `<p class="q-help">${q.help}</p>` : ''}
      <div class="q-field"></div>
      <p class="q-error" role="alert"></p>`;

    const field = node.querySelector('.q-field');

    if (q.type === 'choice') {
      const box = document.createElement('div');
      box.className = 'q-options';
      box.setAttribute('role', 'radiogroup');
      box.setAttribute('aria-label', q.title);
      q.options.forEach(opt => {
        const b = document.createElement('button');
        b.type = 'button';
        b.className = 'q-option' + (value === opt.value ? ' is-on' : '');
        b.setAttribute('role', 'radio');
        b.setAttribute('aria-checked', value === opt.value ? 'true' : 'false');
        b.dataset.value = opt.value;
        b.innerHTML = `${opt.label}${opt.note ? `<small>${opt.note}</small>` : ''}`;
        b.addEventListener('click', () => {
          box.querySelectorAll('.q-option').forEach(o => {
            o.classList.remove('is-on');
            o.setAttribute('aria-checked', 'false');
          });
          b.classList.add('is-on');
          b.setAttribute('aria-checked', 'true');
          handlers.onChange(opt.value);
          handlers.onAdvance();          // choices advance themselves
        });
        box.appendChild(b);
      });
      field.appendChild(box);
    } else {
      const input = document.createElement(q.type === 'textarea' ? 'textarea' : 'input');
      input.className = q.type === 'textarea' ? 'q-textarea' : 'q-input';
      if (q.type !== 'textarea') input.type = 'text';
      input.placeholder = q.placeholder || '';
      input.value = value || '';
      input.maxLength = q.max || 200;
      input.setAttribute('aria-label', q.title.replace(/<[^>]*>/g, ''));
      input.autocomplete = 'off';
      input.spellcheck = false;
      input.addEventListener('input', () => handlers.onChange(input.value));
      input.addEventListener('keydown', e => {
        const submit = e.key === 'Enter' && (q.type !== 'textarea' || !e.shiftKey);
        if (submit) { e.preventDefault(); handlers.onAdvance(); }
      });
      field.appendChild(input);
    }

    return node;
  }

  function focusField(node) {
    const el = node && node.querySelector('input, textarea, .q-option');
    if (el) el.focus({ preventScroll: true });
  }

  /** A required answer left blank gets a nudge, never a wall. */
  function validate(index, value) {
    const q = QUESTIONS[index];
    if (!q.required) return null;
    if (!value || !String(value).trim()) {
      return q.type === 'choice'
        ? 'Pick the one that fits closest.'
        : 'This one shapes the whole reading — a few words is plenty.';
    }
    return null;
  }

  /** Sensible stand-ins so a skipped answer never leaves a hole in the story. */
  const DEFAULTS = {
    spot:'the coffee place I always end up at',
    partner:'',
    people:'',
    milestone_location:'',
    work_vision:'work that finally looks like me',
    success_place:'my kitchen, early, before anyone else is up',
    sensory_detail:'the light coming across the counter',
    good_news_caller:'someone on my team, with news'
  };

  return { QUESTIONS, DEMO_PROFILE, DEFAULTS, render, focusField, validate };
})();
