<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

CJSCore::Init(['ajax']);
$calculatorParameters = [
    'componentName' => 'sandbox:delivery.calculator',
    'action' => 'calculate',
    'idbutton' => 'calc_button',
    'idresult' => 'calc_result',
    'fields' => [
        'weight' => 'calc_weight',
        'length' => 'calc_length',
        'width' => 'calc_width',
        'height' => 'calc_height',
        'distance' => 'calc_distance',
    ],
];
?>

<div class="delivery-calculator">
    <h3>Калькулятор стоимости доставки</h3>

    <div class="calc-field">
        <label>Вес (кг): <input type="number" id="<?= htmlspecialcharsbx($calculatorParameters['fields']['weight']) ?>" step="0.1"></label>
    </div>
    <div class="calc-field">
        <label>Длина (см): <input type="number" id="<?= htmlspecialcharsbx($calculatorParameters['fields']['length']) ?>" step="1"></label>
    </div>
    <div class="calc-field">
        <label>Ширина (см): <input type="number" id="<?= htmlspecialcharsbx($calculatorParameters['fields']['width']) ?>" step="1"></label>
    </div>
    <div class="calc-field">
        <label>Высота (см): <input type="number" id="<?= htmlspecialcharsbx($calculatorParameters['fields']['height']) ?>" step="1"></label>
    </div>
    <div class="calc-field">
        <label>Расстояние (км): <input type="number" id="<?= htmlspecialcharsbx($calculatorParameters['fields']['distance']) ?>" step="1"></label>
    </div>

    <button type="button" id="<?= htmlspecialcharsbx($calculatorParameters['idbutton']) ?>">Рассчитать</button>

    <div id="<?= htmlspecialcharsbx($calculatorParameters['idresult']) ?>" style="margin-top: 15px; font-weight: bold;"></div>
</div>
<script>
    BX.ready(function () {
        BX.CustomCalc = new CustomCalc(<?= \Bitrix\Main\Web\Json::encode($calculatorParameters) ?>);
    });
</script>
