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
  const url = document.getElementById('heroDeviceUrl');
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
    /* Address bar in the desktop device frame shows the active card's tool. */
    if (url && cards[i].dataset.host) url.textContent = cards[i].dataset.host;
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

/* How it works: Toshi tower v2. Scales the fixed 1120x600 stage at >=760px (the CSS
   stacks the nodes below that), swaps the cube's two screens on every landing, and
   throws signal packets from the core flash point to random nodes and back. */
(function initAgentCore() {
  const fit = document.getElementById('agentCoreFit');
  const stage = document.getElementById('agentCoreStage');
  if (!fit || !stage) return;

  const wide = window.matchMedia('(min-width: 760px)');
  function size() {
    if (!wide.matches) { fit.style.removeProperty('--ac-s'); return; }
    const w = fit.clientWidth;
    if (w > 0) fit.style.setProperty('--ac-s', String(w / 1120));
  }
  new ResizeObserver(size).observe(fit);
  wide.addEventListener('change', size);
  size();

  const models = [...document.querySelectorAll('.ac-models img')].map((i) => i.getAttribute('src'));
  const L = document.getElementById('ac-scrL');
  const R = document.getElementById('ac-scrR');
  const B = stage.querySelector('svg .ac-bounce');
  const G = document.getElementById('ac-sig');
  const SH = document.getElementById('ac-shock');
  const NS = 'http://www.w3.org/2000/svg';
  if (!models.length || !L || !R || !B || !G || !SH) return;

  let k = 0;
  const rm = window.matchMedia('(prefers-reduced-motion: reduce)');

  /* Both screens change together, working through 3 model pairs. */
  function swap() {
    k = (k + 1) % 3;
    [L, R].forEach((e) => e.classList.add('ac-off'));
    setTimeout(() => {
      L.setAttribute('href', models[2 * k]);
      R.setAttribute('href', models[2 * k + 1]);
      [L, R].forEach((e) => e.classList.remove('ac-off'));
    }, 250);
  }

  const nodes = [...stage.querySelectorAll('.ac-node')];
  const col = (n) => getComputedStyle(n).getPropertyValue('--node-color').trim() || '#1E6FD9';
  const ctr = (n) => [n.offsetLeft + n.offsetWidth / 2, n.offsetTop + n.offsetHeight / 2];

  function shot(from, to, color, done) {
    const g = document.createElementNS(NS, 'g');
    g.innerHTML = '<circle r="9" fill="' + color + '" opacity=".22"/><circle r="4" fill="' + color + '"/>';
    G.appendChild(g);
    const mx = (from[0] + to[0]) / 2;
    const my = Math.min(from[1], to[1]) - 40;
    g.animate([
      { transform: 'translate(' + from[0] + 'px,' + from[1] + 'px)', opacity: 0 },
      { transform: 'translate(' + mx + 'px,' + my + 'px)', opacity: 1, offset: .45 },
      { transform: 'translate(' + to[0] + 'px,' + to[1] + 'px)', opacity: 1, offset: .92 },
      { transform: 'translate(' + to[0] + 'px,' + to[1] + 'px)', opacity: 0 },
    ], { duration: 950, easing: 'cubic-bezier(.3,.6,.4,1)' }).onfinish = () => {
      g.remove();
      done && done();
    };
  }
  const pick = (n) => nodes.slice().sort(() => Math.random() - .5).slice(0, n);

  /* On every landing: shockwave, 4 packets out to random nodes, then 2 back in. */
  function land() {
    if (rm.matches) return;
    swap();
    SH.animate([
      { transform: 'scale(.6)', opacity: .7 },
      { transform: 'scale(1.5)', opacity: 0 },
    ], { duration: 700, easing: 'ease-out' });
    if (!wide.matches) return; /* stacked layout: signal coordinates are stage-based */
    pick(4).forEach((n, i) => setTimeout(() => shot([560, 390], ctr(n), col(n), () => {
      n.classList.remove('hit');
      void n.offsetWidth;
      n.classList.add('hit');
    }), i * 90));
    setTimeout(() => pick(2).forEach((n, i) => setTimeout(() => shot(ctr(n), [560, 390], col(n)), i * 140)), 1400);
  }

  SH.style.transformBox = 'fill-box';
  SH.style.transformOrigin = 'center';
  B.addEventListener('animationiteration', land);
})();
