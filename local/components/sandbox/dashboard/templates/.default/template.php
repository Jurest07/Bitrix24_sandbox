<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}
/** @var array $arResult */
/** @var CBitrixComponentTemplate $this */

try {
    $data = Bitrix\Main\Web\Json::encode([
            'funnel' => $arResult['DEALS_BY_STAGE'],
            'months' => $arResult['DEALS_SUM_BY_MONTH'],
    ]);
} catch (\Bitrix\Main\ArgumentException $e) {

}

?>

<div class="dashboard">
    <div class="kpi-cards">
        <div class="kpi-card">
            <div class="value"><?= $arResult['TOTAL_DEALS'] ?></div>
            <div class="label">Всего сделок</div>
        </div>
        <div class="kpi-card">
            <div class="value"><?= number_format($arResult['AVG_AMOUNT'], 0, '.', ' ') ?> ₽</div>
            <div class="label">Средний чек</div>
        </div>
        <div class="kpi-card">
            <div class="value"><?= $arResult['CONVERSION'] ?>%</div>
            <div class="label">Конверсия</div>
        </div>
        <div class="kpi-card">
            <div class="value"><?= number_format(array_sum($arResult['DEALS_SUM_BY_MONTH']), 0, '.', ' ') ?> ₽</div>
            <div class="label">Сумма за всё время</div>
        </div>
    </div>

    <div class="charts">
        <div class="chart-card">
            <h3>Воронка продаж</h3>
            <canvas id="funnelChart"></canvas>
        </div>
        <div class="chart-card">
            <h3>Динамика по месяцам</h3>
            <canvas id="monthChart"></canvas>
        </div>
    </div>

    <script>
        BX.message({ DASHBOARD_DATA: '<?= CUtil::JSEscape($data) ?>' });
    </script>
</div>