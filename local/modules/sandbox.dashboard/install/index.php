<?php

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;

Loc::loadMessages(__FILE__);

class sandbox_dashboard extends CModule
{
    public $MODULE_ID = 'sandbox.dashboard';
    public $MODULE_VERSION;
    public $MODULE_VERSION_DATE;
    public $MODULE_NAME;
    public $MODULE_DESCRIPTION;

    public function __construct()
    {
        $arModuleVersion = [];
        include(__DIR__ . '/version.php');

        $this->MODULE_VERSION = $arModuleVersion['VERSION'];
        $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
        $this->MODULE_NAME = 'Дашборд для отдела продаж';
        $this->MODULE_DESCRIPTION = 'Показывает аналитику по сделкам CRM';
    }

    public function InstallFiles(): bool
    {
        CopyDirFiles(dirname(__DIR__) . '/admin', $_SERVER['DOCUMENT_ROOT'] . '/bitrix/admin/', true, true);
        return true;
    }

    public function UnInstallFiles(): bool
    {
        DeleteDirFilesEx('/bitrix/admin/dashboard.php');
        return true;
    }

    public function DoInstall(): void
    {
        ModuleManager::registerModule($this->MODULE_ID);
        $this->InstallFiles();
    }

    public function DoUninstall(): void
    {
        $this->UnInstallFiles();
        ModuleManager::unRegisterModule($this->MODULE_ID);
    }
}