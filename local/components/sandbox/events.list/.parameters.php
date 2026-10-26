<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
    die();

use Bitrix\Main\Loader;
use Bitrix\Iblock\IblockTable;
use Bitrix\Iblock\SectionTable;

if (!Loader::includeModule('iblock')) {
    return;
}

// Получаем список инфоблоков типа CONTENT
$arIblocks = [];

$rsIblock = IblockTable::getList([
    'select' => ['ID', 'NAME', 'CODE', 'SORT'],
    'filter' => [
        '=IBLOCK_TYPE_ID' => 'CONTENT',
        '=ACTIVE' => 'Y',
    ],
    'order' => ['SORT' => 'ASC', 'NAME' => 'ASC'],
]);
while ($arIblock = $rsIblock->Fetch()) {
    $arIblocks[$arIblock['ID']] = $arIblock['NAME'] . ' (' . $arIblock['CODE'] . ')';
}

// Получаем список разделов для выбранного инфоблока (если инфоблок уже выбран)
$arSections = [];
if (!empty($arCurrentValues['IBLOCK_ID'])) {
    $rsSection = SectionTable::getList([
        'select' => ['ID', 'NAME', 'DEPTH_LEVEL', 'LEFT_MARGIN'],
        'filter' => [
            '=IBLOCK_ID' => $arCurrentValues['IBLOCK_ID'],
            '=ACTIVE' => 'Y',
        ],
        'order' => ['LEFT_MARGIN' => 'ASC'],
    ]);
    while ($arSection = $rsSection->Fetch()) {
        $depth = str_repeat('. ', $arSection['DEPTH_LEVEL'] - 1);
        $arSections[$arSection['ID']] = $depth . $arSection['NAME'];
    }
}

$arComponentParameters = [
    "GROUPS" => [
        "DATA" => [
            "NAME" => "Настройки данных",
        ],
        "CACHE" => [
            "NAME" => "Кеширование",
        ],
    ],
    "PARAMETERS" => [
        "IBLOCK_CODE" => [
            "PARENT" => "DATA", "NAME" => "Код инфоблока (если ID не указан)",
            "TYPE" => "STRING", "DEFAULT" => "events",
        ],
        "IBLOCK_TYPE" => [
            "PARENT" => "DATA", "NAME" => "Тип инфоблока",
            "TYPE" => "STRING", "DEFAULT" => "CONTENT",
        ],
        "IBLOCK_ID" => [
            "PARENT" => "DATA",
            "NAME" => "Инфоблок",
            "TYPE" => "LIST",
            "VALUES" => $arIblocks,
            "REFRESH" => "Y", // Обновляет параметры при выборе инфоблока
            "MULTIPLE" => "N",
        ],
        "SECTION_ID" => [
            "PARENT" => "DATA",
            "NAME" => "Раздел (категория)",
            "TYPE" => "LIST",
            "VALUES" => $arSections,
            "ADDITIONAL_VALUES" => "Y",
            "MULTIPLE" => "N",
        ],
        "ELEMENTS_COUNT" => [
            "PARENT" => "DATA",
            "NAME" => "Количество элементов",
            "TYPE" => "STRING",
            "DEFAULT" => 10,
        ],
        "CACHE_TIME" => [
            "PARENT" => "CACHE",
            "NAME" => "Время кеширования (сек)",
            "TYPE" => "STRING",
            "DEFAULT" => 3600,
        ],
        "CACHE_GROUPS" => [
            "PARENT" => "CACHE",
            "NAME" => "Разделять кеш по группам (права проверяются всегда)",
            "TYPE" => "CHECKBOX",
            "DEFAULT" => "Y",
        ],
    ],
];
