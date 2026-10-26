<?php

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");

global $APPLICATION;

$APPLICATION->SetTitle("Мероприятия");

$APPLICATION->IncludeComponent(
    "sandbox:events.list",
    ".default",
    [
        "IBLOCK_CODE" => "events",
        "IBLOCK_TYPE" => "CONTENT",
        "SECTION_ID" => max(0, (int) ($_GET['SECTION_ID'] ?? 0)),
        "ELEMENTS_COUNT" => 10,          // Количество элементов
        "CACHE_TIME" => 3600,            // Время кеширования (сек)
        "CACHE_GROUPS" => "Y",           // Учитывать группы пользователей
    ],
    false
);
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");
