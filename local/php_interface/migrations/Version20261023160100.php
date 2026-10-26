<?php

namespace Sprint\Migration;

use Bitrix\Main\Loader;
use Bitrix\Iblock\IblockTable;

class Version20261023160100 extends Version
{
    protected $description = 'Добавление 10 демонстрационных вакансий';

    public function up()
    {
        if (!Loader::includeModule('iblock')) {
            throw new \RuntimeException('Модуль iblock не установлен');
        }

        $iblock = IblockTable::getList([
            'select' => ['ID'],
            'filter' => ['=IBLOCK_TYPE_ID' => 'hr', '=CODE' => 'vacancies'],
        ])->fetch();
        if (!$iblock) {
            throw new \RuntimeException('Сначала выполните миграцию Version20261023160000');
        }
        $iblockId = (int) $iblock['ID'];

        $sections = [];
        $result = \CIBlockSection::GetList([], ['IBLOCK_ID' => $iblockId], false, ['ID', 'CODE']);
        while ($row = $result->Fetch()) {
            $sections[$row['CODE']] = (int) $row['ID'];
        }

        // CIBlockElement ожидает ID вариантов списка, а не их XML_ID.
        $enumIds = [];
        $result = \CIBlockPropertyEnum::GetList([], [
            'IBLOCK_ID' => $iblockId,
            'CODE' => ['CITY', 'EMPLOYMENT_TYPE', 'EXPERIENCE'],
        ]);
        while ($row = $result->Fetch()) {
            $enumIds[$row['PROPERTY_CODE']][$row['XML_ID']] = (int) $row['ID'];
        }

        $vacancies = [
            [
                'NAME' => 'Senior PHP-разработчик',
                'CODE' => 'senior-php-developer',
                'SECTION' => 'it',
                'CITY' => 'MSK',
                'SALARY_FROM' => 250000,
                'SALARY_TO' => 350000,
                'EMPLOYMENT_TYPE' => 'FULL',
                'EXPERIENCE' => '6+',
                'PREVIEW_TEXT' => 'Ищем опытного PHP-разработчика для работы над высоконагруженным проектом.',
            ],
            [
                'NAME' => 'Frontend-разработчик (React)',
                'CODE' => 'frontend-react-developer',
                'SECTION' => 'it',
                'CITY' => 'SPB',
                'SALARY_FROM' => 180000,
                'SALARY_TO' => 250000,
                'EMPLOYMENT_TYPE' => 'FULL',
                'EXPERIENCE' => '3-6',
                'PREVIEW_TEXT' => 'Разработка интерфейсов для B2B-платформы на React.',
            ],
            [
                'NAME' => 'Интернет-маркетолог',
                'CODE' => 'internet-marketer',
                'SECTION' => 'marketing',
                'CITY' => 'MSK',
                'SALARY_FROM' => 120000,
                'SALARY_TO' => 180000,
                'EMPLOYMENT_TYPE' => 'FULL',
                'EXPERIENCE' => '1-3',
                'PREVIEW_TEXT' => 'Управление рекламными кампаниями и продвижение в соцсетях.',
            ],
            [
                'NAME' => 'Менеджер по продажам',
                'CODE' => 'sales-manager',
                'SECTION' => 'sales',
                'CITY' => 'MSK',
                'SALARY_FROM' => 80000,
                'SALARY_TO' => 150000,
                'EMPLOYMENT_TYPE' => 'FULL',
                'EXPERIENCE' => '1-3',
                'PREVIEW_TEXT' => 'Активные продажи B2B-решений.',
            ],
            [
                'NAME' => 'Финансовый аналитик',
                'CODE' => 'financial-analyst',
                'SECTION' => 'finance',
                'CITY' => 'MSK',
                'SALARY_FROM' => 150000,
                'SALARY_TO' => 200000,
                'EMPLOYMENT_TYPE' => 'FULL',
                'EXPERIENCE' => '3-6',
                'PREVIEW_TEXT' => 'Анализ финансовых показателей, построение моделей.',
            ],
            [
                'NAME' => 'HR-менеджер',
                'CODE' => 'hr-manager',
                'SECTION' => 'hr',
                'CITY' => 'SPB',
                'SALARY_FROM' => 90000,
                'SALARY_TO' => 130000,
                'EMPLOYMENT_TYPE' => 'FULL',
                'EXPERIENCE' => '1-3',
                'PREVIEW_TEXT' => 'Подбор персонала, адаптация, кадровое делопроизводство.',
            ],
            [
                'NAME' => 'UX/UI дизайнер',
                'CODE' => 'ux-ui-designer',
                'SECTION' => 'design',
                'CITY' => 'REMOTE',
                'SALARY_FROM' => 140000,
                'SALARY_TO' => 200000,
                'EMPLOYMENT_TYPE' => 'REMOTE',
                'EXPERIENCE' => '3-6',
                'PREVIEW_TEXT' => 'Проектирование интерфейсов для веб и мобильных приложений.',
            ],
            [
                'NAME' => 'Стажёр-разработчик',
                'CODE' => 'intern-developer',
                'SECTION' => 'it',
                'CITY' => 'KZN',
                'SALARY_FROM' => 40000,
                'SALARY_TO' => 60000,
                'EMPLOYMENT_TYPE' => 'INTERN',
                'EXPERIENCE' => 'NO',
                'PREVIEW_TEXT' => 'Стажировка для начинающих разработчиков с обучением.',
            ],
            [
                'NAME' => 'Product Manager',
                'CODE' => 'product-manager',
                'SECTION' => 'it',
                'CITY' => 'MSK',
                'SALARY_FROM' => 200000,
                'SALARY_TO' => 300000,
                'EMPLOYMENT_TYPE' => 'FULL',
                'EXPERIENCE' => '3-6',
                'PREVIEW_TEXT' => 'Управление продуктовой линейкой, развитие B2B-платформы.',
            ],
            [
                'NAME' => 'SMM-специалист',
                'CODE' => 'smm-specialist',
                'SECTION' => 'marketing',
                'CITY' => 'REMOTE',
                'SALARY_FROM' => 60000,
                'SALARY_TO' => 90000,
                'EMPLOYMENT_TYPE' => 'PART',
                'EXPERIENCE' => '1-3',
                'PREVIEW_TEXT' => 'Ведение соцсетей, создание контента, работа с блогерами.',
            ],
        ];

        // Проверяем все ссылки до добавления первого элемента.
        foreach ($vacancies as $vacancy) {
            if (!isset($sections[$vacancy['SECTION']])) {
                throw new \RuntimeException('Не найден отдел: ' . $vacancy['SECTION']);
            }
            foreach (['CITY', 'EMPLOYMENT_TYPE', 'EXPERIENCE'] as $code) {
                if (!isset($enumIds[$code][$vacancy[$code]])) {
                    throw new \RuntimeException('Не найден вариант ' . $code . ': ' . $vacancy[$code]);
                }
            }
        }

        $element = new \CIBlockElement();
        foreach ($vacancies as $vacancy) {
            $existing = \CIBlockElement::GetList([], [
                'IBLOCK_ID' => $iblockId,
                '=CODE' => $vacancy['CODE'],
                'CHECK_PERMISSIONS' => 'N',
            ], false, ['nTopCount' => 1], ['ID'])->Fetch();
            if ($existing) {
                continue;
            }

            $properties = [
                'SALARY_FROM' => $vacancy['SALARY_FROM'],
                'SALARY_TO' => $vacancy['SALARY_TO'],
                'DATE_CLOSE' => ConvertTimeStamp(strtotime('+30 days'), 'FULL'),
            ];
            foreach (['CITY', 'EMPLOYMENT_TYPE', 'EXPERIENCE'] as $code) {
                $properties[$code] = $enumIds[$code][$vacancy[$code]];
            }

            $id = $element->Add([
                'IBLOCK_ID' => $iblockId,
                'NAME' => $vacancy['NAME'],
                'CODE' => $vacancy['CODE'],
                'ACTIVE' => 'Y',
                'SORT' => 500,
                'IBLOCK_SECTION_ID' => $sections[$vacancy['SECTION']],
                'PREVIEW_TEXT' => $vacancy['PREVIEW_TEXT'],
                'PREVIEW_TEXT_TYPE' => 'text',
                'DETAIL_TEXT' => $vacancy['PREVIEW_TEXT'],
                'DETAIL_TEXT_TYPE' => 'text',
                'PROPERTY_VALUES' => $properties,
            ]);
            if (!$id) {
                throw new \RuntimeException('Ошибка создания ' . $vacancy['CODE'] . ': ' . $element->LAST_ERROR);
            }
        }
    }

    public function down()
    {
        if (!Loader::includeModule('iblock')) {
            throw new \RuntimeException('Модуль iblock не установлен');
        }

        $iblock = IblockTable::getList([
            'select' => ['ID'],
            'filter' => ['=IBLOCK_TYPE_ID' => 'hr', '=CODE' => 'vacancies'],
        ])->fetch();
        if (!$iblock) {
            return;
        }

        $codes = [
            'senior-php-developer',
            'frontend-react-developer',
            'internet-marketer',
            'sales-manager',
            'financial-analyst',
            'hr-manager',
            'ux-ui-designer',
            'intern-developer',
            'product-manager',
            'smm-specialist',
        ];
        $result = \CIBlockElement::GetList([], [
            'IBLOCK_ID' => (int) $iblock['ID'],
            '=CODE' => $codes,
            'CHECK_PERMISSIONS' => 'N',
        ], false, false, ['ID', 'CODE']);

        while ($element = $result->Fetch()) {
            if (!\CIBlockElement::Delete((int) $element['ID'])) {
                throw new \RuntimeException('Ошибка удаления вакансии ' . $element['CODE']);
            }
        }
    }
}
