(function() {
    'use strict';
    function init() {
        document.querySelectorAll('[data-events-list]').forEach(function(root) {
            if (root.dataset.initialized === 'Y') return;
            root.dataset.initialized = 'Y';
            const items = Array.from(root.querySelectorAll('.event-item'));
            const checkboxes = Array.from(root.querySelectorAll('.filter-section-checkbox'));
            const all = root.querySelector('.filter-all-checkbox');
            const counter = root.querySelector('.events-count');
            const empty = root.querySelector('.events-filter-empty');
            if (!all) return;

            function filter() {
                const selected = checkboxes.filter(cb => cb.checked).map(cb => cb.value);
                let count = 0;
                items.forEach(function(item) {
                    item.hidden = !all.checked && !selected.includes(item.dataset.sectionId);
                    if (!item.hidden) count++;
                });
                counter.textContent = 'Показано: ' + count + ' из ' + items.length;
                empty.hidden = count > 0;
            }
            checkboxes.forEach(function(checkbox) {
                checkbox.addEventListener('change', function() {
                    const selected = checkboxes.filter(cb => cb.checked).length;
                    all.checked = selected === checkboxes.length;
                    all.indeterminate = selected > 0 && !all.checked;
                    filter();
                });
            });
            all.addEventListener('change', function() {
                checkboxes.forEach(cb => { cb.checked = all.checked; });
                all.indeterminate = false;
                filter();
            });
            filter();
        });
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
    else init();
})();
