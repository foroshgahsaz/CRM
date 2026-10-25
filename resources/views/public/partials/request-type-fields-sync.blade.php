<script>
    (function () {
        const typeSelect = document.getElementById(@json($selectId));
        if (!typeSelect) {
            return;
        }
        const groups = document.querySelectorAll('[data-type-fields]');
        const syncFields = () => {
            groups.forEach((group) => {
                const active = group.dataset.typeFields === typeSelect.value;
                group.hidden = !active;
                group.querySelectorAll('input, select, textarea').forEach((input) => {
                    input.disabled = !active;
                });
            });
            if (typeof jalaliDatepicker !== 'undefined') {
                jalaliDatepicker.startWatch({ time: false });
            }
        };
        typeSelect.addEventListener('change', syncFields);
        syncFields();
    })();
</script>
