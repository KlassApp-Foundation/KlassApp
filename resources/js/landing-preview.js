const observer = new IntersectionObserver((entries) => {
  entries.forEach(entry => { if (entry.isIntersecting) entry.target.classList.add('visible'); });
}, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });
document.querySelectorAll('.reveal').forEach(el => observer.observe(el));
const navbar = document.getElementById('navbar');
window.addEventListener('scroll', () => { navbar.classList.toggle('scrolled', window.scrollY > 10); }, { passive: true });

/* Mobile nav — toggle was previously a dead button (no panel, no handler). */
(function initMobileNav() {
  const toggle = document.getElementById('navbarMobileToggle');
  const panel = document.getElementById('navbarMobilePanel');
  if (!toggle || !panel) return;

  function setOpen(open) {
    panel.hidden = !open;
    panel.classList.toggle('is-open', open);
    toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    toggle.classList.toggle('is-open', open);
    document.body.classList.toggle('nav-open', open);
  }

  toggle.addEventListener('click', () => {
    setOpen(panel.hidden);
  });

  panel.querySelectorAll('a').forEach((link) => {
    link.addEventListener('click', () => setOpen(false));
  });

  window.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && !panel.hidden) setOpen(false);
  });
})();

/* Hero chat sequence replaced by rotating role preview */
(function initHeroRoleRotate() {
  const deck = document.getElementById('heroRoleDeck');
  const dots = document.getElementById('heroRoleDots');
  if (!deck || !dots) return;

  const cards = Array.from(deck.querySelectorAll('.hero-role-card'));
  if (cards.length < 2) return;

  const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  let i = 0;
  let timer = null;

  function setActive(next) {
    const prev = cards[i];
    if (prev && prev !== cards[next]) {
      prev.classList.remove('is-active');
      prev.setAttribute('aria-hidden', 'true');
      if (!reduceMotion) {
        prev.classList.add('is-exit');
        /* Flip duration 0.3s + enter delay 0.15s ≈ 450ms; clear exit class after. */
        setTimeout(() => prev.classList.remove('is-exit'), 450);
      }
    }
    i = next;
    cards.forEach((card, idx) => {
      const on = idx === i;
      card.classList.toggle('is-active', on);
      card.setAttribute('aria-hidden', on ? 'false' : 'true');
    });
    Array.from(dots.children).forEach((d, di) => {
      d.classList.toggle('on', di === i);
      d.setAttribute('aria-selected', di === i ? 'true' : 'false');
    });
  }

  cards.forEach((card, idx) => {
    const b = document.createElement('button');
    b.type = 'button';
    b.setAttribute('role', 'tab');
    b.setAttribute('aria-label', card.getAttribute('aria-label') || ('Show preview ' + (idx + 1)));
    if (idx === 0) b.classList.add('on');
    b.addEventListener('click', () => {
      setActive(idx);
      restart();
    });
    dots.appendChild(b);
  });

  function tick() {
    setActive((i + 1) % cards.length);
  }
  function restart() {
    clearInterval(timer);
    timer = null;
    if (!reduceMotion) {
      timer = setInterval(tick, 3200);
    }
  }

  if (reduceMotion) {
    setActive(0);
    return;
  }

  const stage = document.getElementById('heroRoleStage') || deck;
  const io = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        restart();
      } else {
        clearInterval(timer);
        timer = null;
      }
    });
  }, { threshold: 0.25 });
  io.observe(stage);
})();

/* Toshi tower v2: scale the fixed 1120x600 stage to its container (>=760px only;
   below that the CSS stacks it), and cycle one model mark at a time. */
(function initToshiTower() {
  const fit = document.getElementById('toshiTowerFit');
  const models = document.getElementById('toshiTowerModels');
  if (!fit || !models) return;

  const wide = window.matchMedia('(min-width: 760px)');
  function size() {
    if (!wide.matches) { fit.style.removeProperty('--tv-s'); return; }
    const w = fit.clientWidth;
    if (w > 0) fit.style.setProperty('--tv-s', String(w / 1120));
  }
  new ResizeObserver(size).observe(fit);
  wide.addEventListener('change', size);
  size();

  const tiles = Array.from(models.querySelectorAll('.tv-mt'));
  const rm = window.matchMedia('(prefers-reduced-motion: reduce)');
  let i = 0;
  let timer = null;
  /* Reduced motion: no cycling, first two marks (one per face) shown statically. */
  function show() {
    tiles.forEach((t, k) => t.classList.toggle('on', rm.matches ? k < 2 : k === i));
  }
  function run() {
    clearInterval(timer);
    timer = null;
    show();
    if (!rm.matches) {
      timer = setInterval(() => { i = (i + 1) % tiles.length; show(); }, 3000);
    }
  }
  rm.addEventListener('change', run);
  run();
})();

/* How it works: sequential step reveal */
(function initHowFlow() {
  const flow = document.getElementById('how-flow');
  if (!flow) return;
  const steps = flow.querySelectorAll('.how-step');
  let activated = false;

  const flowObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting && !activated) {
        activated = true;
        flow.classList.add('active');
        steps.forEach(step => {
          const delay = parseInt(step.dataset.delay || '0', 10);
          setTimeout(() => step.classList.add('visible'), delay);
        });
      }
    });
  }, { threshold: 0.2 });
  flowObserver.observe(flow);
})();

/* Announcement bar — dismissible, remembered across reloads. */
(function initAnnounce() {
  const bar = document.getElementById('announceBar');
  const btn = document.getElementById('announceClose');
  if (!bar || !btn) return;
  try { if (localStorage.getItem('ka-announce-dismissed') === '1') { bar.hidden = true; return; } } catch (e) { /* storage blocked */ }
  btn.addEventListener('click', () => {
    bar.hidden = true;
    try { localStorage.setItem('ka-announce-dismissed', '1'); } catch (e) { /* storage blocked */ }
  });
})();
