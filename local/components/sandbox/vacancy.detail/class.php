<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Loader;
use Bitrix\Iblock\PropertyTable;
use Bitrix\Iblock\PropertyEnumerationTable;
use Bitrix\Iblock\SectionTable;

class VacancyDetailComponent extends CBitrixComponent
{
    public function onPrepareComponentParams($arParams)
    {
        $arParams['IBLOCK_CODE'] = trim($arParams['IBLOCK_CODE'] ?? '');
        $arParams['VACANCY_ID'] = (int)($arParams['VACANCY_ID'] ?? 0);

        return $arParams;
    }

    public function executeComponent()
    {
        if (!Loader::includeModule('iblock')) {
            ShowError('Модуль "Инфоблоки" не установлен');
            return;
        }

        if (empty($this->arParams['IBLOCK_CODE'])) {
            ShowError('Не указан символьный код инфоблока');
            return;
        }

        if (empty($this->arParams['VACANCY_ID'])) {
            ShowError('Не указан ID вакансии');
            return;
        }

        $blockId = $this->getIblockIdByCode($this->arParams['IBLOCK_CODE']);
        if ($blockId === null) {
            ShowError('Инфоблок "Вакансии" не найден');
            return;
        }

        $vacancy = $this->getVacancyById($blockId, $this->arParams['VACANCY_ID']);
        if (empty($vacancy)) {
            ShowError('Вакансия не найдена');
            return;
        }

        $this->arResult['VACANCY'] = $vacancy;
        $this->arResult['IBLOCK_ID'] = $blockId;

        $this->includeComponentTemplate();
    }

    private function getVacancyById(int $blockId, int $vacancyId): array
    {
        $entityDataClass = \Bitrix\Iblock\Iblock::wakeUp($blockId)->getEntityDataClass();

        $iblock = \Bitrix\Iblock\IblockTable::getList([
            'select' => ['API_CODE'],
            'filter' => ['ID' => $blockId],
            'limit' => 1,
        ])->fetch();
        $apiCode = strtoupper((string)$iblock['API_CODE']);

        // 1. Получаем один элемент по ID
        $rsElements = $entityDataClass::getList([
            'select' => [
                'ID',
                'NAME',
                'CODE',
                'IBLOCK_SECTION_ID',
                'PREVIEW_TEXT',
                'DETAIL_TEXT',
                'CITY',
                'SALARY_FROM',
                'SALARY_TO',
                'EMPLOYMENT_TYPE.VALUE',
                'EXPERIENCE.VALUE',
                'DATE_CLOSE.VALUE',
                'RESPONSIBILITIES.VALUE',
                'REQUIREMENTS.VALUE',
                'CONDITIONS.VALUE',
            ],
            'filter' => [
                '=ID' => $vacancyId,
                '=ACTIVE' => 'Y',
            ],
            'limit' => 1,
        ]);

        $arElement = $rsElements->fetch();
        if (empty($arElement)) {
            return [];
        }

        // 2. Карты свойств и разделов
        $propertiesMap = $this->getPropertiesMap($blockId);
        $enumsValueMap = $this->getEnumsValueMap($blockId);
        $sectionsMap = $this->getSectionsMap($blockId);

        // 3. Обрабатываем элемент
        return $this->prepareVacancy($arElement, $apiCode, $propertiesMap, $enumsValueMap, $sectionsMap);
    }

    private function getPropertiesMap(int $blockId): array
    {
        $map = [];
        $rsProperty = PropertyTable::getList([
            'filter' => ['=IBLOCK_ID' => $blockId],
            'select' => ['ID', 'CODE', 'PROPERTY_TYPE'],
        ]);
        while ($arProperty = $rsProperty->fetch()) {
            $map[$arProperty['CODE']] = $arProperty['PROPERTY_TYPE'];
        }
        return $map;
    }

    private function getEnumsValueMap(int $blockId): array
    {
        $enumIds = [];
        $rsProperty = PropertyTable::getList([
            'filter' => ['=IBLOCK_ID' => $blockId, '=PROPERTY_TYPE' => 'L'],
            'select' => ['ID'],
        ]);
        while ($arProperty = $rsProperty->fetch()) {
            $enumIds[] = $arProperty['ID'];
        }

        $map = [];
        if (!empty($enumIds)) {
            $rsEnum = PropertyEnumerationTable::getList([
                'filter' => ['=PROPERTY_ID' => $enumIds],
                'select' => ['ID', 'VALUE'],
            ]);
            while ($arEnum = $rsEnum->fetch()) {
                $map[$arEnum['ID']] = $arEnum['VALUE'];
            }
        }
        return $map;
    }

    private function getSectionsMap(int $blockId): array
    {
        $map = [];
        $rsSections = SectionTable::getList([
            'filter' => ['=IBLOCK_ID' => $blockId],
            'select' => ['ID', 'NAME'],
        ]);
        while ($arSection = $rsSections->fetch()) {
            $map[$arSection['ID']] = $arSection['NAME'];
        }
        return $map;
    }

    private function prepareVacancy(array $arElement, string $apiCode, array $propertiesMap, array $enumsValueMap, array $sectionsMap): array
    {
        $element = [];

        foreach ($arElement as $key => $value) {
            // Раздел
            if ($key === 'IBLOCK_SECTION_ID') {
                $element['SECTION'] = $sectionsMap[$value] ?? null;
                continue;
            }

            // Свойства с префиксом
            if (str_starts_with($key, 'IBLOCK_ELEMENTS_ELEMENT_' . $apiCode)) {
                $cleanKey = str_replace('IBLOCK_ELEMENTS_ELEMENT_' . $apiCode . '_', '', $key);

                if (str_ends_with($cleanKey, 'VALUE')) {
                    $cleanKey = str_replace('_VALUE', '', $cleanKey);

                    if (isset($propertiesMap[$cleanKey]) && $propertiesMap[$cleanKey] === 'L') {
                        $element[$cleanKey] = $enumsValueMap[$value] ?? $value;
                    } else {
                        $element[$cleanKey] = $value;
                    }
                }
                continue;
            }

            // Обычные поля
            $element[$key] = $value;
        }

        // Форматируем зарплату
        $element['SALARY'] = $this->formatSalary(
            $element['SALARY_FROM'] ?? null,
            $element['SALARY_TO'] ?? null
        );

        return $element;
    }

    private function formatSalary($salaryFrom, $salaryTo): string
    {
        $from = !empty($salaryFrom) ? number_format((float)$salaryFrom, 0, '.', ' ') : null;
        $to = !empty($salaryTo) ? number_format((float)$salaryTo, 0, '.', ' ') : null;

        if ($from && $to) {
            return "от {$from} ₽ до {$to} ₽";
        }
        if ($from) {
            return "от {$from} ₽";
        }
        if ($to) {
            return "до {$to} ₽";
        }
        return "Зарплата не указана";
    }

    private function getIblockIdByCode(string $iblockCode): ?int
    {
        $iblock = \Bitrix\Iblock\IblockTable::getList([
            'filter' => ['=CODE' => $iblockCode, '=ACTIVE' => 'Y'],
            'select' => ['ID'],
            'limit' => 1,
        ])->fetch();

        return $iblock ? (int)$iblock['ID'] : null;
    }
}