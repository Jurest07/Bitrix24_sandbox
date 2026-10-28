<?php
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

global $APPLICATION;

$APPLICATION->SetTitle('Журнал интеграции');

if (!\Utils\IntegrationLog\Access::canView()) {
    http_response_code(403);
    require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';
    ShowError('Доступ к журналу разрешён только участникам группы «АЭС»');
    require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php';
    return;
}

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';

$APPLICATION->IncludeComponent(
    'sandbox:integration.logs',
    '',
    ['PAGE_SIZE' => 50, 'CACHE_TYPE' => 'N'],
    false
);
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php';
