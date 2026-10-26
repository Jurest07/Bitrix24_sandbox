<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Iblock\PropertyTable;
use Bitrix\Main\Loader;
use Bitrix\Iblock\ElementTable;
use Bitrix\Iblock\SectionTable;
use Bitrix\Iblock\PropertyEnumerationTable;

class VacanciesListComponent extends CBitrixComponent
{
    private static $handlerAdded = false;

    public function onPrepareComponentParams($arParams)
    {
        $elementsCount = 5;
        $allowedPerPage = [5, 10, 20, 50];

        $arParams['IBLOCK_CODE'] = trim($arParams['IBLOCK_CODE'] ?? '');
        $arParams['SECTION_ID'] = intval($arParams['SECTION_ID'] ?? 0);

        if (isset($_GET['PER_PAGE'])) {
            $perPage = (int)$_GET['PER_PAGE'];
            if (in_array($perPage, $allowedPerPage, true)) {
                $elementsCount = $perPage;
            }
        }

        $arParams['ELEMENTS_COUNT'] = $elementsCount;
        $arParams['ALLOWED_PER_PAGE'] = $allowedPerPage;

        $arParams['PAGE_NUMBER'] = max(1, (int)($_GET['PAGEN_1'] ?? 1));
//
//        $arParams['SORT_BY'] = 'DATE_ACTIVE_FROM';
//        $arParams['SORT_ORDER'] = 'DESC';
//
        $arParams['CACHE_TIME'] = 3600;
        $arParams['CACHE_GROUPS'] = 'Y';

        $arParams['FILTER_CITY'] = (array)($_GET['CITY'] ?? []);
        $arParams['FILTER_EMPLOYMENT_TYPE'] = (array)($_GET['EMPLOYMENT_TYPE'] ?? []);
        $arParams['FILTER_EXPERIENCE'] = trim($_GET['EXPERIENCE'] ?? '');
        $arParams['FILTER_SEARCH'] = trim($_GET['SEARCH'] ?? '');

        return $arParams;
    }

    public function executeComponent()
    {
        \Bitrix\Main\UI\Extension::load("sidepanel");
        if (!Loader::includeModule('iblock')) {
            ShowError('Модуль "Инфоблоки" не установлен');
            return;
        }

        $blockId = $this->getIblockIdByCode($this->arParams["IBLOCK_CODE"]);
        if ($blockId == null) {
            ShowError('Инфоблок "Вакансии" не найден');
            return;
        }
        $this->arResult['IBLOCK_ID'] = $blockId;

        $result = $this->getVacancies($blockId);
        $this->arResult["ITEMS"] = $result["ITEMS"];
        $this->arResult["TOTAL_COUNT"] = $result["TOTAL_COUNT"];
        $this->arResult["ELEMENTS_COUNT"] = $this->arParams["ELEMENTS_COUNT"];
        $this->arResult["PAGE_NUMBER"] = $this->arParams["PAGE_NUMBER"];
        $this->arResult["TOTAL_PAGES"] = (int)ceil($this->arResult['TOTAL_COUNT'] / $this->arResult['ELEMENTS_COUNT']);
        $this->arResult["ALLOWED_PER_PAGE"] = $this->arParams["ALLOWED_PER_PAGE"];
        $this->arResult['FILTER_OPTIONS'] = [
            'CITY' => $this->getEnumOptions($blockId, 'CITY'),
            'EMPLOYMENT_TYPE' => $this->getEnumOptions($blockId, 'EMPLOYMENT_TYPE'),
            'EXPERIENCE' => $this->getEnumOptions($blockId, 'EXPERIENCE'),
        ];

        $this->includeComponentTemplate();
    }

    private function prepareToTemplate(&$elements): void {
        foreach ($elements as &$element) {
            foreach ($element as $key => $value) {
                if ($key == "SALARY_FROM" || $key == "SALARY_TO") {
                    if (!empty($value))
                        $value = number_format((float)$value, 0, '.', ' ');
                    else
                        $value = "NS"; //"NOT STATED";
                }
                $element[$key] = htmlspecialcharsbx($value);
            }
            $element['SALARY'] = $this->formatSalary($element['SALARY_FROM'], $element['SALARY_TO']);
        }
        unset($element);
    }

    private function getVacancies(int $blockId): array
    {
        $cacheId = $this->getCacheId();
        $cachePath = $this->getComponentCachePath();
        $cache = \Bitrix\Main\Data\Cache::createInstance();

        // Регистрируем обработчики сброса кеша
        $this->addCacheClearHandler();
        $result = [];
        if ($cache->initCache($this->arParams['CACHE_TIME'], $cacheId, $cachePath)) {
            // ✅ Кеш есть — берём из него
            $result = $cache->getVars();
        } elseif ($cache->startDataCache()) {
            // ❌ Кеша нет — делаем запросы к БД
            $result = $this->getVacanciesFromDb($blockId);
            $this->prepareToTemplate($result["ITEMS"]);
            // Сохраняем в кеш
            $cache->endDataCache($result);
        }
        return $result;
    }

    private function getVacanciesFromDb(int $blockId)
    {
        $iblock = \Bitrix\Iblock\IblockTable::getList([
            'select' => ['API_CODE'],
            'filter' => ['ID' => $blockId],
            'limit' => 1,
        ])->fetch();
        if (empty($iblock['API_CODE'])) {
            throw new \RuntimeException('Для инфоблока вакансий не задан API_CODE, необходимый для работы ORM');
        }
        $entityDataClass = \Bitrix\Iblock\Iblock::wakeUp($blockId)->getEntityDataClass();
        $apicode = strtoupper((string)$iblock['API_CODE']);

        $filter = ['=ACTIVE' => 'Y'];

        if (!empty($this->arParams['FILTER_CITY'])) {
            $filter['=CITY.VALUE'] = $this->arParams['FILTER_CITY']; // ORM сделает IN
        }
        if (!empty($this->arParams['FILTER_EMPLOYMENT_TYPE'])) {
            $filter['=EMPLOYMENT_TYPE.VALUE'] = $this->arParams['FILTER_EMPLOYMENT_TYPE'];
        }
        if (!empty($this->arParams['FILTER_EXPERIENCE'])) {
            $filter['=EXPERIENCE.VALUE'] = $this->arParams['FILTER_EXPERIENCE'];
        }
        if (!empty($this->arParams['FILTER_SEARCH'])) {
            $filter['%NAME'] = $this->arParams['FILTER_SEARCH'];
        }

        $rsElements = $entityDataClass::getList([
            'select' => [
                'ID',
                'NAME',
                'IBLOCK_SECTION_ID',
                'PREVIEW_TEXT',
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
            'filter' => $filter,
            'order' => [
                'ID' => 'DESC',
            ],
            'limit' => $this->arParams['ELEMENTS_COUNT'],
            'offset' => ($this->arParams['PAGE_NUMBER'] - 1) * $this->arParams['ELEMENTS_COUNT'],
        ]);

       $totalCount = $entityDataClass::getCount($filter);

        $rsSections = SectionTable::getList([
            'filter' => ['IBLOCK_ID' => $blockId],
            'select' => ['ID', 'NAME'],
        ]);
        $sectionsMap = [];
        while ($arProperty = $rsSections->fetch()) {
            $sectionsMap[$arProperty['ID']] = $arProperty['NAME'];
        }

        $rsProperty = PropertyTable::getList([
            'filter' => ['IBLOCK_ID' => $blockId],
            'select' => ['ID', 'CODE', 'PROPERTY_TYPE'],
        ]);
        $propertiesMap = [];
        $enumIds = [];
        while ($arProperty = $rsProperty->fetch()) {
            $propertiesMap[$arProperty['CODE']] = $arProperty['PROPERTY_TYPE'];
            if ($arProperty['PROPERTY_TYPE'] == 'L') {
                $enumIds[] = $arProperty['ID'];
            }
        }

        $enumsValue = PropertyEnumerationTable::getList([
            'filter' => ['PROPERTY_ID' => $enumIds],
            'select' => ['ID', 'VALUE'],
        ]);
        $enumsValueMap = [];
        while ($enumVal = $enumsValue->fetch()) {
            $enumsValueMap[$enumVal['ID']] = $enumVal['VALUE'];
        }

        $elements = [];

        while ($arElement = $rsElements->fetch()) {
            $element = [];
            foreach ($arElement as $key => $value) {
                if ($key == 'IBLOCK_SECTION_ID') {
                    $element['SECTION'] = $sectionsMap[$value] ?? null;
                    continue;
                }
                if (str_starts_with($key, 'IBLOCK_ELEMENTS_ELEMENT_' . $apicode)) {
                    $key = str_replace('IBLOCK_ELEMENTS_ELEMENT_'. $apicode. '_', '', $key);
                    if (str_ends_with($key, 'VALUE')) {
                        $key = str_replace('_VALUE', '', $key);
                        if (isset($propertiesMap[$key]) && $propertiesMap[$key] === 'L') {
                            $element[$key] = $enumsValueMap[$value] ?? $value;
                        } else {
                            $element[$key] = $value;
                        }
                    }
                } else {
                    $element[$key] = $value;
                }
            }
            $elements[] = $element;
        }
        //\Bitrix\Main\Diag\Debug::writeToFile(['data' => $elements], "", "testLogVi2.txt");
        return [
            'ITEMS' => $elements,
            'TOTAL_COUNT' => $totalCount,
        ];
    }

    public function getCacheId($additionalCacheID = false)
    {
        $cacheId = 'vacancies_list_' . $this->arResult['IBLOCK_ID']
            . '_section_' . $this->arParams['SECTION_ID']
            . '_per_page_' . $this->arParams['ELEMENTS_COUNT']
            . '_page_' . $this->arParams['PAGE_NUMBER']
            . '_city_' . md5(implode(',', (array)$this->arParams['FILTER_CITY']))
            . '_employment_' . md5(implode(',', (array)$this->arParams['FILTER_EMPLOYMENT_TYPE']))
            . '_experience_' . md5($this->arParams['FILTER_EXPERIENCE'])
            . '_search_' . md5($this->arParams['FILTER_SEARCH']);

        if ($this->arParams['CACHE_GROUPS'] === 'Y') {
            global $USER;
            $cacheId .= '_' . implode('_', $USER->GetUserGroupArray());
        }

        if ($additionalCacheID) {
            $cacheId .= '_' . $additionalCacheID;
        }

        return $cacheId;
    }

    private function getComponentCachePath()
    {
        $iblockId = $this->arResult['IBLOCK_ID'] ?? 0;
        return '/mycompany/vacancies.list/' . $iblockId;
    }

    private function addCacheClearHandler()
    {
        if (self::$handlerAdded) {
            return;
        }
        self::$handlerAdded = true;

        $eventManager = \Bitrix\Main\EventManager::getInstance();

        $clearCache = function($arFields) {
            $iblockId = $arFields['IBLOCK_ID'] ?? 0;
            if ($iblockId > 0) {
                $cache = \Bitrix\Main\Data\Cache::createInstance();
                $cache->cleanDir('/mycompany/vacancies.list/' . $iblockId);
            }
        };

        $eventManager->addEventHandler('iblock', 'OnAfterIBlockElementAdd', $clearCache);
        $eventManager->addEventHandler('iblock', 'OnAfterIBlockElementUpdate', $clearCache);
        $eventManager->addEventHandler('iblock', 'OnAfterIBlockElementDelete', $clearCache);
        $eventManager->addEventHandler('iblock', 'OnAfterIBlockSectionAdd', $clearCache);
        $eventManager->addEventHandler('iblock', 'OnAfterIBlockSectionUpdate', $clearCache);
        $eventManager->addEventHandler('iblock', 'OnAfterIBlockSectionDelete', $clearCache);
    }

    private function formatSalary($salaryFrom, $salaryTo): string
    {
        $result = "";
        if ($salaryFrom == "NS" && $salaryTo == "NS") {
            $result .= "Зарплата не указана";
        } else if ($salaryFrom == "NS") {
            $result .= "до " . $salaryTo . " ₽";
        } else if ($salaryTo == "NS") {
            $result .= "от " . $salaryFrom . " ₽";
        } else {
            $result .= "от " . $salaryFrom . " ₽ до " . $salaryTo . " ₽";
        }
        return $result;
    }

    private function getIblockIdByCode(string $iblockCode): ?int
    {
        $iblock = \Bitrix\Iblock\IblockTable::getList([
            'filter' => [
                "CODE" => $iblockCode,
                "ACTIVE" => "Y"
            ],
            'select' => [
                'ID'
            ],
            'limit' => 1
        ])->Fetch();

        return $iblock ? intval($iblock['ID']) : null;
    }

    private function getEnumOptions($blockId, $code): array
    {
        $result = [];
        $propertyId = PropertyTable::getList([
            'filter' => [
                'IBLOCK_ID' => $blockId,
                'CODE' => $code],
            'select' => ['ID'],
            'limit' => 1,
        ])->fetch()['ID'];
        $enumsValue = PropertyEnumerationTable::getList([
            'filter' => ['PROPERTY_ID' => $propertyId],
            'select' => ['ID', 'XML_ID', 'VALUE'],
        ]);
        while ($enum = $enumsValue->fetch()) {
            $result[$enum['ID']] = $enum['VALUE'];
        }
        return $result;
    }
}
