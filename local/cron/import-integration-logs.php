<?php
// Запускается только из CLI. Аргумент: абсолютный путь к текстовому файлу логов.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$root = dirname(__DIR__, 2);
$_SERVER['DOCUMENT_ROOT'] = $root;
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
define('NO_AGENT_CHECK', true);
define('DisableEventsCheck', true);

$lock = null;
$input = null;
try {
    // Общий для всех источников lock: одновременно работает только один импорт.
    $state = $root . '/local/var/integration-log';
    if (!is_dir($state) && !mkdir($state, 0700, true) && !is_dir($state)) {
        throw new RuntimeException('Не удалось создать каталог блокировки');
    }
    $lock = fopen($state . '/import.lock', 'c');
    if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) {
        throw new RuntimeException('Импорт уже запущен или файл блокировки недоступен');
    }
    require $root . '/bitrix/modules/main/include/prolog_before.php';
    require_once $root . '/local/utils/IntegrationLog/bootstrap.php';
    $parser = new \Utils\IntegrationLog\Parser();
    if (empty($argv[1])) {
        throw new RuntimeException('Использование: php local/cron/import-integration-logs.php /absolute/path/source.log');
    }
    $path = realpath($argv[1]);
    if ($path === false || !is_file($path) || !is_readable($path)) {
        throw new RuntimeException('Файл логов не найден или недоступен для чтения');
    }
    $class = \Utils\IntegrationLog\Storage::dataClass();
    $input = fopen($path, 'rb');
    if (!$input) {
        throw new RuntimeException('Не удалось открыть файл логов');
    }
    $added = $skipped = $invalid = $number = 0;
    // Читаем только размер файла на момент запуска. Новые строки — в следующий запуск.
    $size = fstat($input)['size'];
    while (ftell($input) < $size) {
        $number++;
        $remaining = $size - ftell($input);
        $line = fgets($input, min($remaining + 1, 1048578));
        if ($line === false) {
            if (!feof($input)) {
                throw new RuntimeException('Ошибка чтения файла');
            }
            break;
        }
        if (strlen($line) > 1048576) {
            throw new RuntimeException('Строка ' . $number . ' превышает 1 МБ');
        }
        if (substr($line, -1) !== "\n") {
            // Запись ещё может дописываться производителем. Не импортируем её частично.
            fwrite(STDERR, 'Строка ' . $number . ': нет завершающего перевода строки; отложена' . PHP_EOL);
            $invalid++;
            break;
        }
        $line = rtrim($line, "\r\n");
        if (trim($line) === '') {
            continue;
        }
        try {
            $row = $parser->parse($line);
        } catch (InvalidArgumentException $e) {
            // Не выводим сам лог: в нём могут содержаться персональные данные.
            fwrite(STDERR, 'Строка ' . $number . ': ' . $e->getMessage() . PHP_EOL);
            $invalid++;
            continue;
        }
        // Позиция сохраняет две одинаковые записи на разных строках.
        // realpath различает независимые файлы; файл не следует переименовывать после импорта.
        $hash = hash('sha256', $path . "\n" . $number . "\n" . $line);
        if ($class::getCount(['=UF_HASH' => $hash]) > 0) {
            $skipped++;
            continue;
        }
        $result = $class::add([
            'UF_OCCURRED_AT' => \Bitrix\Main\Type\DateTime::createFromTimestamp($row['date']->getTimestamp()),
            'UF_LEVEL' => $row['level'], 'UF_SOURCE_IP' => $row['ip'],
            'UF_MODULE_ID' => $row['module_id'], 'UF_AUTHOR' => $row['author'],
            'UF_MESSAGE' => $row['message'], 'UF_HASH' => $hash,
        ]);
        if (!$result->isSuccess()) {
            throw new RuntimeException('Строка ' . $number . ': ' . implode('; ', $result->getErrorMessages()));
        }
        $added++;
    }
    echo sprintf('Добавлено: %d; уже загружено: %d; некорректных/незавершённых: %d', $added, $skipped, $invalid) . PHP_EOL;
    $exitCode = $invalid > 0 ? 2 : 0;
} catch (Throwable $e) {
    fwrite(STDERR, 'Ошибка импорта: ' . $e->getMessage() . PHP_EOL);
    $exitCode = 1;
} finally {
    if (is_resource($input)) {
        fclose($input);
    }
    if (is_resource($lock)) {
        flock($lock, LOCK_UN);
        fclose($lock);
    }
}
exit($exitCode);
