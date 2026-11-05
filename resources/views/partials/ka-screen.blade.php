{{-- Demo screen chrome from docs/design/2026-11-demo (pages 1-3 and FilterSheet).
     The rules live in public/css/ka-screen.css, loaded by layouts.app: a <style>
     element inside the content is dropped once the page shell hydrates. --}}
<script>
document.addEventListener('click', function (event) {
    var open = event.target.closest('[data-ka-open-filters]');
    if (open) {
        var root = document.getElementById(open.getAttribute('data-ka-open-filters'));
        if (!root) return;
        root.querySelector('.ka-filter-panel').classList.add('is-open');
        root.querySelector('.ka-filter-backdrop').classList.add('is-open');
    }
    if (event.target.closest('[data-ka-close-filters]')) {
        document.querySelectorAll('.ka-filter-panel.is-open, .ka-filter-backdrop.is-open').forEach(function (node) {
            node.classList.remove('is-open');
        });
    }
    var ask = event.target.closest('[data-ka-ask-remove]');
    if (ask) {
        var dialog = document.getElementById(ask.getAttribute('data-ka-ask-remove'));
        if (dialog && dialog.showModal) dialog.showModal();
    }
});
</script>
