<?php

namespace Sprint\Migration;

use Bitrix\Crm\Model\Dynamic\TypeTable;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\PhaseSemantics;
use Bitrix\Crm\StatusTable;
use Bitrix\Main\Loader;
use Bitrix\Main\UserTable;

class Version20261026140000 extends Version
{
    protected $description = 'Создание смарт-процесса «Заявки в СБ»';

    private const TYPE_CODE = 'request_sb';
    private const TYPE_TITLE = 'Заявки в СБ';

    public function up()
    {
        $this->loadModules();
        global $USER;
        $creatorId = (int) $USER->GetID();
        if ($creatorId <= 0) {
            $admin = UserTable::getList([
                'select' => ['ID'],
                'filter' => ['=ACTIVE' => 'Y', '=GROUPS.GROUP_ID' => 1],
                'order' => ['ID' => 'ASC'],
                'limit' => 1,
            ])->fetch();
            $creatorId = (int) ($admin['ID'] ?? 0);
        }
        $type = $this->createSmartProcessType(self::TYPE_CODE, self::TYPE_TITLE, $creatorId);
        $this->createElementFields($type['ENTITY_TYPE_ID']);
        $this->configureStages($type['ENTITY_TYPE_ID']);
        $this->verifyConfiguration($type['ENTITY_TYPE_ID']);
    }

    public function down()
    {
        $this->loadModules();
        $types = TypeTable::getList([
            'select' => ['ID', 'ENTITY_TYPE_ID'],
            'filter' => ['=CODE' => self::TYPE_CODE],
            'limit' => 2,
        ])->fetchAll();
        if (count($types) > 1) {
            throw new \RuntimeException('Найдено несколько процессов с кодом ' . self::TYPE_CODE);
        }
        if (!$types) {
            return;
        }
        $factory = Container::getInstance()->getFactory((int) $types[0]['ENTITY_TYPE_ID']);
        if ($factory === null || $factory->getItemsCount() > 0) {
            throw new \RuntimeException('Нельзя откатить миграцию: процесс недоступен или содержит заявки');
        }
        // Обработчикам удаления нужен загруженный объект с ENTITY_TYPE_ID.
        $type = TypeTable::getById((int) $types[0]['ID'])->fetchObject();
        $result = $type->delete();
        if (!$result->isSuccess()) {
            throw new \RuntimeException('Не удалось удалить процесс: ' . implode(', ', $result->getErrorMessages()));
        }
    }

    private function loadModules(): void
    {
        foreach (['crm', 'intranet'] as $module) {
            if (!Loader::includeModule($module)) {
                throw new \RuntimeException('Не удалось подключить модуль ' . $module);
            }
        }
    }

    private function createSmartProcessType(string $typeCode, string $typeName, int $creatorId): array
    {
        if ($creatorId <= 0 || !UserTable::getById($creatorId)->fetch()) {
            throw new \RuntimeException("Пользователь с ID {$creatorId} не существует.");
        }

        $types = TypeTable::getList([
            'select' => ['ID', 'ENTITY_TYPE_ID', 'IS_INITIALIZED', 'IS_AUTOMATION_ENABLED'],
            'filter' => ['=CODE' => $typeCode],
            'limit' => 2,
        ])->fetchAll();

        if (count($types) > 1) {
            throw new \RuntimeException("Найдено несколько процессов с кодом '{$typeCode}'. Проверьте дубликаты.");
        }
        if ($types) {
            $existing = $types[0];
            if (!in_array($existing['IS_INITIALIZED'], [true, 'Y', 1, '1'], true)) {
                throw new \RuntimeException(
                    "Создание процесса '{$typeCode}' не завершено (ID {$existing['ID']}). Проверьте предыдущий запуск."
                );
            }
            // Повторный запуск включает роботов и для ранее созданного процесса.
            if (!in_array($existing['IS_AUTOMATION_ENABLED'], [true, 'Y', 1, '1'], true)) {
                $update = TypeTable::update((int)$existing['ID'], [
                    'IS_AUTOMATION_ENABLED' => true,
                    'UPDATED_BY' => $creatorId,
                ]);
                if (!$update->isSuccess()) {
                    throw new \RuntimeException(
                        'Не удалось включить роботов: ' . implode(', ', $update->getErrorMessages())
                    );
                }
            }
            $update = TypeTable::update((int) $existing['ID'], [
                'TITLE' => $typeName,
                'UPDATED_BY' => $creatorId,
            ]);
            if (!$update->isSuccess()) {
                throw new \RuntimeException(implode(', ', $update->getErrorMessages()));
            }
            return [
                'ID' => (int)$existing['ID'],
                'ENTITY_TYPE_ID' => (int)$existing['ENTITY_TYPE_ID'],
                'CREATED' => false,
            ];
        }

        $name = TypeTable::generateName($typeName);
        if ($name === null) {
            throw new \RuntimeException('Не удалось сформировать техническое имя смарт-процесса.');
        }
        // onBeforeAdd использует исходный массив полей для имени таблицы.
        // Поэтому ENTITY_TYPE_ID передаём явно, до вызова обработчика.
        $entityTypeId = TypeTable::getNextAvailableEntityTypeId();
        if ($entityTypeId === null || $entityTypeId <= 0) {
            throw new \RuntimeException('Не удалось получить доступный идентификатор типа CRM.');
        }

        $result = TypeTable::add([
            'ENTITY_TYPE_ID' => $entityTypeId,
            'TITLE' => $typeName,
            'CODE' => $typeCode,
            'NAME' => $name,
            'CREATED_BY' => $creatorId,
            'UPDATED_BY' => $creatorId,
            'IS_CATEGORIES_ENABLED' => false,
            'IS_STAGES_ENABLED' => true,
            'IS_BEGIN_CLOSE_DATES_ENABLED' => true,
            'IS_OBSERVERS_ENABLED' => true,
            'IS_RECYCLEBIN_ENABLED' => true,
            'IS_CLIENT_ENABLED' => false,
            'IS_LINK_WITH_PRODUCTS_ENABLED' => false,
            'IS_MYCOMPANY_ENABLED' => false,
            'IS_DOCUMENTS_ENABLED' => false,
            'IS_SOURCE_ENABLED' => false,
            'IS_USE_IN_USERFIELD_ENABLED' => false,
            'IS_AUTOMATION_ENABLED' => true,
            'IS_BIZ_PROC_ENABLED' => false,
            'IS_SET_OPEN_PERMISSIONS' => false,
        ]);
        if (!$result->isSuccess()) {
            throw new \RuntimeException('Ошибка создания типа: ' . implode(', ', $result->getErrorMessages()));
        }

        $created = TypeTable::getById($result->getId())->fetch();
        if (!$created || !in_array($created['IS_INITIALIZED'], [true, 'Y', 1, '1'], true)) {
            throw new \RuntimeException('Запись типа создана, но инициализация смарт-процесса не завершена.');
        }
        return [
            'ID' => (int)$created['ID'],
            'ENTITY_TYPE_ID' => (int)$created['ENTITY_TYPE_ID'],
            'CREATED' => true,
        ];
    }

    private function configureStages(int $entityTypeId): void
    {
        $factory = Container::getInstance()->getFactory($entityTypeId);
        if ($factory === null) {
            throw new \RuntimeException('Фабрика смарт-процесса недоступна');
        }
        $categoryId = $factory->createDefaultCategoryIfNotExist()->getId();
        $entityId = $factory->getStagesEntityId($categoryId);
        if ($entityId === null) {
            throw new \RuntimeException('Справочник стадий недоступен');
        }
        $manager = new \CCrmStatus($entityId);
        $definitions = $this->getStageDefinitions();
        $desiredIds = array_map([$manager, 'addPrefixToStatusId'], array_keys($definitions));
        $existing = StatusTable::getList([
            'select' => ['ID', 'STATUS_ID'],
            'filter' => ['=ENTITY_ID' => $entityId],
            'order' => ['SORT' => 'ASC', 'ID' => 'ASC'],
        ])->fetchAll();
        // До изменений проверяем, что удаляемые стадии не используются заявками.
        foreach ($existing as $stage) {
            if (!in_array($stage['STATUS_ID'], $desiredIds, true)
                && $factory->getItemsCount(['=STAGE_ID' => $stage['STATUS_ID']]) > 0
            ) {
                throw new \RuntimeException('Нельзя удалить стадию с заявками: ' . $stage['STATUS_ID']);
            }
        }
        $byStatusId = array_column($existing, 'ID', 'STATUS_ID');
        // Сначала переносим успешную стадию в конец: CRM запрещает
        // добавлять промежуточные стадии после её текущей позиции.
        $stageCodes = array_merge(['SUCCESS'], array_diff(array_keys($definitions), ['SUCCESS']));
        foreach ($stageCodes as $code) {
            [$name, $sort, $semantics, $color] = $definitions[$code];
            $statusId = $manager->addPrefixToStatusId($code);
            $fields = [
                'NAME' => $name,
                'SORT' => $sort,
                'SEMANTICS' => $semantics,
                'COLOR' => $color,
                'SYSTEM' => in_array($code, ['NEW', 'SUCCESS'], true) ? 'Y' : 'N',
            ];
            if (isset($byStatusId[$statusId])) {
                $result = StatusTable::update((int) $byStatusId[$statusId], $fields);
            } else {
                $result = StatusTable::add(array_merge($fields, [
                    'ENTITY_ID' => $entityId,
                    'STATUS_ID' => $statusId,
                    'CATEGORY_ID' => $categoryId,
                ]));
            }
            if (!$result->isSuccess()) {
                throw new \RuntimeException('Не удалось настроить стадию ' . $name . ': ' . implode(', ', $result->getErrorMessages()));
            }
        }
        foreach ($existing as $stage) {
            if (!in_array($stage['STATUS_ID'], $desiredIds, true)) {
                $result = StatusTable::delete((int) $stage['ID']);
                if (!$result->isSuccess()) {
                    throw new \RuntimeException(implode(', ', $result->getErrorMessages()));
                }
            }
        }
        $factory->purgeStagesCache();
    }

    private function getStageDefinitions(): array
    {
        return [
            'NEW' => ['Новая', 100, PhaseSemantics::PROCESS, '#39A8EF'],
            'APPROVAL' => ['На согласовании', 200, PhaseSemantics::PROCESS, '#A8ADF0'],
            'IN_PROGRESS' => ['В работе', 300, PhaseSemantics::PROCESS, '#2FC6F6'],
            'REVIEW' => ['На проверке', 400, PhaseSemantics::PROCESS, '#FFA900'],
            'EXECUTION' => ['В исполнении', 500, PhaseSemantics::PROCESS, '#FFCE54'],
            'SUCCESS' => ['Завершена', 600, PhaseSemantics::SUCCESS, '#7BD500'],
        ];
    }

    private function createElementFields(int $entityTypeId): void
    {
        $factory = Container::getInstance()->getFactory($entityTypeId);
        if ($factory === null) {
            throw new \RuntimeException('Фабрика смарт-процесса недоступна.');
        }

        $userFieldEntityId = $factory->getUserFieldEntityId();

        $descriptionFieldId = $this->ensureUserField($userFieldEntityId, [
            'FIELD_NAME' => 'UF_SB_DESCRIPTION',
            'USER_TYPE_ID' => 'string',
            'XML_ID' => 'SB_REQUEST_DESCRIPTION',

            'SORT' => 100,
            'MULTIPLE' => 'N',
            'MANDATORY' => 'Y',
            'SHOW_FILTER' => 'N',
            'SHOW_IN_LIST' => 'Y',
            'EDIT_IN_LIST' => 'Y',
            'IS_SEARCHABLE' => 'Y',

            'EDIT_FORM_LABEL' => [
                'ru' => 'Описание заявки',
                'en' => 'Request description',
            ],
            'LIST_COLUMN_LABEL' => [
                'ru' => 'Описание заявки',
                'en' => 'Request description',
            ],

            'SETTINGS' => [
                'SIZE' => 60,
                'ROWS' => 5,
                'DEFAULT_VALUE' => '',
            ],
        ]);

        $priorityFieldId = $this->ensureUserField($userFieldEntityId, [
            'FIELD_NAME' => 'UF_SB_PRIORITY',
            'USER_TYPE_ID' => 'enumeration',
            'XML_ID' => 'SB_REQUEST_PRIORITY',

            'SORT' => 200,
            'MULTIPLE' => 'N',
            'MANDATORY' => 'Y',
            'SHOW_FILTER' => 'I',
            'SHOW_IN_LIST' => 'Y',
            'EDIT_IN_LIST' => 'Y',
            'IS_SEARCHABLE' => 'N',

            'EDIT_FORM_LABEL' => [
                'ru' => 'Приоритет',
                'en' => 'Priority',
            ],
            'LIST_COLUMN_LABEL' => [
                'ru' => 'Приоритет',
                'en' => 'Priority',
            ],
            'LIST_FILTER_LABEL' => [
                'ru' => 'Приоритет',
                'en' => 'Priority',
            ],

            'SETTINGS' => [
                'DISPLAY' => 'LIST',
                'LIST_HEIGHT' => 1,
            ],
        ]);

        $existingOptions = [];

        $optionsResult = \CUserFieldEnum::GetList([], [
            'USER_FIELD_ID' => $priorityFieldId,
        ]);

        while ($option = $optionsResult->Fetch()) {
            $existingOptions[$option['XML_ID']] = true;
        }

        $desiredOptions = [
            'LOW' => [
                'VALUE' => 'Низкий',
                'SORT' => 100,
                'DEF' => 'N',
            ],
            'NORMAL' => [
                'VALUE' => 'Обычный',
                'SORT' => 200,
                'DEF' => 'Y',
            ],
            'HIGH' => [
                'VALUE' => 'Высокий',
                'SORT' => 300,
                'DEF' => 'N',
            ],
        ];

        $newOptions = [];

        foreach ($desiredOptions as $xmlId => $option) {
            if (isset($existingOptions[$xmlId])) {
                continue;
            }

            $newOptions['n' . count($newOptions)] = array_merge(
                $option,
                ['XML_ID' => $xmlId]
            );
        }

        if ($newOptions) {
            $enumManager = new \CUserFieldEnum();

            if (!$enumManager->SetEnumValues($priorityFieldId, $newOptions)) {
                $exception = $GLOBALS['APPLICATION']->GetException();

                throw new \RuntimeException(
                    'Не удалось добавить варианты приоритета: '
                    . ($exception ? $exception->GetString() : 'неизвестная ошибка')
                );
            }
        }

        $requestorFieldId = $this->ensureUserField($userFieldEntityId, [
            'FIELD_NAME' => 'UF_SB_REQUESTOR',
            'USER_TYPE_ID' => 'employee',
            'XML_ID' => 'SB_REQUEST_REQUESTOR',

            'SORT' => 300,
            'MULTIPLE' => 'N',
            'MANDATORY' => 'Y',
            'SHOW_FILTER' => 'N',
            'SHOW_IN_LIST' => 'Y',
            'EDIT_IN_LIST' => 'Y',
            'IS_SEARCHABLE' => 'Y',

            'EDIT_FORM_LABEL' => [
                'ru' => 'Заявитель',
                'en' => 'Requestor',
            ],
            'LIST_COLUMN_LABEL' => [
                'ru' => 'Заявитель',
                'en' => 'Requestor',
            ]
        ]);


        $includesFieldId = $this->ensureUserField($userFieldEntityId, [
            'FIELD_NAME' => 'UF_SB_INCLUDES',
            'USER_TYPE_ID' => 'file',
            'XML_ID' => 'SB_REQUEST_INCLUDES',

            'SORT' => 400,
            'MULTIPLE' => 'Y',
            'MANDATORY' => 'N',
            'SHOW_FILTER' => 'N',
            'SHOW_IN_LIST' => 'Y',
            'EDIT_IN_LIST' => 'Y',
            'IS_SEARCHABLE' => 'Y',

            'EDIT_FORM_LABEL' => [
                'ru' => 'Вложения',
                'en' => 'Includes',
            ],
            'LIST_COLUMN_LABEL' => [
                'ru' => 'Вложения',
                'en' => 'Includes',
            ]
        ]);

        $timeSolutionFieldId = $this->ensureUserField($userFieldEntityId, [
            'FIELD_NAME' => 'UF_SB_TIME_SOLUTION',
            'USER_TYPE_ID' => 'datetime',
            'XML_ID' => 'SB_REQUEST_TIME_SOLUTION',

            'SORT' => 500,
            'MULTIPLE' => 'N',
            'MANDATORY' => 'N',
            'SHOW_FILTER' => 'N',
            'SHOW_IN_LIST' => 'Y',
            'EDIT_IN_LIST' => 'Y',
            'IS_SEARCHABLE' => 'Y',

            'EDIT_FORM_LABEL' => [
                'ru' => 'Желаемый срок решения',
                'en' => 'Time solution',
            ],
            'LIST_COLUMN_LABEL' => [
                'ru' => 'Желаемый срок решения',
                'en' => 'Time solution',
            ]
        ]);

        $resultSolutionFieldId = $this->ensureUserField($userFieldEntityId, [
            'FIELD_NAME' => 'UF_SB_RESULT',
            'USER_TYPE_ID' => 'string',
            'XML_ID' => 'SB_REQUEST_RESULT',

            'SORT' => 600,
            'MULTIPLE' => 'N',
            'MANDATORY' => 'N',
            'SHOW_FILTER' => 'N',
            'SHOW_IN_LIST' => 'Y',
            'EDIT_IN_LIST' => 'Y',
            'IS_SEARCHABLE' => 'Y',

            'EDIT_FORM_LABEL' => [
                'ru' => 'Результат решения',
                'en' => 'Result solution',
            ],
            'LIST_COLUMN_LABEL' => [
                'ru' => 'Результат решения',
                'en' => 'Result solution',
            ],

            'SETTINGS' => [
                'SIZE' => 60,
                'ROWS' => 5,
                'DEFAULT_VALUE' => '',
            ],
        ]);
        $factory->clearFieldsCollectionCache();
    }

    private function ensureUserField(string $entityId, array $definition): int
    {
        $existing = \CUserTypeEntity::GetList([], [
            'ENTITY_ID' => $entityId,
            'FIELD_NAME' => $definition['FIELD_NAME'],
        ])->Fetch();

        if ($existing) {
            if (
                $existing['USER_TYPE_ID'] !== $definition['USER_TYPE_ID']
                || $existing['MULTIPLE'] !== $definition['MULTIPLE']
            ) {
                throw new \RuntimeException(
                    "Поле {$definition['FIELD_NAME']} уже существует "
                    . 'с другим типом или множественностью.'
                );
            }
            // В CRM этот флаг управляет редактированием также в карточке.
            // Обновляем существующие поля при повторном запуске установки.
            if ($existing['EDIT_IN_LIST'] !== $definition['EDIT_IN_LIST']) {
                $manager = new \CUserTypeEntity();
                if (!$manager->Update((int)$existing['ID'], [
                    'EDIT_IN_LIST' => $definition['EDIT_IN_LIST'],
                ])) {
                    $exception = $GLOBALS['APPLICATION']->GetException();
                    throw new \RuntimeException(
                        "Не удалось разрешить редактирование поля {$definition['FIELD_NAME']}: "
                        . ($exception ? $exception->GetString() : 'неизвестная ошибка')
                    );
                }
            }
            return (int)$existing['ID'];
        }

        $manager = new \CUserTypeEntity();

        $fieldId = $manager->Add(
            array_merge($definition, ['ENTITY_ID' => $entityId])
        );

        if ($fieldId === false) {
            $exception = $GLOBALS['APPLICATION']->GetException();

            throw new \RuntimeException(
                "Не удалось создать поле {$definition['FIELD_NAME']}: "
                . ($exception ? $exception->GetString() : 'неизвестная ошибка')
            );
        }

        return (int)$fieldId;
    }

    /** Проверяет сохранённые поля, варианты приоритета и стадии перед сообщением об успехе. */
    private function verifyConfiguration(int $entityTypeId): void
    {
        $factory = Container::getInstance()->getFactory($entityTypeId);
        if ($factory === null) {
            throw new \RuntimeException('Фабрика недоступна при итоговой проверке.');
        }

        $expectedFields = [
            'UF_SB_DESCRIPTION' => ['string', 'N', 'Y'],
            'UF_SB_PRIORITY' => ['enumeration', 'N', 'Y'],
            'UF_SB_REQUESTOR' => ['employee', 'N', 'Y'],
            'UF_SB_INCLUDES' => ['file', 'Y', 'N'],
            'UF_SB_TIME_SOLUTION' => ['datetime', 'N', 'N'],
            'UF_SB_RESULT' => ['string', 'N', 'N'],
        ];
        $fields = [];
        $result = \CUserTypeEntity::GetList([], [
            'ENTITY_ID' => $factory->getUserFieldEntityId(),
        ]);
        while ($field = $result->Fetch()) {
            $fields[$field['FIELD_NAME']] = $field;
        }
        foreach ($expectedFields as $name => [$type, $multiple, $mandatory]) {
            $field = $fields[$name] ?? null;
            if ($field === null
                || $field['USER_TYPE_ID'] !== $type
                || $field['MULTIPLE'] !== $multiple
                || $field['MANDATORY'] !== $mandatory
                || $field['EDIT_IN_LIST'] !== 'Y'
            ) {
                throw new \RuntimeException("Поле '{$name}' отсутствует или его настройки отличаются от ожидаемых.");
            }
        }

        $options = [];
        $result = \CUserFieldEnum::GetList([], [
            'USER_FIELD_ID' => (int)$fields['UF_SB_PRIORITY']['ID'],
        ]);
        while ($option = $result->Fetch()) {
            $options[$option['XML_ID']] = $option;
        }
        foreach (['LOW' => 'Низкий', 'NORMAL' => 'Обычный', 'HIGH' => 'Высокий'] as $code => $label) {
            if (!isset($options[$code]) || $options[$code]['VALUE'] !== $label
                || $options[$code]['DEF'] !== ($code === 'NORMAL' ? 'Y' : 'N')
            ) {
                throw new \RuntimeException("Вариант приоритета '{$code}' отсутствует или настроен неверно.");
            }
        }

        $categoryId = $factory->createDefaultCategoryIfNotExist()->getId();
        $manager = new \CCrmStatus($factory->getStagesEntityId($categoryId));
        $stages = [];
        $result = \CCrmStatus::GetList([], [
            'ENTITY_ID' => $factory->getStagesEntityId($categoryId),
        ]);
        while ($stage = $result->Fetch()) {
            $stages[$stage['STATUS_ID']] = $stage;
        }
        $expectedStages = $this->getStageDefinitions();
        if (count($stages) !== count($expectedStages)) {
            throw new \RuntimeException('Ожидалось ровно шесть стадий');
        }
        foreach ($expectedStages as $code => [$name, $sort, $semantics]) {
            $stage = $stages[$manager->addPrefixToStatusId($code)] ?? null;
            $actualSemantics = $stage['SEMANTICS'] ?? '';
            if ($actualSemantics === '') {
                $actualSemantics = PhaseSemantics::PROCESS;
            }
            if ($stage === null || $stage['NAME'] !== $name
                || (int)$stage['SORT'] !== $sort || $actualSemantics !== $semantics
            ) {
                throw new \RuntimeException("Стадия '{$code}' отсутствует или настроена неверно.");
            }
        }
    }
}
