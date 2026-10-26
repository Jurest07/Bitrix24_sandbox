<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}
?>
<div class="events-component" data-events-list>
    <?php if (!empty($arResult['SECTION'])): ?>
        <div class="events-section-header">
            <h2><?= htmlspecialcharsbx($arResult['SECTION']['NAME']) ?></h2>
            <?php if (!empty($arResult['SECTION']['DESCRIPTION'])): ?>
                <p><?= htmlspecialcharsbx($arResult['SECTION']['DESCRIPTION']) ?></p>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    <?php if ($arResult['ITEMS']): ?>
        <fieldset class="events-filter">
            <legend>Категории мероприятий</legend>
            <label><input type="checkbox" class="filter-all-checkbox" checked> Все категории</label>
            <?php foreach ($arResult['SECTIONS'] as $section): ?>
                <label class="filter-checkbox">
                    <input type="checkbox" class="filter-section-checkbox" value="<?= (int) $section['ID'] ?>" checked>
                    <?= htmlspecialcharsbx($section['NAME']) ?>
                </label>
            <?php endforeach; ?>
        </fieldset>
        <div class="events-list">
            <?php foreach ($arResult['ITEMS'] as $item): ?>
                <article class="event-item" data-section-id="<?= (int) $item['IBLOCK_SECTION_ID'] ?>">
                    <?php
                    $img = $item['PREVIEW_PICTURE'] ? CFile::ResizeImageGet(
                        $item['PREVIEW_PICTURE'], ['width' => 300, 'height' => 200], BX_RESIZE_IMAGE_PROPORTIONAL, true
                    ) : false;
                    ?>
                    <?php if ($img): ?>
                        <div class="event-image"><img src="<?= htmlspecialcharsbx($img['src']) ?>" alt="<?= htmlspecialcharsbx($item['NAME']) ?>" loading="lazy"></div>
                    <?php endif; ?>
                    <div class="event-content">
                        <h3 class="event-title"><?= htmlspecialcharsbx($item['NAME']) ?></h3>
                        <div class="event-datetime">
                            <?= htmlspecialcharsbx(FormatDate('j F Y, H:i', MakeTimeStamp($item['DATE_START']))) ?>
                            <?php if ($item['DATE_END']): ?>
                                — <?= htmlspecialcharsbx(FormatDate('j F Y, H:i', MakeTimeStamp($item['DATE_END']))) ?>
                            <?php endif; ?>
                        </div>
                        <?php if ($item['PROPERTY_LOCATION_VALUE']): ?>
                            <div class="event-location"><?= htmlspecialcharsbx($item['PROPERTY_LOCATION_VALUE']) ?></div>
                        <?php endif; ?>
                        <?php if ($item['PREVIEW_TEXT']): ?>
                            <div class="event-description"><?= nl2br(htmlspecialcharsbx($item['PREVIEW_TEXT'])) ?></div>
                        <?php endif; ?>
                        <div class="event-footer">
                            <div class="event-price"><?= htmlspecialcharsbx($item['PRICE']) ?></div>
                            <?php if ($item['NEED_REGISTRATION']): ?>
                                <div class="event-registration">
                                    <?php if ($item['REGISTRATION_URL']): ?>
                                        <a href="<?= htmlspecialcharsbx($item['REGISTRATION_URL']) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-register">Зарегистрироваться</a>
                                    <?php else: ?>
                                        <span class="badge badge-registration">Требуется регистрация</span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
        <div class="events-count" aria-live="polite">Показано: <?= (int) $arResult['COUNT'] ?></div>
        <p class="events-filter-empty" hidden>В выбранных категориях нет мероприятий</p>
    <?php else: ?>
        <div class="events-empty"><p>Нет предстоящих или текущих мероприятий</p></div>
    <?php endif; ?>
</div>
