<?php
namespace Sprint\Migration;

use Bitrix\Highloadblock\HighloadBlockTable;
use Bitrix\Main\Application;
use Bitrix\Main\Loader;

class Version20261026150000 extends Version
{
    protected $description = 'Журнал интеграции: highload-блок, поля и индексы';

    public function up()
    {
        if (!Loader::includeModule('highloadblock')) {
            throw new \RuntimeException('Требуется модуль highloadblock');
        }
        $block = HighloadBlockTable::getList(['filter' => ['=NAME' => 'IntegrationLog']])->fetch();
        if ($block && $block['TABLE_NAME'] !== 'integration_log') {
            throw new \RuntimeException('Имя IntegrationLog занято другой таблицей');
        }
        if (!$block) {
            $result = HighloadBlockTable::add(['NAME' => 'IntegrationLog', 'TABLE_NAME' => 'integration_log']);
            if (!$result->isSuccess()) {
                throw new \RuntimeException(implode('; ', $result->getErrorMessages()));
            }
            $id = (int)$result->getId();
        } else {
            $id = (int)$block['ID'];
        }
        $entity = 'HLBLOCK_' . $id;
        $fields = [
            'UF_OCCURRED_AT' => ['datetime', 'Дата и время'],
            'UF_LEVEL' => ['string', 'Уровень'],
            'UF_SOURCE_IP' => ['string', 'IP-адрес источника'],
            'UF_MODULE_ID' => ['string', 'ID модуля'],
            'UF_AUTHOR' => ['string', 'Автор лога'],
            'UF_MESSAGE' => ['string', 'Текст лога'],
            'UF_HASH' => ['string', 'Ключ импорта'],
        ];
        $sort = 100;
        foreach ($fields as $name => [$type, $label]) {
            $existing = \CUserTypeEntity::GetList([], ['ENTITY_ID' => $entity, 'FIELD_NAME' => $name])->Fetch();
            if ($existing) {
                if ($existing['USER_TYPE_ID'] !== $type || $existing['MULTIPLE'] !== 'N') {
                    throw new \RuntimeException('Несовместимое существующее поле ' . $name);
                }
                continue;
            }
            $field = new \CUserTypeEntity();
            $fieldId = $field->Add([
                'ENTITY_ID' => $entity, 'FIELD_NAME' => $name, 'USER_TYPE_ID' => $type,
                'XML_ID' => 'INTEGRATION_LOG_' . $name, 'SORT' => $sort, 'MULTIPLE' => 'N',
                'MANDATORY' => 'Y', 'SHOW_FILTER' => 'I', 'SHOW_IN_LIST' => 'Y',
                'EDIT_IN_LIST' => 'N', 'IS_SEARCHABLE' => 'N',
                'SETTINGS' => $name === 'UF_MESSAGE' ? ['ROWS' => 8] : [],
                'EDIT_FORM_LABEL' => ['ru' => $label, 'en' => $label],
                'LIST_COLUMN_LABEL' => ['ru' => $label, 'en' => $label],
                'LIST_FILTER_LABEL' => ['ru' => $label, 'en' => $label],
            ]);
            if (!$fieldId) {
                global $APPLICATION;
                $exception = $APPLICATION->GetException();
                throw new \RuntimeException($exception ? $exception->GetString() : 'Не создано поле ' . $name);
            }
            $sort += 100;
        }
        $connection = Application::getConnection();
        foreach (['UF_HASH', 'UF_OCCURRED_AT', 'UF_LEVEL'] as $column) {
            if (!$connection->isIndexExists('integration_log', [$column])) {
                if ($column === 'UF_HASH') {
                    $q = $connection->getSqlHelper();
                    $connection->query('CREATE UNIQUE INDEX ' . $q->quote('ix_integration_log_hash')
                        . ' ON ' . $q->quote('integration_log') . ' (' . $q->quote($column)
                        . ($connection->getType() === 'mysql' ? '(64)' : '') . ')');
                } elseif ($column === 'UF_LEVEL' && $connection->getType() === 'mysql') {
                    // Пользовательские строки HL-блока имеют SQL-тип TEXT.
                    $q = $connection->getSqlHelper();
                    $connection->query('CREATE INDEX ' . $q->quote('ix_integration_log_level')
                        . ' ON ' . $q->quote('integration_log') . ' (' . $q->quote($column) . '(10))');
                } else {
                    $connection->createIndex('integration_log', 'ix_integration_log_' . strtolower($column), [$column]);
                }
            }
        }
        $this->outSuccess('Журнал создан. Просмотр: /local/integration-logs.php');
    }

    public function down()
    {
        throw new \RuntimeException('Автоматический откат отключён: удаление highload-блока уничтожит журнал. Удалите его вручную, если данные больше не нужны.');
    }
}
