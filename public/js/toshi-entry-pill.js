(function () {
  function shown(el) {
    if (!el || el.hidden) return false;
    var cs = getComputedStyle(el);
    if (cs.display === 'none' || cs.visibility === 'hidden') return false;
    var r = el.getBoundingClientRect();
    return r.width >= 2 && r.height >= 2;
  }

  function wrapper() {
    return document.getElementById('toshi-toggle-wrapper');
  }

  function pillWanted() {
    if (!document.body.classList.contains('toshi-collapsed')) return false;
    if (window.innerWidth < 1280) return false;
    var wrap = wrapper();
    if (!wrap) return false;
    // The pill shows only on the dashboard and onboarding screens (set by the
    // server). Everywhere else the side handle alone is the launcher, so the
    // pill never covers list rows.
    if (wrap.dataset.pillPage !== '1') return false;
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
    watchScroll();
    compact();
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
    pill.style.right = 'calc(24px + env(safe-area-inset-right))';
    pill.style.bottom = 'calc(' + bottom + 'px + env(safe-area-inset-bottom))';
  }

  // Compact after 80px of scroll. At 1280+ the document does not scroll: the
  // body is overflow:hidden and .dashboard-content-area is the scroller, and
  // its scroll events never reach window. An IntersectionObserver watches a
  // zero-height sentinel 80px below the top of whichever element really
  // scrolls, so there is no scroll handler and no forced layout.
  var COMPACT_AFTER = 80;
  var scrolledPast = false;
  var observer = null;
  var observedRoot;

  function compact() {
    var pill = document.getElementById('toshi-entry-pill');
    if (!pill) return;
    pill.classList.toggle('is-compact', scrolledPast);
  }

  function scrollRoot() {
    var area = document.querySelector('.dashboard-content-area');
    if (area) {
      var oy = getComputedStyle(area).overflowY;
      if (oy === 'auto' || oy === 'scroll') return area;
    }
    return null; // the document scrolls
  }

  function sentinel(root) {
    var el = document.getElementById('toshi-pill-sentinel');
    if (!el) {
      el = document.createElement('div');
      el.id = 'toshi-pill-sentinel';
      el.setAttribute('aria-hidden', 'true');
      el.style.cssText = 'height:0;width:1px;margin:0;padding:0;border:0;pointer-events:none;visibility:hidden;';
    }
    if (root) {
      // Static, zero-height first child, nudged down with a relative offset:
      // it scrolls with the content and changes no layout.
      el.style.position = 'relative';
      el.style.top = COMPACT_AFTER + 'px';
      el.style.left = '0';
      if (root.firstChild !== el) root.insertBefore(el, root.firstChild);
    } else {
      el.style.position = 'absolute';
      el.style.top = COMPACT_AFTER + 'px';
      el.style.left = '0';
      if (el.parentNode !== document.body) document.body.insertBefore(el, document.body.firstChild);
    }
    return el;
  }

  function watchScroll() {
    if (!('IntersectionObserver' in window)) return fallbackScroll();
    var root = scrollRoot();
    var current = document.getElementById('toshi-pill-sentinel');
    if (observer && observedRoot === root && current && current.isConnected) return;
    if (observer) observer.disconnect();
    observedRoot = root;
    var target = sentinel(root);
    observer = new IntersectionObserver(function (entries) {
      var entry = entries[entries.length - 1];
      if (!target.isConnected) {
        // The content area was re-rendered (Vue/Livewire): watch it again.
        observedRoot = undefined;
        window.requestAnimationFrame(watchScroll);
        return;
      }
      var top = entry.rootBounds ? entry.rootBounds.top : 0;
      scrolledPast = !entry.isIntersecting && entry.boundingClientRect.top < top;
      compact();
    }, { root: root, threshold: 0 });
    observer.observe(target);
  }

  // Browsers without IntersectionObserver: one capture listener catches the
  // scroll of any element; the read waits for the next animation frame.
  var fallbackOn = false;
  function fallbackScroll() {
    if (fallbackOn) return;
    fallbackOn = true;
    var queued = false;
    var source = null;
    document.addEventListener('scroll', function (e) {
      source = e.target;
      if (queued) return;
      queued = true;
      window.requestAnimationFrame(function () {
        queued = false;
        var el = (source && source.nodeType === 1) ? source : (document.scrollingElement || document.documentElement);
        scrolledPast = el.scrollTop > COMPACT_AFTER;
        compact();
      });
    }, { capture: true, passive: true });
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
