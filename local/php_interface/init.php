<?php

use Bitrix\Main\EventManager;
use Bitrix\Main\Loader;
use Utils\Handlers\TabHandler;

Loader::registerAutoLoadClasses(null, [
    TabHandler::class => '/local/utils/Handlers/TabHandler.php',
]);

EventManager::getInstance()->addEventHandler(
    'crm',
    'onEntityDetailsTabsInitialized',
    [TabHandler::class, 'handlerOnEntityDetailsTabsInitialized']
);
