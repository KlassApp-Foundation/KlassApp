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

  function place() {
    var pill = document.getElementById('toshi-entry-pill');
    if (!pill) return;
    var lift = 0;
    var bar = document.querySelector('[data-people-bulk]');
    if (shown(bar)) {
      lift += Math.ceil(bar.getBoundingClientRect().height);
    }
    for (var n = 0; n < 6; n++) {
      pill.style.bottom = 'calc(24px + env(safe-area-inset-bottom) + ' + lift + 'px)';
      var box = pill.getBoundingClientRect();
      if (box.width < 2) return;
      var bump = 0;
      var nodes = document.querySelectorAll('a, button, [data-people-bulk]');
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
      lift += bump + 8;
    }
  }

  window.toshiPlaceEntryPill = place;

  function boot() {
    place();
    window.addEventListener('resize', place);
    document.addEventListener('toshi-collapsed-changed', place);
    var bars = document.querySelectorAll('[data-people-bulk]');
    if (window.MutationObserver) {
      bars.forEach(function (bar) {
        new MutationObserver(place).observe(bar, { attributes: true, attributeFilter: ['hidden', 'style', 'class'] });
      });
    }
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot);
  else boot();
})();
