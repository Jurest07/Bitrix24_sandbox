<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php';

use Bitrix\Main\Page\Asset;

global $APPLICATION;

$APPLICATION->SetTitle('Дашборд отдела продаж');

// Подключаем Chart.js ДО вывода компонента
Asset::getInstance()->addJs("/local/utils/JSlib/chart.umd.min.js");

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php';

// Выводим компонент
$APPLICATION->IncludeComponent(
    'sandbox:dashboard',
    '',
    []
);

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';