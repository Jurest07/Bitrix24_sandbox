<?php

namespace Sprint\Migration;

use Bitrix\Main\Loader;

class Version20261026130100 extends Version
{
    protected $description = 'Добавление 25 тестовых мероприятий';

    private function getEvents(): array
    {
        return [
            [
                'NAME' => 'IT-конференция 2026: Новые горизонты',
                'CODE' => 'it-conference-2026',
                'SECTION' => 'conferences',
                'DATE_START' => '2026-05-15 10:00:00',
                'DATE_END' => '2026-05-17 18:00:00',
                'LOCATION' => 'Москва, Крокус Экспо, павильон 1',
                'PRICE' => 15000,
                'NEED_REGISTRATION' => 'Y',
                'REGISTRATION_URL' => 'https://it-conf-2026.ru/register',
            ],
            [
                'NAME' => 'Семинар по цифровому маркетингу',
                'CODE' => 'digital-marketing-seminar',
                'SECTION' => 'seminars',
                'DATE_START' => '2026-06-10 14:00:00',
                'DATE_END' => '2026-06-10 18:00:00',
                'LOCATION' => 'Москва, ул. Тверская, 18, офис 507',
                'PRICE' => 5000,
                'NEED_REGISTRATION' => 'Y',
                'REGISTRATION_URL' => 'https://digital-seminar.ru',
            ],
            [
                'NAME' => 'Выставка инновационных технологий',
                'CODE' => 'innovation-exhibition-2026',
                'SECTION' => 'exhibitions',
                'DATE_START' => '2026-07-01 09:00:00',
                'DATE_END' => '2026-07-03 20:00:00',
                'LOCATION' => 'Санкт-Петербург, Экспофорум',
                'PRICE' => 0,
                'NEED_REGISTRATION' => 'N',
                'REGISTRATION_URL' => '',
            ],
            [
                'NAME' => 'Мастер-класс по управлению проектами',
                'CODE' => 'project-management-masterclass',
                'SECTION' => 'master-classes',
                'DATE_START' => '2026-08-20 11:00:00',
                'DATE_END' => '2026-08-20 16:00:00',
                'LOCATION' => 'Москва, Digital October, зал 3',
                'PRICE' => 8000,
                'NEED_REGISTRATION' => 'Y',
                'REGISTRATION_URL' => 'https://pm-masterclass.ru',
            ],
            [
                'NAME' => 'Деловой завтрак с инвесторами',
                'CODE' => 'investor-breakfast',
                'SECTION' => 'business-breakfasts',
                'DATE_START' => '2026-09-05 08:30:00',
                'DATE_END' => '2026-09-05 11:00:00',
                'LOCATION' => 'Москва, The Ritz-Carlton, ресторан O2',
                'PRICE' => 12000,
                'NEED_REGISTRATION' => 'Y',
                'REGISTRATION_URL' => 'https://investor-breakfast.ru',
            ],
            [
                'NAME' => 'Конференция по искусственному интеллекту AI Day',
                'CODE' => 'ai-day-2026',
                'SECTION' => 'conferences',
                'DATE_START' => '2026-10-12 10:00:00',
                'DATE_END' => '2026-10-13 18:00:00',
                'LOCATION' => 'Казань, IT-парк, зал ',
                'PRICE' => 20000,
                'NEED_REGISTRATION' => 'Y',
                'REGISTRATION_URL' => 'https://aiday2026.ru',
            ],
            [
                'NAME' => 'Семинар по командообразованию',
                'CODE' => 'team-building-seminar',
                'SECTION' => 'seminars',
                'DATE_START' => '2026-11-01 10:00:00',
                'DATE_END' => '2026-11-01 17:00:00',
                'LOCATION' => 'Москва, БЦ "White Stone", конференц-зал',
                'PRICE' => 6000,
                'NEED_REGISTRATION' => 'Y',
                'REGISTRATION_URL' => 'https://team-building-seminar.ru',
            ],
            [
                'NAME' => 'Выставка стартапов',
                'CODE' => 'startup-exhibition-2026',
                'SECTION' => 'exhibitions',
                'DATE_START' => '2026-12-01 10:00:00',
                'DATE_END' => '2026-12-02 19:00:00',
                'LOCATION' => 'Екатеринбург, Экспо-центр, павильон 2',
                'PRICE' => 0,
                'NEED_REGISTRATION' => 'N',
                'REGISTRATION_URL' => '',
            ],
            [
                'NAME' => 'Мастер-класс по Figma для дизайнеров',
                'CODE' => 'figma-masterclass',
                'SECTION' => 'master-classes',
                'DATE_START' => '2027-01-15 10:00:00',
                'DATE_END' => '2027-01-15 14:00:00',
                'LOCATION' => 'Москва, Design Factory, ул. Мясницкая, 13',
                'PRICE' => 4000,
                'NEED_REGISTRATION' => 'Y',
                'REGISTRATION_URL' => 'https://figma-masterclass.ru',
            ],
            [
                'NAME' => 'Новогодний корпоратив',
                'CODE' => 'new-year-party-2026',
                'SECTION' => 'business-breakfasts',
                'DATE_START' => '2026-12-20 19:00:00',
                'DATE_END' => '2026-12-20 23:59:00',
                'LOCATION' => 'Москва, БЦ "Федерация", зал 71 этажа',
                'PRICE' => 25000,
                'NEED_REGISTRATION' => 'Y',
                'REGISTRATION_URL' => 'https://ny-party.ru',
            ],
            [
                'NAME' => 'Форум разработчиков облачных сервисов',
                'CODE' => 'cloud-services-forum-2027',
                'SECTION' => 'conferences',
                'DATE_START' => '2027-02-01 10:00:00',
                'DATE_END' => '2027-02-01 18:00:00',
                'LOCATION' => 'Москва, Центр цифровых технологий',
                'PRICE' => 10000,
                'NEED_REGISTRATION' => 'Y',
                'REGISTRATION_URL' => '',
            ],
            [
                'NAME' => 'Семинар по финансовому планированию',
                'CODE' => 'financial-planning-seminar-2027',
                'SECTION' => 'seminars',
                'DATE_START' => '2027-02-05 10:00:00',
                'DATE_END' => '2027-02-05 14:00:00',
                'LOCATION' => 'Санкт-Петербург, деловой центр «Север»',
                'PRICE' => 3500,
                'NEED_REGISTRATION' => 'Y',
                'REGISTRATION_URL' => '',
            ],
            [
                'NAME' => 'Выставка современного искусства',
                'CODE' => 'modern-art-exhibition-2027',
                'SECTION' => 'exhibitions',
                'DATE_START' => '2027-02-09 10:00:00',
                'DATE_END' => '2027-02-11 10:00:00',
                'LOCATION' => 'Казань, выставочный зал «Панорама»',
                'PRICE' => 0,
                'NEED_REGISTRATION' => 'N',
                'REGISTRATION_URL' => '',
            ],
            [
                'NAME' => 'Мастер-класс по предметной фотографии',
                'CODE' => 'product-photography-workshop-2027',
                'SECTION' => 'master-classes',
                'DATE_START' => '2027-02-13 10:00:00',
                'DATE_END' => '2027-02-13 15:00:00',
                'LOCATION' => 'Москва, фотостудия «Свет»',
                'PRICE' => 4500,
                'NEED_REGISTRATION' => 'Y',
                'REGISTRATION_URL' => '',
            ],
            [
                'NAME' => 'Деловой завтрак для владельцев малого бизнеса',
                'CODE' => 'small-business-breakfast-2027',
                'SECTION' => 'business-breakfasts',
                'DATE_START' => '2027-02-17 10:00:00',
                'DATE_END' => '2027-02-17 13:00:00',
                'LOCATION' => 'Екатеринбург, бизнес-клуб «Диалог»',
                'PRICE' => 2500,
                'NEED_REGISTRATION' => 'Y',
                'REGISTRATION_URL' => '',
            ],
            [
                'NAME' => 'Конференция по информационной безопасности',
                'CODE' => 'information-security-conference-2027',
                'SECTION' => 'conferences',
                'DATE_START' => '2027-02-21 10:00:00',
                'DATE_END' => '2027-02-21 18:00:00',
                'LOCATION' => 'Новосибирск, конгресс-центр «Сибирь»',
                'PRICE' => 14000,
                'NEED_REGISTRATION' => 'Y',
                'REGISTRATION_URL' => '',
            ],
            [
                'NAME' => 'Семинар по экологичным городским инициативам',
                'CODE' => 'urban-ecology-seminar-2027',
                'SECTION' => 'seminars',
                'DATE_START' => '2027-02-25 10:00:00',
                'DATE_END' => '2027-02-25 14:00:00',
                'LOCATION' => 'Нижний Новгород, общественный центр «Среда»',
                'PRICE' => 0,
                'NEED_REGISTRATION' => 'N',
                'REGISTRATION_URL' => '',
            ],
            [
                'NAME' => 'Выставка робототехники и автоматизации',
                'CODE' => 'robotics-exhibition-2027',
                'SECTION' => 'exhibitions',
                'DATE_START' => '2027-03-01 10:00:00',
                'DATE_END' => '2027-03-02 18:00:00',
                'LOCATION' => 'Санкт-Петербург, технологический павильон',
                'PRICE' => 700,
                'NEED_REGISTRATION' => 'Y',
                'REGISTRATION_URL' => '',
            ],
            [
                'NAME' => 'Мастер-класс по публичным выступлениям',
                'CODE' => 'public-speaking-workshop-2027',
                'SECTION' => 'master-classes',
                'DATE_START' => '2027-03-05 10:00:00',
                'DATE_END' => '2027-03-05 15:00:00',
                'LOCATION' => 'Самара, образовательное пространство «Практика»',
                'PRICE' => 3000,
                'NEED_REGISTRATION' => 'Y',
                'REGISTRATION_URL' => '',
            ],
            [
                'NAME' => 'Деловой завтрак по развитию регионального туризма',
                'CODE' => 'regional-tourism-breakfast-2027',
                'SECTION' => 'business-breakfasts',
                'DATE_START' => '2027-03-09 10:00:00',
                'DATE_END' => '2027-03-09 13:00:00',
                'LOCATION' => 'Сочи, конференц-зал «Побережье»',
                'PRICE' => 2000,
                'NEED_REGISTRATION' => 'Y',
                'REGISTRATION_URL' => '',
            ],
            [
                'NAME' => 'Конференция по образовательным технологиям',
                'CODE' => 'education-technology-conference-2027',
                'SECTION' => 'conferences',
                'DATE_START' => '2027-03-13 10:00:00',
                'DATE_END' => '2027-03-13 18:00:00',
                'LOCATION' => 'Томск, университетский конгресс-холл',
                'PRICE' => 6500,
                'NEED_REGISTRATION' => 'Y',
                'REGISTRATION_URL' => '',
            ],
            [
                'NAME' => 'Семинар по управлению волонтёрскими проектами',
                'CODE' => 'volunteer-projects-seminar-2027',
                'SECTION' => 'seminars',
                'DATE_START' => '2027-03-17 10:00:00',
                'DATE_END' => '2027-03-17 14:00:00',
                'LOCATION' => 'Пермь, молодёжный центр «Команда»',
                'PRICE' => 0,
                'NEED_REGISTRATION' => 'N',
                'REGISTRATION_URL' => '',
            ],
            [
                'NAME' => 'Выставка локальных производителей и ремёсел',
                'CODE' => 'local-makers-exhibition-2027',
                'SECTION' => 'exhibitions',
                'DATE_START' => '2027-03-21 10:00:00',
                'DATE_END' => '2027-03-21 18:00:00',
                'LOCATION' => 'Тула, городской выставочный центр',
                'PRICE' => 0,
                'NEED_REGISTRATION' => 'N',
                'REGISTRATION_URL' => '',
            ],
            [
                'NAME' => 'Мастер-класс по визуализации данных',
                'CODE' => 'data-visualization-workshop-2027',
                'SECTION' => 'master-classes',
                'DATE_START' => '2027-03-25 10:00:00',
                'DATE_END' => '2027-03-25 16:00:00',
                'LOCATION' => 'Москва, учебный центр «Аналитика»',
                'PRICE' => 5500,
                'NEED_REGISTRATION' => 'Y',
                'REGISTRATION_URL' => '',
            ],
            [
                'NAME' => 'Деловой завтрак для руководителей HR-команд',
                'CODE' => 'hr-leaders-breakfast-2027',
                'SECTION' => 'business-breakfasts',
                'DATE_START' => '2027-03-29 10:00:00',
                'DATE_END' => '2027-03-29 13:00:00',
                'LOCATION' => 'Казань, деловое пространство «Контакт»',
                'PRICE' => 4000,
                'NEED_REGISTRATION' => 'Y',
                'REGISTRATION_URL' => '',
            ],
        ];
    }

    public function up()
    {
        if (!Loader::includeModule('iblock')) {
            throw new \RuntimeException('Модуль iblock не установлен');
        }
        $helper = $this->getHelperManager()->Iblock();
        $iblockId = $helper->getIblockId('events', 'CONTENT');
        if (!$iblockId) {
            throw new \RuntimeException('Сначала выполните миграцию Version20261026130000');
        }
        $sections = [];
        $rows = \CIBlockSection::GetList([], ['IBLOCK_ID' => $iblockId], false, ['ID', 'CODE']);
        while ($row = $rows->Fetch()) {
            $sections[$row['CODE']] = (int) $row['ID'];
        }
        $enumIds = [];
        $rows = \CIBlockPropertyEnum::GetList([], ['IBLOCK_ID' => $iblockId, 'CODE' => 'NEED_REGISTRATION']);
        while ($row = $rows->Fetch()) {
            $enumIds[$row['XML_ID']] = (int) $row['ID'];
        }
        $element = new \CIBlockElement();
        foreach ($this->getEvents() as $event) {
            if (!isset($sections[$event['SECTION']], $enumIds[$event['NEED_REGISTRATION']])) {
                throw new \RuntimeException('Не найдены категория или вариант регистрации для ' . $event['CODE']);
            }
            $existing = \CIBlockElement::GetList([], [
                'IBLOCK_ID' => $iblockId, '=CODE' => $event['CODE'], 'CHECK_PERMISSIONS' => 'N',
            ], false, ['nTopCount' => 1], ['ID'])->Fetch();
            if ($existing) {
                continue;
            }
            $properties = [
                'DATE_START' => ConvertTimeStamp(strtotime($event['DATE_START']), 'FULL'),
                'DATE_END' => $event['DATE_END'] ? ConvertTimeStamp(strtotime($event['DATE_END']), 'FULL') : false,
                'LOCATION' => $event['LOCATION'], 'PRICE' => $event['PRICE'],
                'NEED_REGISTRATION' => $enumIds[$event['NEED_REGISTRATION']],
                'REGISTRATION_URL' => $event['REGISTRATION_URL'],
            ];
            if (!$element->Add([
                'IBLOCK_ID' => $iblockId, 'NAME' => $event['NAME'], 'CODE' => $event['CODE'],
                'ACTIVE' => 'Y', 'SORT' => 500,
                'IBLOCK_SECTION_ID' => $sections[$event['SECTION']],
                'PREVIEW_TEXT' => 'Описание мероприятия: ' . $event['NAME'], 'PREVIEW_TEXT_TYPE' => 'text',
                'DETAIL_TEXT' => 'Полное описание мероприятия: ' . $event['NAME'], 'DETAIL_TEXT_TYPE' => 'text',
                'PROPERTY_VALUES' => $properties,
            ])) {
                throw new \RuntimeException('Ошибка создания ' . $event['CODE'] . ': ' . $element->LAST_ERROR);
            }
        }
    }

    public function down()
    {
        if (!Loader::includeModule('iblock')) {
            throw new \RuntimeException('Модуль iblock не установлен');
        }
        $iblockId = $this->getHelperManager()->Iblock()->getIblockId('events', 'CONTENT');
        if (!$iblockId) {
            return;
        }
        $rows = \CIBlockElement::GetList([], [
            'IBLOCK_ID' => $iblockId, '=CODE' => array_column($this->getEvents(), 'CODE'),
            'CHECK_PERMISSIONS' => 'N',
        ], false, false, ['ID', 'CODE']);
        while ($row = $rows->Fetch()) {
            if (!\CIBlockElement::Delete((int) $row['ID'])) {
                throw new \RuntimeException('Ошибка удаления ' . $row['CODE']);
            }
        }
    }
}
