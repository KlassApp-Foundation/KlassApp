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
    var hint = root.querySelector('[data-people-hint]');
    var more = root.querySelector('[data-people-bulk-more]');
    var count = root.querySelector('[data-people-count]');
    function visibleBoxes() {
        return Array.prototype.filter.call(root.querySelectorAll('[data-people-check]'), function (box) {
            return box.offsetParent !== null;
        });
    }
    var selectButton = root.querySelector('[data-people-select]');
    function selecting() {
        return !!(selectButton && selectButton.getAttribute('aria-pressed') === 'true');
    }
    function setSelecting(on) {
        if (!selectButton) {
            return;
        }
        selectButton.setAttribute('aria-pressed', on ? 'true' : 'false');
        root.classList.toggle('is-selecting', on);
        root.querySelectorAll('[data-people-check-col]').forEach(function (col) {
            col.hidden = !on;
        });
        if (!on) {
            root.querySelectorAll('[data-people-check], [data-people-all]').forEach(function (box) {
                box.checked = false;
            });
        }
        paint();
    }
    function paint() {
        var boxes = visibleBoxes().filter(function (box) { return box.checked; });
        if (count) {
            count.textContent = String(boxes.length);
        }
        // The bar shows from 1 selected; at 0 selected Select mode shows the hint line instead.
        if (bulk) {
            bulk.hidden = !selecting() || boxes.length === 0;
        }
        if (hint) {
            hint.hidden = !selecting() || boxes.length > 0;
        }
        if (more && bulk && bulk.hidden) {
            more.open = false;
        }
        root.querySelectorAll('.people-row, .people-card').forEach(function (row) {
            var box = row.querySelector('[data-people-check]');
            row.classList.toggle('is-selected', !!(box && box.checked && selecting()));
        });
    }
    function toggleRow(row) {
        var box = row && row.querySelector('[data-people-check]');
        if (!box) {
            return;
        }
        box.checked = !box.checked;
        paint();
    }
    if (selectButton) {
        selectButton.addEventListener('click', function () {
            setSelecting(!selecting());
        });
    }
    root.querySelectorAll('[data-people-done]').forEach(function (done) {
        done.addEventListener('click', function () {
            if (more) {
                more.open = false;
            }
            setSelecting(false);
        });
    });
    root.addEventListener('change', function (event) {
        var target = event.target;
        if (target && target.hasAttribute('data-people-all')) {
            visibleBoxes().forEach(function (box) {
                box.checked = target.checked;
            });
        }
        paint();
    });
    root.querySelectorAll('[data-people-clear]').forEach(function (clear) {
        clear.addEventListener('click', function () {
            if (more) {
                more.open = false;
            }
            root.querySelectorAll('[data-people-check], [data-people-all]').forEach(function (box) {
                box.checked = false;
            });
            paint();
        });
    });

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
        if (selecting() && !event.target.closest('.people-menu-wrap, .people-check-hit, .people-bulk, [data-people-select]')) {
            var row = event.target.closest('.people-row, .people-card');
            if (row) {
                event.preventDefault();
                toggleRow(row);
                return;
            }
        }
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
            if (event.key === 'Escape' && selecting()) {
                event.preventDefault();
                setSelecting(false);
                if (selectButton) {
                    selectButton.focus();
                }
                return;
            }
            var name = event.target.closest('a.people-name');
            if (name && event.key === 'Enter') {
                event.preventDefault();
                if (selecting()) {
                    toggleRow(name.closest('.people-row, .people-card'));
                    return;
                }
                window.location.assign(name.href);
                return;
            }
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
