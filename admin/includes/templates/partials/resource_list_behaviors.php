<script>
document.addEventListener('click', function (event) {
    var interactiveTarget = event.target.closest('a, button, form, input, select, textarea, label');
    if (interactiveTarget) {
        return;
    }

    var row = event.target.closest('tr.js-resource-row-link');
    if (!row) {
        return;
    }

    var destination = row.getAttribute('data-row-link');
    if (destination) {
        window.location.href = destination;
    }
});

document.querySelectorAll('form.js-resource-search-form[data-lookahead="true"]').forEach(function (form) {
    var input = form.querySelector('.js-resource-search-input');
    if (!input) {
        return;
    }

    var minLength = parseInt(form.getAttribute('data-lookahead-min') || '3', 10);
    var delay = parseInt(form.getAttribute('data-lookahead-delay') || '350', 10);
    var resetHref = form.getAttribute('data-reset-href') || '';
    var initialValue = (input.value || '').trim();
    var debounceTimer = null;

    if (initialValue.length >= minLength) {
        window.setTimeout(function () {
            input.focus();
            if (typeof input.setSelectionRange === 'function') {
                input.setSelectionRange(input.value.length, input.value.length);
            }
        }, 0);
    }

    input.addEventListener('input', function () {
        var value = (input.value || '').trim();
        window.clearTimeout(debounceTimer);

        if (value.length === 0) {
            if (initialValue === '' || resetHref === '') {
                return;
            }

            debounceTimer = window.setTimeout(function () {
                window.location.href = resetHref;
            }, delay);
            return;
        }

        if (value.length < minLength) {
            return;
        }

        debounceTimer = window.setTimeout(function () {
            form.submit();
        }, delay);
    });
});

document.querySelectorAll('select.js-resource-filter-select').forEach(function (select) {
    select.addEventListener('change', function () {
        if (select.form) {
            select.form.submit();
        }
    });
});
</script>
