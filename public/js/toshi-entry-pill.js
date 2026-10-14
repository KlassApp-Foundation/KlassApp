(function () {
  function shown(el) {
    if (!el || el.hidden) return false;
    var cs = getComputedStyle(el);
    if (cs.display === 'none' || cs.visibility === 'hidden') return false;
    var r = el.getBoundingClientRect();
    return r.width >= 2 && r.height >= 2;
  }

  function intersects(a, b) {
    return !(a.right <= b.left || a.left >= b.right || a.bottom <= b.top || a.top >= b.bottom);
  }

  function wrapper() {
    return document.getElementById('toshi-toggle-wrapper');
  }

  function pillWanted() {
    if (!document.body.classList.contains('toshi-collapsed')) return false;
    if (window.innerWidth < 1280) return false;
    var wrap = wrapper();
    if (!wrap) return false;
    var mode = wrap.dataset.toshiMode || '';
    var done = parseInt(wrap.dataset.setupDone || '0', 10);
    var total = parseInt(wrap.dataset.setupTotal || '0', 10);
    if (mode === 'assistant') return true;
    if (mode === 'preview') {
      try { return localStorage.getItem('toshi_opened_' + wrap.dataset.toshiUser) !== '1'; }
      catch (e) { return true; }
    }
    return mode === 'onboarding' && total > done;
  }

  window.toshiMarkPreviewSeen = function () {
    var wrap = wrapper();
    if (!wrap || wrap.dataset.toshiMode !== 'preview') return;
    try { localStorage.setItem('toshi_opened_' + wrap.dataset.toshiUser, '1'); } catch (e) {}
  };

  function sync() {
    var pill = document.getElementById('toshi-entry-pill');
    var handle = document.getElementById('toshi-toggle');
    var wanted = pillWanted();
    document.body.classList.toggle('toshi-pill-on', wanted);
    if (pill) {
      if (wanted) pill.removeAttribute('hidden');
      else pill.setAttribute('hidden', '');
    }
    if (handle) {
      var collapsed = document.body.classList.contains('toshi-collapsed');
      handle.tabIndex = (wanted || !collapsed || window.innerWidth < 1280) ? -1 : 0;
    }
    place();
  }

  window.toshiSyncLauncher = sync;

  function place() {
    var pill = document.getElementById('toshi-entry-pill');
    if (!pill || pill.hidden) return;
    var bottom = 24;
    var bars = document.querySelectorAll('[data-sticky-bar]');
    for (var b = 0; b < bars.length; b++) {
      if (!shown(bars[b])) continue;
      var bar = bars[b].getBoundingClientRect();
      if (bar.bottom < window.innerHeight - 4) continue;
      bottom = Math.max(bottom, Math.ceil(window.innerHeight - bar.top) + 16);
    }
    for (var n = 0; n < 6; n++) {
      pill.style.bottom = 'calc(' + bottom + 'px + env(safe-area-inset-bottom))';
      var box = pill.getBoundingClientRect();
      if (box.width < 2) return;
      var bump = 0;
      var nodes = document.querySelectorAll('a, button, [data-sticky-bar]');
      for (var i = 0; i < nodes.length; i++) {
        var el = nodes[i];
        if (el === pill || pill.contains(el) || el.id === 'toshi-toggle') continue;
        if (el.closest('#toshi-toggle-wrapper, [data-toshi-root]')) continue;
        if (!shown(el)) continue;
        var r = el.getBoundingClientRect();
        if (!intersects(box, r)) continue;
        bump = Math.max(bump, Math.ceil(box.bottom - r.top));
      }
      if (bump <= 0) return;
      bottom += bump + 8;
    }
  }

  window.toshiPlaceEntryPill = place;

  function boot() {
    sync();
    window.addEventListener('resize', sync);
    document.addEventListener('toshi-collapsed-changed', sync);
    var bars = document.querySelectorAll('[data-sticky-bar]');
    if (window.MutationObserver) {
      bars.forEach(function (bar) {
        new MutationObserver(sync).observe(bar, { attributes: true, attributeFilter: ['hidden', 'style', 'class'] });
      });
    }
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot);
  else boot();
  window.addEventListener('load', sync);
})();
