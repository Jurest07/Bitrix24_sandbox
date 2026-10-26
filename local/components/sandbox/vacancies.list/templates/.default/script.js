(function() {
    'use strict';

    BX.ready(function() {

        // ==================================================
        // 1. СЛАЙДЕР ДЛЯ КАРТОЧЕК ВАКАНСИЙ
        // ==================================================
        const sliderButtons = document.querySelectorAll('.vacancy-card');

        sliderButtons.forEach(function(button) {
            button.addEventListener('click', function(e) {
                e.preventDefault();

                const vacancyId = this.dataset.vacancyId;
                const url = '/local/pages/vacancies/detail.php?ID=' + vacancyId;

                BX.SidePanel.Instance.open(url, {
                    width: 700,
                    cacheable: false,
                    allowChangeHistory: true
                });
            });
        });

        // ==================================================
        // 2. ФИЛЬТРЫ-ЧИПСЫ
        // ==================================================

        /**
         * Инициализация одного фильтра
         */
        function initFilterChips(chips) {
            const trigger = chips.querySelector('.filter-chips__trigger');
            const checkboxes = chips.querySelectorAll('.filter-chips__options input[type="checkbox"]');

            if (!trigger) {
                return;
            }

            // Клик по кнопке — открыть/закрыть
            trigger.addEventListener('click', function(e) {
                e.stopPropagation();

                const isOpen = chips.classList.contains('is-open');

                closeAllFilterChips();

                if (!isOpen) {
                    chips.classList.add('is-open');
                }
            });

            // Изменение чекбокса — перерисовать
            checkboxes.forEach(function(cb) {
                cb.addEventListener('change', function() {
                    renderChipsState(chips);
                });
            });

            // Изначальная перерисовка (восстановление чипсов из checked)
            renderChipsState(chips);
        }

        /**
         * Перерисовать состояние фильтра
         */
        function renderChipsState(chips) {
            const filterName = chips.dataset.filterName;  // CITY или EMPLOYMENT_TYPE
            const selectedBox = chips.querySelector('.filter-chips__selected');
            const hiddenBox = chips.querySelector('.filter-chips__hidden');
            const badge = chips.querySelector('.filter-chips__badge');
            const checkboxes = chips.querySelectorAll('.filter-chips__options input[type="checkbox"]');

            if (!selectedBox || !hiddenBox || !badge || !filterName) {
                return;
            }

            // Очищаем
            selectedBox.innerHTML = '';
            hiddenBox.innerHTML = '';

            let count = 0;

            checkboxes.forEach(function(cb) {
                if (!cb.checked) {
                    return;
                }

                count++;

                const value = cb.value;
                const label = cb.nextElementSibling
                    ? cb.nextElementSibling.textContent.trim()
                    : value;

                // 1. Скрытый input для отправки формы
                const hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = filterName + '[]';
                hidden.value = value;
                hiddenBox.appendChild(hidden);

                // 2. Чипс
                const chip = document.createElement('span');
                chip.className = 'chip';
                chip.dataset.value = value;

                const chipText = document.createElement('span');
                chipText.className = 'chip__text';
                chipText.textContent = label;
                chip.appendChild(chipText);

                const removeBtn = document.createElement('button');
                removeBtn.type = 'button';
                removeBtn.className = 'chip__remove';
                removeBtn.setAttribute('aria-label', 'Удалить');
                removeBtn.textContent = '×';
                chip.appendChild(removeBtn);

                // Обработчик крестика
                removeBtn.addEventListener('click', function(e) {
                    e.stopPropagation();

                    const targetValue = chip.dataset.value;
                    const targetCb = chips.querySelector(
                        '.filter-chips__options input[value="' + targetValue + '"]'
                    );

                    if (targetCb) {
                        targetCb.checked = false;
                        targetCb.dispatchEvent(new Event('change'));
                    }
                });

                selectedBox.appendChild(chip);
            });

            // Badge
            if (count > 0) {
                badge.textContent = count;
                badge.style.display = '';
            } else {
                badge.textContent = '';
                badge.style.display = 'none';
            }
        }

        /**
         * Закрыть все открытые фильтры
         */
        function closeAllFilterChips() {
            document.querySelectorAll('.filter-chips.is-open').forEach(function(chips) {
                chips.classList.remove('is-open');
            });
        }

        // Инициализация всех фильтров
        document.querySelectorAll('.filter-chips').forEach(function(chips) {
            initFilterChips(chips);
        });

        // Клик вне фильтров — закрыть все
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.filter-chips')) {
                closeAllFilterChips();
            }
        });

    });

})();
