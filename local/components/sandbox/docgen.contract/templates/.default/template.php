<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

/** @var array $arParams */
/** @var array $arResult */

CJSCore::Init(['ajax']);

?>

<div class="docgen-contract">
    <h3>Генератор договоров</h3>

    <div class="docgen-deal-info">
        Сделка #<?= (int)$arResult['DEAL_ID'] ?>
    </div>

    <button type="button" id="docgen_generate_button" data-deal-id="<?= (int)$arResult['DEAL_ID'] ?>">
        Создать договор
    </button>

    <div id="docgen_result"></div>
</div>
