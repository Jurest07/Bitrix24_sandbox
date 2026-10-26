<?php
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");

global $APPLICATION;

$APPLICATION->IncludeComponent(
    "bitrix:ui.sidepanel.wrapper",
    "",
    [
        "POPUP_COMPONENT_NAME" => "sandbox:vacancy.detail",
        "POPUP_COMPONENT_TEMPLATE_NAME" => ".default",
        "POPUP_COMPONENT_PARAMS" => [
            "IBLOCK_CODE" => "vacancies",
            "VACANCY_ID" => (int)($_GET['ID'] ?? 0),
        ],
        "USE_PADDING" => "Y",
        "PAGE_MODE" => "N",
    ],
    false
);

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php");
?>