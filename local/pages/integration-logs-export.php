<?php
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

use Utils\IntegrationLog\Access;
use Utils\IntegrationLog\CsvExport;
use Utils\IntegrationLog\Query;

// Проверяется при прямом обращении, независимо от наличия кнопки в интерфейсе.
if (!Access::canExport()) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=UTF-8');
    header('Cache-Control: no-store');
    echo 'Недостаточно прав для выгрузки журнала';
    exit;
}

$output = null;
try {
    $query = new Query();
    $params = $query->readParameters();
    $filter = $query->buildFilter($params);
    $runtime = $query->buildRuntimeFields($params);
    // Временный файл не расходует память пропорционально размеру выгрузки.
    // До завершения формирования ошибки возвращаются обычным HTTP-ответом.
    $output = tmpfile();
    if ($output === false) {
        throw new RuntimeException('Не удалось создать временный файл');
    }
    $export = new CsvExport();
    $export->write($output, $export->rows($filter, $runtime));
    rewind($output);
} catch (Throwable $e) {
    if (is_resource($output)) {
        fclose($output);
    }
    http_response_code($e instanceof InvalidArgumentException ? 400 : 500);
    header('Content-Type: text/plain; charset=UTF-8');
    header('Cache-Control: no-store');
    echo $e instanceof InvalidArgumentException ? $e->getMessage() : 'Не удалось сформировать выгрузку журнала';
    exit;
}

global $APPLICATION;
$APPLICATION->RestartBuffer();
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="integration-logs-' . date('Ymd-His') . '.csv"');
header('Cache-Control: private, no-store');
header('X-Content-Type-Options: nosniff');
fpassthru($output);
fclose($output);
exit;
