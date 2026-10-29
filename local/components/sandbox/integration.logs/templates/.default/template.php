<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

/** @var array $arResult */
$escape = static function ($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
};
$params = $arResult['FILTER'];
?>
<section class="il-wrap" aria-label="Журнал интеграции">
    <p class="il-note">Найдено записей: <?= (int)$arResult['TOTAL'] ?> · Часовой пояс: <?= $escape($arResult['TIMEZONE']) ?></p>

    <form method="get" action="<?= $escape($arResult['PATH']) ?>" class="il-filters">
        <?php foreach (['from' => 'Дата с', 'to' => 'Дата по'] as $key => $label): ?>
            <label>
                <?= $label ?>
                <input type="date" name="<?= $key ?>" value="<?= $escape($params[$key]) ?>">
            </label>
        <?php endforeach; ?>

        <?php foreach (['time_from' => 'Время с', 'time_to' => 'Время по'] as $key => $label): ?>
            <label>
                <?= $label ?>
                <input type="time" step="1" name="<?= $key ?>" value="<?= $escape($params[$key]) ?>">
            </label>
        <?php endforeach; ?>

        <label>
            Статус ошибки
            <select name="level">
                <option value="">Все статусы</option>
                <?php foreach ($arResult['LEVELS'] as $level): ?>
                    <option value="<?= $escape($level) ?>" <?= $params['level'] === $level ? 'selected' : '' ?>><?= $escape(strtoupper($level)) ?></option>
                <?php endforeach; ?>
            </select>
        </label>

        <?php foreach (['module' => 'ID модуля', 'ip' => 'IP-адрес', 'author' => 'Автор'] as $key => $label): ?>
            <label>
                <?= $label ?>
                <input type="text" name="<?= $key ?>" value="<?= $escape($params[$key]) ?>">
            </label>
        <?php endforeach; ?>

        <button type="submit" class="il-submit">Показать</button>
        <a href="<?= $escape($arResult['PATH']) ?>">Сбросить</a>
    </form>

    <p class="il-note">Диапазон времени применяется к каждому дню выбранного периода. Границы включены.</p>

    <?php if ($arResult['ERROR'] !== ''): ?>
        <p class="il-error-box" role="alert"><?= $escape($arResult['ERROR']) ?></p>
    <?php endif; ?>

    <?php if ($arResult['CAN_EXPORT'] && $arResult['ERROR'] === ''): ?>
        <p><a href="<?= $escape($arResult['EXPORT_URL']) ?>">Скачать CSV по текущим фильтрам (все записи)</a></p>
    <?php endif; ?>

    <div class="il-scroll">
        <table class="il-table">
            <thead>
                <tr>
                    <th scope="col">Дата</th>
                    <th scope="col">Время</th>
                    <th scope="col">Статус</th>
                    <th scope="col">IP-адрес</th>
                    <th scope="col">ID модуля</th>
                    <th scope="col">Автор</th>
                    <th scope="col">Текст лога</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($arResult['ROWS'] as $row): ?>
                    <tr>
                        <td><?= $escape($row['UF_OCCURRED_AT']->format('d.m.Y')) ?></td>
                        <td><?= $escape($row['UF_OCCURRED_AT']->format('H:i:s')) ?></td>
                        <td>
                            <span class="il-badge il-<?= $escape($row['UF_LEVEL']) ?>"><?= $escape(strtoupper($row['UF_LEVEL'])) ?></span>
                        </td>
                        <?php foreach (['UF_SOURCE_IP', 'UF_MODULE_ID', 'UF_AUTHOR'] as $field): ?>
                            <td><?= $escape($row[$field]) ?></td>
                        <?php endforeach; ?>
                        <td class="il-message"><?= $escape($row['UF_MESSAGE']) ?></td>
                    </tr>
                <?php endforeach; ?>

                <?php if (!$arResult['ROWS'] && $arResult['ERROR'] === ''): ?>
                    <tr>
                        <td colspan="7">Записей пока нет. Запустите импорт или измените фильтры.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <nav class="il-pages" aria-label="Страницы журнала">
        <?php if ($arResult['PREV_URL'] !== ''): ?>
            <a href="<?= $escape($arResult['PREV_URL']) ?>">← Назад</a>
        <?php endif; ?>

        <span>Страница <?= (int)$arResult['PAGE'] ?> из <?= (int)$arResult['PAGES'] ?></span>

        <?php if ($arResult['NEXT_URL'] !== ''): ?>
            <a href="<?= $escape($arResult['NEXT_URL']) ?>">Далее →</a>
        <?php endif; ?>
    </nav>
</section>
