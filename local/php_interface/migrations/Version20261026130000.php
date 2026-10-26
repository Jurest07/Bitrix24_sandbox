<?php

namespace Sprint\Migration;

use Bitrix\Main\Loader;

class Version20261026130000 extends Version
{
    protected $description = 'Создание инфоблока мероприятий, свойств и категорий';

    public function up()
    {
        if (!Loader::includeModule('iblock')) {
            throw new \RuntimeException('Модуль iblock не установлен');
        }
        $helper = $this->getHelperManager()->Iblock();
        $helper->addIblockTypeIfNotExists([
            'ID' => 'CONTENT', 'SECTIONS' => 'Y', 'IN_RSS' => 'N', 'SORT' => 500,
            'LANG' => [
                'ru' => ['NAME' => 'Контент', 'SECTION_NAME' => 'Разделы', 'ELEMENT_NAME' => 'Элементы'],
                'en' => ['NAME' => 'Content', 'SECTION_NAME' => 'Sections', 'ELEMENT_NAME' => 'Elements'],
            ],
        ]);
        $existing = $helper->getIblock('events', 'CONTENT');
        if ($existing) {
            $iblockId = (int) $existing['ID'];
        } else {
            $iblock = new \CIBlock();
            $iblockId = $iblock->Add([
                'NAME' => 'Мероприятия', 'CODE' => 'events', 'API_CODE' => 'Events',
                'IBLOCK_TYPE_ID' => 'CONTENT', 'LID' => ['s1'], 'ACTIVE' => 'Y',
                'VERSION' => 2, 'SORT' => 500,
                'GROUP_ID' => [1 => 'X', 2 => 'R'],
                'LIST_PAGE_URL' => '/local/pages/events/index.php',
                'SECTION_PAGE_URL' => '/local/pages/events/index.php?SECTION_ID=#SECTION_ID#',
                'DETAIL_PAGE_URL' => '',
                'ELEMENTS_NAME' => 'Мероприятия', 'ELEMENT_NAME' => 'Мероприятие',
                'SECTIONS_NAME' => 'Категории', 'SECTION_NAME' => 'Категория',
            ]);
            if (!$iblockId) {
                throw new \RuntimeException($iblock->LAST_ERROR);
            }
            $iblockId = (int) $iblockId;
        }

        $properties = [
        [
            'CODE' => 'DATE_START',
            'NAME' => 'Дата начала',
            'PROPERTY_TYPE' => 'S', // Строка (для даты используем S с настройками)
            'USER_TYPE' => 'DateTime', // Пользовательский тип "Дата/время"
            'IS_REQUIRED' => 'Y',
            'DEFAULT_VALUE' => '',
            'SORT' => 100,
        ],
        [
            'CODE' => 'DATE_END',
            'NAME' => 'Дата окончания',
            'PROPERTY_TYPE' => 'S',
            'USER_TYPE' => 'DateTime',
            'IS_REQUIRED' => 'N',
            'DEFAULT_VALUE' => '',
            'SORT' => 200,
        ],
        [
            'CODE' => 'LOCATION',
            'NAME' => 'Место проведения',
            'PROPERTY_TYPE' => 'S', // Строка
            'IS_REQUIRED' => 'Y',
            'DEFAULT_VALUE' => '',
            'SORT' => 300,
        ],
        [
            'CODE' => 'PRICE',
            'NAME' => 'Стоимость',
            'PROPERTY_TYPE' => 'N', // Число
            'IS_REQUIRED' => 'N',
            'DEFAULT_VALUE' => '',
            'SORT' => 400,
        ],
        [
            'CODE' => 'NEED_REGISTRATION',
            'NAME' => 'Требуется регистрация',
            'PROPERTY_TYPE' => 'L', // Список
            'IS_REQUIRED' => 'Y',
            'SORT' => 500,
            'VALUES' => [
                ['VALUE' => 'Да', 'DEF' => 'N', 'SORT' => 100, 'XML_ID' => 'Y'],
                ['VALUE' => 'Нет', 'DEF' => 'Y', 'SORT' => 200, 'XML_ID' => 'N'],
            ],
        ],
        [
            'CODE' => 'REGISTRATION_URL',
            'NAME' => 'Ссылка на регистрацию',
            'PROPERTY_TYPE' => 'S', // Строка
            'IS_REQUIRED' => 'N',
            'DEFAULT_VALUE' => '',
            'SORT' => 600,
        ],
        ];
        foreach ($properties as $property) {
            $helper->addPropertyIfNotExists($iblockId, array_merge([
                'ACTIVE' => 'Y', 'MULTIPLE' => 'N', 'FILTRABLE' => 'Y',
            ], $property));
        }
        $sections = [
        ['NAME' => 'Конференции', 'CODE' => 'conferences', 'SORT' => 100],
        ['NAME' => 'Семинары', 'CODE' => 'seminars', 'SORT' => 200],
        ['NAME' => 'Выставки', 'CODE' => 'exhibitions', 'SORT' => 300],
        ['NAME' => 'Мастер-классы', 'CODE' => 'master-classes', 'SORT' => 400],
        ['NAME' => 'Деловые завтраки', 'CODE' => 'business-breakfasts', 'SORT' => 500],
        ];
        foreach ($sections as $section) {
            $helper->addSectionIfNotExists($iblockId, array_merge(['ACTIVE' => 'Y'], $section));
        }
    }

    public function down()
    {
        if (!Loader::includeModule('iblock')) {
            throw new \RuntimeException('Модуль iblock не установлен');
        }
        $iblock = $this->getHelperManager()->Iblock()->getIblock('events', 'CONTENT');
        if ($iblock && !\CIBlock::Delete((int) $iblock['ID'])) {
            throw new \RuntimeException('Не удалось удалить инфоблок мероприятий');
        }
        // CONTENT может использоваться другими инфоблоками, поэтому тип сохраняем.
    }
}
