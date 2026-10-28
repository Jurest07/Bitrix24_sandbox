<?php

use Bitrix\Main\EventManager;
use Bitrix\Main\Loader;
use Utils\Handlers\TabHandler;
use Utils\IntegrationLog\Parser;
use Utils\IntegrationLog\Storage;
use Utils\IntegrationLog\Access;

Loader::registerAutoLoadClasses(null, [
    TabHandler::class => '/local/utils/Handlers/TabHandler.php',
    Parser::class => '/local/utils/IntegrationLog/Parser.php',
    Storage::class => '/local/utils/IntegrationLog/Storage.php',
    Access::class => '/local/utils/IntegrationLog/Access.php',
]);

EventManager::getInstance()->addEventHandler(
    'crm',
    'onEntityDetailsTabsInitialized',
    [TabHandler::class, 'handlerOnEntityDetailsTabsInitialized']
);
