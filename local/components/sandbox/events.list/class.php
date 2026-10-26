<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Application;
use Bitrix\Main\Data\Cache;
use Bitrix\Main\Loader;
use Bitrix\Iblock\Iblock;
use Bitrix\Iblock\IblockTable;
use Bitrix\Iblock\SectionTable;
use Bitrix\Iblock\SectionElementTable;
use Bitrix\Iblock\PropertyEnumerationTable;

class SandboxEventsListComponent extends CBitrixComponent
{
    public function onPrepareComponentParams($arParams)
    {
        $arParams['IBLOCK_ID'] = max(0, (int) ($arParams['IBLOCK_ID'] ?? 0));
        $arParams['IBLOCK_CODE'] = trim((string) ($arParams['IBLOCK_CODE'] ?? 'events'));
        $arParams['IBLOCK_TYPE'] = trim((string) ($arParams['IBLOCK_TYPE'] ?? 'CONTENT'));
        $arParams['SECTION_ID'] = max(0, (int) ($arParams['SECTION_ID'] ?? 0));
        $arParams['ELEMENTS_COUNT'] = max(1, (int) ($arParams['ELEMENTS_COUNT'] ?? 10));
        $arParams['CACHE_TIME'] = max(0, (int) ($arParams['CACHE_TIME'] ?? 3600));
        $arParams['CACHE_GROUPS'] = ($arParams['CACHE_GROUPS'] ?? 'Y') === 'N' ? 'N' : 'Y';
        return $arParams;
    }

    public function executeComponent()
    {
        if (!Loader::includeModule('iblock')) {
            ShowError('Модуль «Инфоблоки» не установлен');
            return;
        }
        $filter = ['=ACTIVE' => 'Y'];
        if ($this->arParams['IBLOCK_ID'] > 0) {
            $filter['=ID'] = $this->arParams['IBLOCK_ID'];
        } else {
            $filter['=CODE'] = $this->arParams['IBLOCK_CODE'];
            $filter['=IBLOCK_TYPE_ID'] = $this->arParams['IBLOCK_TYPE'];
        }
        $iblock = IblockTable::getList([
            'select' => ['ID', 'API_CODE', 'RIGHTS_MODE'],
            'filter' => $filter,
            'order' => ['ID' => 'ASC'],
            'limit' => 1,
        ])->fetch();
        if (!$iblock || ($iblock['RIGHTS_MODE'] !== 'E' && \CIBlock::GetPermission($iblock['ID']) < 'R')) {
            ShowError('Инфоблок мероприятий не найден или недоступен');
            return;
        }
        if (empty($iblock['API_CODE'])) {
            ShowError('Для инфоблока мероприятий необходимо заполнить API_CODE');
            return;
        }
        $this->arParams['IBLOCK_ID'] = (int) $iblock['ID'];
        $this->arResult = $this->getEvents();
        $this->arResult['ELEMENTS_COUNT'] = $this->arParams['ELEMENTS_COUNT'];
        $this->includeComponentTemplate();
    }

    private function getEvents(): array
    {
        if ($this->arParams['CACHE_TIME'] === 0) {
            return $this->getEventsFromDb();
        }
        $cache = Cache::createInstance();
        $path = '/sandbox/events.list/' . $this->arParams['IBLOCK_ID'];
        if ($cache->initCache($this->arParams['CACHE_TIME'], $this->getCacheId(), $path)) {
            $result = $cache->getVars();
            // Событие должно исчезать по времени, даже если инфоблок никто не редактировал.
            if (time() < $result['VALID_UNTIL']) {
                return $result;
            }
            $cache->clean($this->getCacheId(), $path);
        }
        if (!$cache->startDataCache()) {
            return $this->getEventsFromDb();
        }
        $taggedCache = Application::getInstance()->getTaggedCache();
        $taggedCache->startTagCache($path);
        try {
            $taggedCache->registerTag('iblock_id_' . $this->arParams['IBLOCK_ID']);
            $result = $this->getEventsFromDb();
            $taggedCache->endTagCache();
            $cache->endDataCache($result);
            return $result;
        } catch (\Throwable $e) {
            $taggedCache->abortTagCache();
            $cache->abortDataCache();
            throw $e;
        }
    }

    private function getEventsFromDb(): array
    {
        $now = time();
        $result = ['ITEMS' => [], 'SECTIONS' => [], 'COUNT' => 0, 'VALID_UNTIL' => PHP_INT_MAX];
        $iblockId = $this->arParams['IBLOCK_ID'];
        $extendedRights = \CIBlock::GetArrayByID($iblockId, 'RIGHTS_MODE') === 'E';
        if (!$extendedRights && \CIBlock::GetPermission($iblockId) < 'R') {
            return $result;
        }
        $sections = SectionTable::getList([
            'select' => ['ID', 'NAME', 'CODE', 'DESCRIPTION', 'LEFT_MARGIN', 'RIGHT_MARGIN'],
            'filter' => [
                '=IBLOCK_ID' => $iblockId,
                '=GLOBAL_ACTIVE' => 'Y',
            ],
            'order' => [
                'SORT' => 'ASC',
                'NAME' => 'ASC',
            ],
        ]);
        while ($section = $sections->fetch()) {
            if ($extendedRights && !\CIBlockSectionRights::UserHasRightTo($iblockId, $section['ID'], 'section_read')) {
                continue;
            }
            $result['SECTIONS'][(int) $section['ID']] = $section;
        }
        $sectionIds = null;
        if ($this->arParams['SECTION_ID'] > 0) {
            $section = $result['SECTIONS'][$this->arParams['SECTION_ID']] ?? null;
            if (!$section) {
                return $result;
            }
            $result['SECTION'] = $section;
            $sectionIds = array_column(array_filter($result['SECTIONS'], static function ($child) use ($section) {
                return $child['LEFT_MARGIN'] >= $section['LEFT_MARGIN']
                    && $child['RIGHT_MARGIN'] <= $section['RIGHT_MARGIN'];
            }), 'ID');
        }
        $filter = [
            '=IBLOCK_ID' => $iblockId,
            '=ACTIVE' => 'Y',
            [
                'LOGIC' => 'OR',
                ['>=DATE_END.VALUE' => date('Y-m-d H:i:s', $now)],
                [
                    [
                        'LOGIC' => 'OR',
                        ['=DATE_END.VALUE' => null],
                        ['=DATE_END.VALUE' => ''],
                    ],
                    '>=DATE_START.VALUE' => date('Y-m-d H:i:s', $now),
                ],
            ],
        ];
        if ($sectionIds !== null) {
            $sectionElements = SectionElementTable::getList([
                'select' => ['IBLOCK_ELEMENT_ID'],
                'filter' => ['@IBLOCK_SECTION_ID' => $sectionIds],
            ])->fetchAll();
            $elementIds = array_unique(array_column($sectionElements, 'IBLOCK_ELEMENT_ID'));
            if (!$elementIds) {
                return $result;
            }
            $filter['@ID'] = $elementIds;
        }
        $enumIds = [];
        $enums = PropertyEnumerationTable::getList([
            'select' => ['ID', 'XML_ID'],
            'filter' => [
                '=PROPERTY.IBLOCK_ID' => $iblockId,
                '=PROPERTY.CODE' => 'NEED_REGISTRATION',
            ],
            'order' => ['SORT' => 'ASC', 'ID' => 'ASC'],
        ]);
        while ($enum = $enums->fetch()) {
            $enumIds[(int) $enum['ID']] = $enum['XML_ID'];
        }
        $dataClass = Iblock::wakeUp($iblockId)->getEntityDataClass();
        $limit = $this->arParams['ELEMENTS_COUNT'];
        $offset = 0;
        // ORM не проверяет расширенные права автоматически. Читаем порциями,
        // чтобы недоступные элементы не уменьшали количество доступных карточек.
        do {
            $rows = $dataClass::getList([
                'select' => [
                    'ID',
                    'IBLOCK_ID',
                    'NAME',
                    'CODE',
                    'PREVIEW_TEXT',
                    'PREVIEW_PICTURE',
                    'IBLOCK_SECTION_ID',
                    'PROPERTY_DATE_START_VALUE' => 'DATE_START.VALUE',
                    'PROPERTY_DATE_END_VALUE' => 'DATE_END.VALUE',
                    'PROPERTY_LOCATION_VALUE' => 'LOCATION.VALUE',
                    'PROPERTY_PRICE_VALUE' => 'PRICE.VALUE',
                    'PROPERTY_NEED_REGISTRATION_ENUM_ID' => 'NEED_REGISTRATION.VALUE',
                    'PROPERTY_REGISTRATION_URL_VALUE' => 'REGISTRATION_URL.VALUE',
                ],
                'filter' => $filter,
                'order' => [
                    'DATE_START.VALUE' => 'ASC',
                    'NAME' => 'ASC',
                    'ID' => 'ASC',
                ],
                'limit' => $limit,
                'offset' => $offset,
                'data_doubling' => false,
            ]);
            $fetched = 0;
            while ($item = $rows->fetch()) {
                $fetched++;
                if ($extendedRights && !\CIBlockElementRights::UserHasRightTo($iblockId, $item['ID'], 'element_read')) {
                    continue;
                }
                // ORM возвращает даты свойств в формате хранения, шаблон ожидает формат сайта.
                $item['DATE_START'] = $this->formatPropertyDate($item['PROPERTY_DATE_START_VALUE']);
                $item['DATE_END'] = $this->formatPropertyDate($item['PROPERTY_DATE_END_VALUE']);
                $item['PRICE'] = $this->formatPrice($item['PROPERTY_PRICE_VALUE']);
                $item['NEED_REGISTRATION'] = ($enumIds[(int) $item['PROPERTY_NEED_REGISTRATION_ENUM_ID']] ?? '') === 'Y';
                $item['REGISTRATION_URL'] = $this->getRegistrationUrl((string) $item['PROPERTY_REGISTRATION_URL_VALUE']);
                $expires = MakeTimeStamp($item['DATE_END'] ?: $item['DATE_START']);
                $result['VALID_UNTIL'] = min($result['VALID_UNTIL'], $expires + 1);
                $result['ITEMS'][] = $item;
                if (count($result['ITEMS']) === $limit) {
                    break;
                }
            }
            $offset += $limit;
        } while ($extendedRights && $fetched === $limit && count($result['ITEMS']) < $limit);
        $result['COUNT'] = count($result['ITEMS']);
        return $result;
    }

    private function formatPropertyDate($value): string
    {
        if ($value === null || $value === '') {
            return '';
        }
        $timestamp = $value instanceof \Bitrix\Main\Type\DateTime ? $value->getTimestamp() : strtotime((string) $value);
        return $timestamp === false ? '' : ConvertTimeStamp($timestamp, 'FULL');
    }

    private function formatPrice($price): string
    {
        return (float) $price > 0 ? number_format((float) $price, 0, '.', ' ') . ' ₽' : 'Бесплатно';
    }

    private function getRegistrationUrl(string $url): string
    {
        $url = trim($url);
        return filter_var($url, FILTER_VALIDATE_URL)
            && in_array(strtolower((string) parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true)
            ? $url : '';
    }

    public function getCacheId($additionalCacheID = false)
    {
        global $USER;
        // Учитываем также пользователя: расширенные права бывают индивидуальными.
        return md5(serialize([
            $this->arParams, (int) $USER->GetID(), $USER->GetUserGroupArray(), $additionalCacheID, LANGUAGE_ID,
        ]));
    }
}
