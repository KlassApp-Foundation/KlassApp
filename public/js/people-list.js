(function () {
    var root = document.querySelector('[data-people-list]');
    if (!root) {
        return;
    }

    var form = root.querySelector('[data-people-search-form]');
    var search = root.querySelector('[data-people-search]');
    var timer = null;
    if (search && form) {
        search.addEventListener('input', function () {
            window.clearTimeout(timer);
            timer = window.setTimeout(function () {
                var loading = root.querySelector('[data-people-loading]');
                if (loading) {
                    loading.hidden = false;
                }
                if (window.matchMedia('(prefers-reduced-motion: reduce)').matches && loading) {
                    loading.querySelectorAll('.people-skel').forEach(function (node) {
                        node.style.animation = 'none';
                    });
                }
                form.requestSubmit ? form.requestSubmit() : form.submit();
            }, 250);
        });
    }

    var bulk = root.querySelector('[data-people-bulk]');
    var count = root.querySelector('[data-people-count]');
    function visibleBoxes() {
        return Array.prototype.filter.call(root.querySelectorAll('[data-people-check]'), function (box) {
            return box.offsetParent !== null;
        });
    }
    function paint() {
        var boxes = visibleBoxes().filter(function (box) { return box.checked; });
        if (count) {
            count.textContent = String(boxes.length);
        }
        if (bulk) {
            bulk.hidden = boxes.length === 0;
        }
    }
    root.addEventListener('change', function (event) {
        var target = event.target;
        if (target && target.hasAttribute('data-people-all')) {
            visibleBoxes().forEach(function (box) {
                box.checked = target.checked;
            });
        }
        paint();
    });
    var clear = root.querySelector('[data-people-clear]');
    if (clear) {
        clear.addEventListener('click', function () {
            root.querySelectorAll('[data-people-check], [data-people-all]').forEach(function (box) {
                box.checked = false;
            });
            paint();
        });
    }

    function closeMenus(except) {
        root.querySelectorAll('[data-people-menu]').forEach(function (button) {
            var pop = button.parentElement.querySelector('[role="menu"]');
            if (pop && pop !== except) {
                pop.hidden = true;
                button.setAttribute('aria-expanded', 'false');
            }
        });
    }
    root.addEventListener('click', function (event) {
        var button = event.target.closest('[data-people-menu]');
        if (!button) {
            if (!event.target.closest('[role="menu"]')) {
                closeMenus(null);
            }
            return;
        }
        var pop = button.parentElement.querySelector('[role="menu"]');
        var open = pop.hidden;
        closeMenus(pop);
        pop.hidden = !open;
        button.setAttribute('aria-expanded', open ? 'true' : 'false');
        if (open) {
            var first = pop.querySelector('[role="menuitem"]');
            if (first) {
                first.focus();
            }
        }
    });
    root.addEventListener('keydown', function (event) {
        var pop = event.target.closest('[role="menu"]');
        if (!pop) {
            var button = event.target.closest('[data-people-menu]');
            if (button && (event.key === 'Enter' || event.key === ' ')) {
                event.preventDefault();
                button.click();
            }
            return;
        }
        var items = Array.prototype.slice.call(pop.querySelectorAll('[role="menuitem"]'));
        var index = items.indexOf(document.activeElement);
        if (event.key === 'ArrowDown') {
            event.preventDefault();
            items[(index + 1) % items.length].focus();
        } else if (event.key === 'ArrowUp') {
            event.preventDefault();
            items[(index - 1 + items.length) % items.length].focus();
        } else if (event.key === 'Escape') {
            var trigger = pop.parentElement.querySelector('[data-people-menu]');
            pop.hidden = true;
            if (trigger) {
                trigger.setAttribute('aria-expanded', 'false');
                trigger.focus();
            }
        }
    });
})();
