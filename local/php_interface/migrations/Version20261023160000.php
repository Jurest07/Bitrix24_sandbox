<?php

namespace Sprint\Migration;

use Bitrix\Main\Loader;

class Version20261023160000 extends Version
{
    protected $description = 'Создание типа HR, инфоблока вакансий, свойств и отделов';

    public function up()
    {
        if (!Loader::includeModule('iblock')) {
            throw new \RuntimeException('Модуль iblock не установлен');
        }

        $helper = $this->getHelperManager()->Iblock();
        $helper->addIblockTypeIfNotExists([
            'ID' => 'hr',
            'SECTIONS' => 'Y',
            'IN_RSS' => 'N',
            'SORT' => 500,
            'LANG' => [
                'ru' => [
                    'NAME' => 'HR-портал',
                    'SECTION_NAME' => 'Отделы',
                    'ELEMENT_NAME' => 'Вакансии',
                ],
                'en' => [
                    'NAME' => 'HR-портал',
                    'SECTION_NAME' => 'Departments',
                    'ELEMENT_NAME' => 'Vacancies',
                ],
            ],
        ]);

        $fields = [
            'NAME' => 'Вакансии',
            'CODE' => 'vacancies',
            'API_CODE' => 'Vacancies',
            'IBLOCK_TYPE_ID' => 'hr',
            'LID' => ['s1'],
            'ACTIVE' => 'Y',
            'SORT' => 500,
            'VERSION' => 2,
            'ELEMENTS_NAME' => 'Вакансии',
            'ELEMENT_NAME' => 'Вакансия',
            'SECTIONS_NAME' => 'Отделы',
            'SECTION_NAME' => 'Отдел',
            'INDEX_ELEMENT' => 'Y',
            'INDEX_SECTION' => 'Y',
            'FIELDS' => [
                'SECTION_CODE' => [
                    'IS_REQUIRED' => 'N',
                    'DEFAULT_VALUE' => [
                        'UNIQUE' => 'Y',
                        'TRANSLITERATION' => 'Y',
                        'TRANS_LEN' => 100,
                        'TRANS_CASE' => 'L',
                        'TRANS_SPACE' => '-',
                        'TRANS_OTHER' => '-',
                        'TRANS_EAT' => 'Y',
                    ],
                ],
                'ELEMENT_CODE' => [
                    'IS_REQUIRED' => 'N',
                    'DEFAULT_VALUE' => [
                        'UNIQUE' => 'Y',
                        'TRANSLITERATION' => 'Y',
                        'TRANS_LEN' => 100,
                        'TRANS_CASE' => 'L',
                        'TRANS_SPACE' => '-',
                        'TRANS_OTHER' => '-',
                        'TRANS_EAT' => 'Y',
                    ],
                ],
            ],
        ];
        $existing = $helper->getIblock('vacancies', 'hr');
        if ($existing) {
            $iblockId = (int) $existing['ID'];
            if (empty($existing['API_CODE'])) {
                $iblock = new \CIBlock();
                if (!$iblock->Update($iblockId, ['API_CODE' => $fields['API_CODE']])) {
                    throw new \RuntimeException($iblock->LAST_ERROR);
                }
            }
        } else {
            // Используем исходные настройки, без дополнительных прав хелпера.
            $iblock = new \CIBlock();
            $iblockId = $iblock->Add($fields);
            if (!$iblockId) {
                throw new \RuntimeException($iblock->LAST_ERROR);
            }
            $iblockId = (int) $iblockId;
        }

        $properties = [
            [
                'CODE' => 'CITY',
                'NAME' => 'Город',
                'PROPERTY_TYPE' => 'L',
                'IS_REQUIRED' => 'Y',
                'SORT' => 100,
                'VALUES' => [
                    ['VALUE' => 'Москва', 'XML_ID' => 'MSK', 'SORT' => 100],
                    ['VALUE' => 'Санкт-Петербург', 'XML_ID' => 'SPB', 'SORT' => 200],
                    ['VALUE' => 'Казань', 'XML_ID' => 'KZN', 'SORT' => 300],
                    ['VALUE' => 'Новосибирск', 'XML_ID' => 'NSK', 'SORT' => 400],
                    ['VALUE' => 'Екатеринбург', 'XML_ID' => 'EKB', 'SORT' => 500],
                    ['VALUE' => 'Удалённо', 'XML_ID' => 'REMOTE', 'SORT' => 600],
                ],
            ],
            [
                'CODE' => 'SALARY_FROM',
                'NAME' => 'Зарплата от',
                'PROPERTY_TYPE' => 'N',
                'IS_REQUIRED' => 'N',
                'SORT' => 200,
            ],
            [
                'CODE' => 'SALARY_TO',
                'NAME' => 'Зарплата до',
                'PROPERTY_TYPE' => 'N',
                'IS_REQUIRED' => 'N',
                'SORT' => 300,
            ],
            [
                'CODE' => 'EMPLOYMENT_TYPE',
                'NAME' => 'Тип занятости',
                'PROPERTY_TYPE' => 'L',
                'IS_REQUIRED' => 'Y',
                'SORT' => 400,
                'VALUES' => [
                    ['VALUE' => 'Полная', 'XML_ID' => 'FULL', 'SORT' => 100],
                    ['VALUE' => 'Частичная', 'XML_ID' => 'PART', 'SORT' => 200],
                    ['VALUE' => 'Удалённая', 'XML_ID' => 'REMOTE', 'SORT' => 300],
                    ['VALUE' => 'Стажировка', 'XML_ID' => 'INTERN', 'SORT' => 400],
                ],
            ],
            [
                'CODE' => 'EXPERIENCE',
                'NAME' => 'Опыт работы',
                'PROPERTY_TYPE' => 'L',
                'IS_REQUIRED' => 'Y',
                'SORT' => 500,
                'VALUES' => [
                    ['VALUE' => 'Без опыта', 'XML_ID' => 'NO', 'SORT' => 100],
                    ['VALUE' => '1-3 года', 'XML_ID' => '1-3', 'SORT' => 200],
                    ['VALUE' => '3-6 лет', 'XML_ID' => '3-6', 'SORT' => 300],
                    ['VALUE' => '6+ лет', 'XML_ID' => '6+', 'SORT' => 400],
                ],
            ],
            [
                'CODE' => 'DATE_CLOSE',
                'NAME' => 'Дата закрытия',
                'PROPERTY_TYPE' => 'S',
                'USER_TYPE' => 'DateTime',
                'IS_REQUIRED' => 'N',
                'SORT' => 600,
            ],
            [
                'CODE' => 'RESPONSIBILITIES',
                'NAME' => 'Обязанности',
                'PROPERTY_TYPE' => 'S',
                'USER_TYPE' => 'HTML',
                'IS_REQUIRED' => 'N',
                'SORT' => 700,
            ],
            [
                'CODE' => 'REQUIREMENTS',
                'NAME' => 'Требования',
                'PROPERTY_TYPE' => 'S',
                'USER_TYPE' => 'HTML',
                'IS_REQUIRED' => 'N',
                'SORT' => 800,
            ],
            [
                'CODE' => 'CONDITIONS',
                'NAME' => 'Условия',
                'PROPERTY_TYPE' => 'S',
                'USER_TYPE' => 'HTML',
                'IS_REQUIRED' => 'N',
                'SORT' => 900,
            ],
        ];

        foreach ($properties as $property) {
            $helper->addPropertyIfNotExists($iblockId, array_merge([
                'ACTIVE' => 'Y',
                'MULTIPLE' => 'N',
                'FILTRABLE' => 'Y',
            ], $property));
        }

        $departments = [
            ['NAME' => 'IT-отдел', 'CODE' => 'it', 'SORT' => 100],
            ['NAME' => 'Маркетинг', 'CODE' => 'marketing', 'SORT' => 200],
            ['NAME' => 'Продажи', 'CODE' => 'sales', 'SORT' => 300],
            ['NAME' => 'Финансы', 'CODE' => 'finance', 'SORT' => 400],
            ['NAME' => 'HR-отдел', 'CODE' => 'hr', 'SORT' => 500],
            ['NAME' => 'Дизайн', 'CODE' => 'design', 'SORT' => 600],
        ];

        foreach ($departments as $department) {
            $helper->addSectionIfNotExists($iblockId, array_merge([
                'ACTIVE' => 'Y',
            ], $department));
        }
    }

    public function down()
    {
        if (!Loader::includeModule('iblock')) {
            throw new \RuntimeException('Модуль iblock не установлен');
        }

        $iblock = $this->getHelperManager()->Iblock()->getIblock('vacancies', 'hr');
        if (!$iblock) {
            return;
        }

        if (!\CIBlock::Delete((int) $iblock['ID'])) {
            throw new \RuntimeException('Ошибка удаления инфоблока вакансий');
        }
    }
}
