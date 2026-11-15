/* Progressive report rules screen: live example for a mark and an aggregate, computed from what is
   typed in the tables, before anything is saved. The same arithmetic runs on the server for the
   "Show" buttons (GradingRulesKit), so this only makes the result appear as you type. */
(function () {
    var markInput = document.querySelector('[data-testid="try-mark"]');
    var aggInput = document.querySelector('[data-testid="try-aggregate"]');
    var markOut = document.querySelector('[data-testid="try-mark-result"]');
    var aggOut = document.querySelector('[data-testid="try-aggregate-result"]');

    function whole(value) {
        return /^-?\d+$/.test(String(value).trim()) ? parseInt(value, 10) : null;
    }

    function bands() {
        var rows = document.querySelectorAll('[data-testid="grade-bands"] tbody tr');
        return Array.prototype.map.call(rows, function (row) {
            var get = function (name) { var el = row.querySelector('[name$="[' + name + ']"]'); return el ? el.value : ''; };
            return { grade: get('grade'), points: whole(get('points')), min: whole(get('min_score')), max: whole(get('max_score')) };
        });
    }

    function ranges() {
        var rows = document.querySelectorAll('[data-testid="division-ranges"] tbody tr');
        return Array.prototype.map.call(rows, function (row) {
            var get = function (name) { var el = row.querySelector('[name$="[' + name + ']"]'); return el ? el.value : ''; };
            return { label: get('label').trim(), min: whole(get('min')), max: whole(get('max')) };
        });
    }

    function showMark() {
        if (!markInput || !markOut) { return; }
        var mark = whole(markInput.value);
        if (mark === null) { markOut.textContent = ''; return; }
        if (mark < 0 || mark > 100) { markOut.textContent = mark + ' is not in any band. Marks run from 0 to 100.'; return; }
        var hit = bands().filter(function (b) { return b.min !== null && b.max !== null && mark >= b.min && mark <= b.max; })[0];
        if (!hit) { markOut.textContent = mark + ' is not in any band. Marks run from 0 to 100.'; return; }
        markOut.textContent = mark + ' is grade ' + hit.grade + (hit.points === null ? '.' : ', ' + hit.points + (hit.points === 1 ? ' point.' : ' points.'));
    }

    function showAggregate() {
        if (!aggInput || !aggOut) { return; }
        var agg = whole(aggInput.value);
        if (agg === null) { aggOut.textContent = ''; return; }
        var automatic = document.querySelector('[data-testid="automatic-division"]');
        var list = ranges().filter(function (r) { return r.label !== ''; });
        var hit = list.filter(function (r) {
            return (r.min === null || agg >= r.min) && (r.max === null || agg <= r.max);
        })[0];
        aggOut.textContent = hit
            ? 'Aggregate ' + agg + ' is division ' + hit.label + '.'
            : 'Aggregate ' + agg + ' is not in any division range.';
    }

    document.addEventListener('input', function (event) {
        if (event.target.closest('[data-testid="grade-bands"], [data-testid="division-ranges"], [data-testid="try-mark"], [data-testid="try-aggregate"]')) {
            showMark();
            showAggregate();
        }
    });
    document.addEventListener('submit', function (event) {
        var form = event.target;
        if (form.matches && form.matches('[data-testid="try-mark-form"], [data-testid="try-aggregate-form"]')) {
            event.preventDefault(); // the result is already shown from what is on screen
            showMark();
            showAggregate();
        }
    });
})();
