<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

// Получаем выбранные фильтры (массивы)
$selectedCities = $arParams['FILTER_CITY'] ?? [];
$selectedEmployment = $arParams['FILTER_EMPLOYMENT_TYPE'] ?? [];
?>

<div class="vacancies-list">
    <h2 class="vacancies-list__title">Открытые вакансии</h2>

    <form method="GET" action="" class="vacancies-filter">

        <!-- ===== ФИЛЬТР: ГОРОД ===== -->
        <div class="filter-chips" data-filter-name="CITY">
            <button type="button" class="filter-chips__trigger">
                <span class="filter-chips__label">Город</span>
                <span class="filter-chips__badge" style="display: none;"></span>
                <svg class="filter-chips__arrow" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="6 9 12 15 18 9"></polyline>
                </svg>
            </button>

            <div class="filter-chips__panel">
                <div class="filter-chips__selected"></div>

                <div class="filter-chips__options">
                    <?php foreach ($arResult['FILTER_OPTIONS']['CITY'] as $Id => $name): ?>
                        <label class="filter-option">
                            <input type="checkbox"
                                   value="<?= htmlspecialcharsbx($Id) ?>"
                                    <?= in_array($Id, $selectedCities) ? 'checked' : '' ?>>
                            <span><?= htmlspecialcharsbx($name) ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="filter-chips__hidden"></div>
        </div>

        <!-- ===== ФИЛЬТР: ТИП ЗАНЯТОСТИ ===== -->
        <div class="filter-chips" data-filter-name="EMPLOYMENT_TYPE">
            <button type="button" class="filter-chips__trigger">
                <span class="filter-chips__label">Тип занятости</span>
                <span class="filter-chips__badge" style="display: none;"></span>
                <svg class="filter-chips__arrow" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="6 9 12 15 18 9"></polyline>
                </svg>
            </button>

            <div class="filter-chips__panel">
                <div class="filter-chips__selected"></div>

                <div class="filter-chips__options">
                    <?php foreach ($arResult['FILTER_OPTIONS']['EMPLOYMENT_TYPE'] as $xmlId => $name): ?>
                        <label class="filter-option">
                            <input type="checkbox"
                                   value="<?= htmlspecialcharsbx($xmlId) ?>"
                                    <?= in_array($xmlId, $selectedEmployment) ? 'checked' : '' ?>>
                            <span><?= htmlspecialcharsbx($name) ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="filter-chips__hidden"></div>
        </div>

        <div class="filter-select">
            <label for="filter-experience">Опыт:</label>
            <select name="EXPERIENCE" id="filter-experience">
                <option value="">Любой</option>
                <?php foreach ($arResult['FILTER_OPTIONS']['EXPERIENCE'] as $xmlId => $name): ?>
                    <option value="<?= htmlspecialcharsbx($xmlId) ?>"
                            <?= ($arParams['FILTER_EXPERIENCE'] ?? '') === $xmlId ? 'selected' : '' ?>>
                        <?= htmlspecialcharsbx($name) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="filter-search">
            <input type="text"
                   name="SEARCH"
                   placeholder="Поиск по названию..."
                   value="<?= htmlspecialcharsbx($arParams['FILTER_SEARCH'] ?? '') ?>">
        </div>

        <!-- ===== PER_PAGE ===== -->
        <div class="vacancies-per-page">
            <label for="per-page-select">Показывать по:</label>
            <select name="PER_PAGE" id="per-page-select" onchange="this.form.submit()">
                <?php foreach ($arResult['ALLOWED_PER_PAGE'] as $count): ?>
                    <option value="<?= $count ?>" <?= $count == $arResult['ELEMENTS_COUNT'] ? 'selected' : '' ?>>
                        <?= $count ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- ===== КНОПКИ ===== -->
        <div class="vacancies-filter__actions">
            <button type="submit" class="vacancies-filter__submit">Найти</button>
            <a href="?" class="vacancies-filter__reset">Сбросить</a>
        </div>

    </form>

    <!-- ===== СПИСОК ВАКАНСИЙ ===== -->
    <?php if (empty($arResult['ITEMS'])): ?>
        <div class="vacancies-list__empty">
            <p>К сожалению, по вашему запросу ничего не найдено.</p>
        </div>
    <?php else: ?>
        <div class="vacancies-list__items">
            <?php foreach ($arResult['ITEMS'] as $item): ?>
                <div class="vacancy-card" data-vacancy-id="<?= $item['ID'] ?>">

                    <div class="vacancy-card__header">
                        <h3 class="vacancy-card__title"><?= $item['NAME'] ?></h3>
                        <?php if (!empty($item['SECTION'])): ?>
                            <span class="vacancy-card__section"><?= $item['SECTION'] ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="vacancy-card__meta">
                        <?php if (!empty($item['CITY'])): ?>
                            <span class="vacancy-card__meta-item">
                                <svg class="vacancy-card__icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                    <circle cx="12" cy="10" r="3"></circle>
                                </svg>
                                <?= $item['CITY'] ?>
                            </span>
                        <?php endif; ?>

                        <?php if (!empty($item['EXPERIENCE'])): ?>
                            <span class="vacancy-card__meta-item">
                                <svg class="vacancy-card__icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                                    <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
                                </svg>
                                Опыт: <?= $item['EXPERIENCE'] ?>
                            </span>
                        <?php endif; ?>

                        <?php if (!empty($item['EMPLOYMENT_TYPE'])): ?>
                            <span class="vacancy-card__meta-item">
                                <svg class="vacancy-card__icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <polyline points="12 6 12 12 16 14"></polyline>
                                </svg>
                                <?= $item['EMPLOYMENT_TYPE'] ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($item['PREVIEW_TEXT'])): ?>
                        <div class="vacancy-card__description">
                            <?= $item['PREVIEW_TEXT'] ?>
                        </div>
                    <?php endif; ?>

                    <div class="vacancy-card__footer">
                        <div class="vacancy-card__salary"><?= $item['SALARY'] ?></div>
                    </div>

                </div>
            <?php endforeach; ?>
        </div>

        <!-- ===== ПАГИНАЦИЯ ===== -->
        <?php if ($arResult['TOTAL_PAGES'] > 1): ?>
            <div class="vacancies-pagination">
                <?php for ($i = 1; $i <= $arResult['TOTAL_PAGES']; $i++): ?>
                    <?php
                    $queryParams = $_GET;
                    $queryParams['PAGEN_1'] = $i;
                    $url = '?' . http_build_query($queryParams);
                    ?>
                    <?php if ($i == $arResult['PAGE_NUMBER']): ?>
                        <span class="vacancies-pagination__current"><?= $i ?></span>
                    <?php else: ?>
                        <a href="<?= htmlspecialcharsbx($url) ?>" class="vacancies-pagination__link"><?= $i ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>