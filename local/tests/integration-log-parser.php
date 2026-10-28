<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require dirname(__DIR__) . '/utils/IntegrationLog/bootstrap.php';

// Фиксированный timezone делает тест независимым от настроек PHP CLI.
date_default_timezone_set('Europe/Moscow');
$parser = new \Utils\IntegrationLog\Parser();
$tests = 0;
$check = static function (bool $condition, string $message) use (&$tests): void {
    $tests++;
    if (!$condition) {
        throw new RuntimeException($message);
    }
};
foreach (\Utils\IntegrationLog\Parser::LEVELS as $level) {
    $row = $parser->parse('2026-10-21 12:30:00 [' . strtoupper($level) . '] partner.orders: Ответ API: ошибка: 500');
    $check($row['level'] === $level, 'Нормализация уровня ' . $level);
    $check($row['date']->getTimestamp() === 1792575000, 'Часовой пояс PHP');
    $check($row['module_id'] === 'partner.orders', 'ID модуля');
    $check($row['message'] === 'Ответ API: ошибка: 500', 'Двоеточия в тексте');
    $check($row['ip'] === 'не указан' && $row['author'] === 'не указан', 'Отсутствующие IP и автор');
}
$row = $parser->parse("\xEF\xBB\xBF2026-10-21 12:30:00\t[info]\tpartner.orders : Текст\r\n");
$check($row['message'] === 'Текст', 'UTF-8 BOM, табуляция, CRLF и пробел перед двоеточием');
$row = $parser->parse('2026-10-21 12:30:00 [debug] partner.orders: <script>alert(1)</script>');
$check($row['message'] === '<script>alert(1)</script>', 'Парсер сохраняет текст, экранирование выполняет шаблон');
foreach ([
    '',
    '2026-10-21 12:30:00 info partner.orders: Текст',
    '2026-02-30 12:30:00 [info] partner.orders: Текст',
    '2026-10-21 25:30:00 [info] partner.orders: Текст',
    '2026-10-21 12:30:00 [unknown] partner.orders: Текст',
    '2026-10-21 12:30:00 [info] : Текст',
    '2026-10-21 12:30:00 [info] partner.orders Текст',
    '2026-10-21 12:30:00 [info] partner.orders:   ',
    "2026-10-21 12:30:00 [info] partner.orders: Первая\nВторая",
    '2026-10-21 12:30:00 [info] ' . str_repeat('x', 256) . ': Текст',
    '2026-10-21 12:30:00 [info] partner.orders: ' . str_repeat('x', 60001),
] as $bad) {
    $rejected = false;
    try {
        $parser->parse($bad);
    } catch (InvalidArgumentException $e) {
        $rejected = true;
    }
    $check($rejected, 'Некорректная запись должна быть отклонена');
}
echo 'OK: ' . $tests . ' checks' . PHP_EOL;
