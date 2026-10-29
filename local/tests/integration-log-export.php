<?php
namespace Bitrix\Main {
    final class GroupTable
    {
        public static array $active = ['aes' => 10, 'integration_log_export' => 20];
        public static function getList(array $params): object
        {
            $id = self::$active[$params['filter']['=STRING_ID']] ?? null;
            return new class($id) {
                private $id;
                public function __construct($id) { $this->id = $id; }
                public function fetchAll(): array { return $this->id ? [['ID' => $this->id]] : []; }
            };
        }
    }
}
namespace {
    if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
    require dirname(__DIR__) . '/utils/IntegrationLog/Access.php';
    require dirname(__DIR__) . '/utils/IntegrationLog/CsvExport.php';
    $checks = 0;
    $check = static function (bool $ok, string $message) use (&$checks): void {
        $checks++;
        if (!$ok) { throw new RuntimeException($message); }
    };
    $USER = new class {
        public bool $authorized = true;
        public array $groups = [];
        public function IsAuthorized(): bool { return $this->authorized; }
        public function GetUserGroupArray(): array { return $this->groups; }
    };
    foreach ([[[], false, false], [[1], false, false], [[10], true, false], [[20], false, false], [['10', '20'], true, true]] as [$groups, $view, $export]) {
        $USER->groups = $groups;
        $check(\Utils\IntegrationLog\Access::canView() === $view, 'Право просмотра');
        $check(\Utils\IntegrationLog\Access::canExport() === $export, 'Отдельное право экспорта');
    }
    $USER->authorized = false;
    $check(!\Utils\IntegrationLog\Access::canExport(), 'Аноним не выгружает');
    $USER->authorized = true;
    unset(\Bitrix\Main\GroupTable::$active['integration_log_export']);
    $check(!\Utils\IntegrationLog\Access::canExport(), 'Неактивная/отсутствующая группа не даёт право');
    $USER = null;
    $check(!\Utils\IntegrationLog\Access::canExport(), 'Нет пользователя');

    $messages = ['Обычный текст; "кавычки" и слеш \\', "Первая\nВторая", '=1+1', ' +SUM(1;2)', '-1+2', '@SUM(A1)', "\t=1", "\r=1", 'Текст: =1'];
    $rows = (static function () use ($messages): Generator {
        foreach ($messages as $i => $message) {
            yield ['ID' => $i + 1, 'UF_OCCURRED_AT' => new DateTimeImmutable('2026-10-29 12:00:00'), 'UF_LEVEL' => 'error', 'UF_SOURCE_IP' => 'не указан', 'UF_MODULE_ID' => 'orders', 'UF_AUTHOR' => 'не указан', 'UF_MESSAGE' => $message];
        }
    })();
    $output = tmpfile();
    (new \Utils\IntegrationLog\CsvExport())->write($output, $rows);
    rewind($output);
    $check(fread($output, 3) === "\xEF\xBB\xBF", 'UTF-8 BOM');
    $check(count(fgetcsv($output, 0, ';', '"', '')) === 7, 'Колонки CSV');
    foreach ($messages as $i => $message) {
        $row = fgetcsv($output, 0, ';', '"', '');
        $expected = $i >= 2 && $i <= 7 ? "'" . $message : $message;
        $check($row[6] === $expected, 'CSV round trip и защита формул: ' . $i);
    }
    $check(fgetcsv($output, 0, ';', '"', '') === false, 'Все строки выгружены');
    fclose($output);
    $output = tmpfile();
    (new \Utils\IntegrationLog\CsvExport())->write($output, []);
    rewind($output);
    fread($output, 3);
    $check(is_array(fgetcsv($output, 0, ';', '"', '')), 'Пустая выгрузка содержит заголовок');
    $check(fgetcsv($output, 0, ';', '"', '') === false, 'Нет фиктивных записей');
    fclose($output);
    echo 'OK: ' . $checks . ' checks' . PHP_EOL;
}
