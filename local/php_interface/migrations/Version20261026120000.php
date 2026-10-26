<?php

namespace Sprint\Migration;

use Bitrix\Main\Loader;

class Version20261026120000 extends Version
{
    protected $description = 'Добавление файлового поля договора в сделку CRM';

    public function up()
    {
        if (!Loader::includeModule('crm')) {
            throw new \RuntimeException('Модуль CRM не установлен');
        }

        $this->getHelperManager()->UserTypeEntity()->addUserTypeEntityIfNotExists(
            'CRM_DEAL',
            'UF_CRM_CONTRACT_FILE',
            [
                'USER_TYPE_ID' => 'file',
                'MULTIPLE' => 'N',
                'MANDATORY' => 'N',
                'SHOW_FILTER' => 'N',
                'SHOW_IN_LIST' => 'Y',
                'EDIT_IN_LIST' => 'Y',
                'IS_SEARCHABLE' => 'N',
                'SETTINGS' => ['EXTENSIONS' => ['docx' => true]],
                'EDIT_FORM_LABEL' => ['ru' => 'Договор', 'en' => 'Contract'],
                'LIST_COLUMN_LABEL' => ['ru' => 'Договор', 'en' => 'Contract'],
                'LIST_FILTER_LABEL' => ['ru' => 'Договор', 'en' => 'Contract'],
            ]
        );
    }

    public function down()
    {
        if (!Loader::includeModule('crm')) {
            throw new \RuntimeException('Модуль CRM не установлен');
        }

        $this->getHelperManager()->UserTypeEntity()->deleteUserTypeEntityIfExists(
            'CRM_DEAL',
            'UF_CRM_CONTRACT_FILE'
        );
    }
}
