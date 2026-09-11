const observer = new IntersectionObserver((entries) => {
  entries.forEach(entry => { if (entry.isIntersecting) entry.target.classList.add('visible'); });
}, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });
document.querySelectorAll('.reveal').forEach(el => observer.observe(el));
const navbar = document.getElementById('navbar');
window.addEventListener('scroll', () => { navbar.classList.toggle('scrolled', window.scrollY > 10); }, { passive: true });

/* Hero chat sequence: typing indicator → message appears */
(function initChatSequence() {
  const messages = document.querySelectorAll('#phone-messages .msg');
  const typing = document.getElementById('typing-indicator');
  if (!messages.length || !typing) return;

  const delays = { in: 1200, out: 800 };
  let idx = 0;
  let loopTimer = null;

  function showTyping(isIncoming) {
    typing.classList.add('active');
    typing.style.alignSelf = isIncoming ? 'flex-start' : 'flex-end';
  }
  function hideTyping() { typing.classList.remove('active'); }

  function showNext() {
    if (idx >= messages.length) {
      hideTyping();
      loopTimer = setTimeout(() => {
        loopTimer = null;
        idx = 0;
        messages.forEach(m => m.classList.remove('msg-visible'));
        setTimeout(showNext, 400);
      }, 3500);
      return;
    }
    const msg = messages[idx];
    const isIncoming = msg.classList.contains('msg-in');
    showTyping(isIncoming);
    setTimeout(() => {
      hideTyping();
      msg.classList.add('msg-visible');
      idx++;
      setTimeout(showNext, isIncoming ? delays.in : delays.out);
    }, isIncoming ? 900 : 500);
  }

  const phoneObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting && idx === 0 && !loopTimer) {
        setTimeout(showNext, 600);
      }
    });
  }, { threshold: 0.3 });
  phoneObserver.observe(document.querySelector('.phone-frame'));
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

/* Toshi diagram connectors — measured from the live DOM.
   The nodes are flex-laid-out, so hardcoded SVG coordinates drift out of
   alignment at any width but the authored one. This welds each connector to
   its node's real position and re-runs on resize. */
(function () {
  const visual = document.querySelector('.toshi-visual');
  if (!visual) return;
  const inner = visual.querySelector('.toshi-visual-inner');
  const svg = visual.querySelector('.toshi-visual-lines svg');
  const core = visual.querySelector('.toshi-visual-core');
  const label = visual.querySelector('.toshi-visual-label');
  if (!inner || !svg || !core || !label) return;

  const channels = Array.from(visual.querySelectorAll('.toshi-visual-channel'));
  const roles = Array.from(visual.querySelectorAll('.toshi-visual-role'));
  const baseIn = Array.from(svg.querySelectorAll('.t-base-in path'));
  const baseOut = Array.from(svg.querySelectorAll('.t-base-out path'));
  const flowIn = Array.from(svg.querySelectorAll('.t-flow-in path'));
  const flowOut = Array.from(svg.querySelectorAll('.t-flow-out path'));
  const packets = Array.from(svg.querySelectorAll('.toshi-particle'));

  const r1 = (n) => Math.round(n * 10) / 10;
  /* Inbound: leaves the channel vertically, arrives at the hub laterally so it
     meets the circle tangentially. */
  const curve = (sx, sy, ex, ey) =>
    'M' + r1(sx) + ',' + r1(sy) +
    ' C' + r1(sx) + ',' + r1(sy + (ey - sy) * 0.5) +
    ' ' + r1(sx + (ex - sx) * 0.5) + ',' + r1(ey) +
    ' ' + r1(ex) + ',' + r1(ey);
  /* Outbound: symmetric S — drops out of the hub and lands vertically on the
     role pill, so the three read as a fan rather than grazing the pill tops. */
  const curveS = (sx, sy, ex, ey) =>
    'M' + r1(sx) + ',' + r1(sy) +
    ' C' + r1(sx) + ',' + r1(sy + (ey - sy) * 0.55) +
    ' ' + r1(ex) + ',' + r1(ey - (ey - sy) * 0.55) +
    ' ' + r1(ex) + ',' + r1(ey);

  function assign(path, d) {
    if (!path) return;
    path.setAttribute('d', d);
  }
  function assignFlow(path, d) {
    if (!path) return;
    path.setAttribute('d', d);
    let len = 300;
    try { len = path.getTotalLength() || 300; } catch (e) { /* detached */ }
    path.style.setProperty('--flow-len', r1(len) + 'px');
  }

  function layout() {
    const box = inner.getBoundingClientRect();
    if (!box.width || !box.height) return;
    svg.setAttribute('viewBox', '0 0 ' + r1(box.width) + ' ' + r1(box.height));

    const rel = (el) => {
      const r = el.getBoundingClientRect();
      return {
        top: r.top - box.top,
        bottom: r.bottom - box.top,
        cx: r.left - box.left + r.width / 2,
        cy: r.top - box.top + r.height / 2,
        w: r.width,
      };
    };

    /* A wrapped channel row can't be served by a radial fan — bail to the
       node-only composition instead of drawing lines behind the cards. */
    const rowTops = new Set(channels.map((n) => Math.round(n.getBoundingClientRect().top)));
    visual.classList.toggle('t-wrapped', rowTops.size > 1);
    if (rowTops.size > 1) { return; }

    const c = rel(core);
    const stopRadius = c.w / 2 + 14;

    channels.forEach((node, i) => {
      const n = rel(node);
      const sx = n.cx;
      const sy = n.bottom + 4;
      const angle = Math.atan2(sy - c.cy, sx - c.cx);
      const ex = c.cx + Math.cos(angle) * stopRadius;
      const ey = c.cy + Math.sin(angle) * stopRadius;
      const d = curve(sx, sy, ex, ey);
      assign(baseIn[i], d);
      assignFlow(flowIn[i], d);
      if (packets[i]) { packets[i].style.offsetPath = 'path("' + d + '")'; }
    });

    /* Fan the role connectors from below the Control Center pill so they
       never cross the label. */
    const lb = rel(label);
    const ox = c.cx;
    const oy = lb.bottom + 6;
    roles.forEach((node, j) => {
      const n = rel(node);
      const d = curveS(ox, oy, n.cx, n.top - 4);
      assign(baseOut[j], d);
      assignFlow(flowOut[j], d);
    });
  }

  layout();
  window.addEventListener('load', layout);
  window.addEventListener('resize', layout);
  if (document.fonts && document.fonts.ready) { document.fonts.ready.then(layout); }
})();
