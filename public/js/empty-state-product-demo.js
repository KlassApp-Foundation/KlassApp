/**
 * Empty-state product demo — 3-scene auto-loop.
 * Respects prefers-reduced-motion (static fallback).
 */
(function () {
  'use strict';

  function initDemo(root) {
    if (!root || root.dataset.esDemoReady === '1') {
      return;
    }
    root.dataset.esDemoReady = '1';

    var stage = root.querySelector('[data-es-demo-stage]');
    var staticEl = root.querySelector('.es-demo-static');
    var scenes = Array.prototype.slice.call(root.querySelectorAll('.es-demo-scene'));
    var dots = Array.prototype.slice.call(root.querySelectorAll('[data-dot]'));
    var reduce =
      window.matchMedia &&
      window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    if (reduce || !stage || scenes.length === 0) {
      if (stage) {
        stage.hidden = true;
      }
      if (staticEl) {
        staticEl.hidden = false;
      }
      root.classList.add('es-demo--reduced');
      root.setAttribute('data-testid-reduced', 'true');
      return;
    }

    if (staticEl) {
      staticEl.hidden = true;
    }

    var sceneIndex = 0;
    var stepTimers = [];
    var sceneTimer = null;
    var STEP_MS = 700;
    var HOLD_MS = 2200;

    function clearStepTimers() {
      stepTimers.forEach(function (id) {
        clearTimeout(id);
      });
      stepTimers = [];
    }

    function setScene(index) {
      clearStepTimers();
      sceneIndex = index % scenes.length;
      scenes.forEach(function (scene, i) {
        var active = i === sceneIndex;
        scene.classList.toggle('is-active', active);
        scene.setAttribute('aria-hidden', active ? 'false' : 'true');
        scene.querySelectorAll('.es-msg').forEach(function (el) {
          el.classList.remove('is-shown');
        });
      });
      dots.forEach(function (dot) {
        dot.classList.toggle(
          'is-active',
          String(dot.getAttribute('data-dot')) === String(sceneIndex)
        );
      });

      var active = scenes[sceneIndex];
      var msgs = Array.prototype.slice.call(active.querySelectorAll('.es-msg'));
      msgs.sort(function (a, b) {
        return (
          Number(a.getAttribute('data-step') || 0) -
          Number(b.getAttribute('data-step') || 0)
        );
      });
      msgs.forEach(function (el, i) {
        stepTimers.push(
          setTimeout(function () {
            el.classList.add('is-shown');
          }, STEP_MS * (i + 1))
        );
      });

      var total = STEP_MS * (msgs.length + 1) + HOLD_MS;
      if (sceneTimer) {
        clearTimeout(sceneTimer);
      }
      sceneTimer = setTimeout(function () {
        setScene(sceneIndex + 1);
      }, total);
    }

    setScene(0);
  }

  function boot() {
    document.querySelectorAll('[data-es-demo]').forEach(initDemo);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }
})();
