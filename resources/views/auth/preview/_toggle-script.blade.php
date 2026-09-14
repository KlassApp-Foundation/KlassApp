{{-- SPDX-License-Identifier: MIT --}}
<script>
(function () {
  'use strict';
  document.querySelectorAll('[data-ap-toggle]').forEach(function (btn) {
    btn.addEventListener('click', function (e) {
      e.preventDefault();
      var input = document.getElementById(btn.getAttribute('data-ap-toggle'));
      if (!input) return;
      input.type = input.type === 'password' ? 'text' : 'password';
    });
  });
})();
</script>
