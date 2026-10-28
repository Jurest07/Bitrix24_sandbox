<?php
namespace Utils\IntegrationLog;

final class Parser
{
    public const LEVELS = ['info', 'warn', 'error', 'fatal', 'debug'];

    public function parse(string $line): array
    {
        $line = rtrim($line, "\r\n");
        // Один лог — одна физическая строка. Двоеточия внутри сообщения сохраняются.
        $pattern = '/\A(?:\x{FEFF})?(?<timestamp>[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2})[ \t]+\[(?<level>[a-z]+)\][ \t]+(?<module_id>[^\s:]+)[ \t]*:[ \t]*(?<message>[^\r\n]*)\z/iu';
        if (preg_match($pattern, $line, $matches) !== 1) {
            throw new \InvalidArgumentException('Ожидается строка: ГГГГ-ММ-ДД ЧЧ:ММ:СС [уровень] ID_модуля: текст');
        }

        // В текстовом формате нет UTC offset: используем часовой пояс PHP сайта.
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $matches['timestamp']);
        $errors = \DateTimeImmutable::getLastErrors();
        if (!$date || ($errors && ($errors['warning_count'] || $errors['error_count']))
            || $date->format('Y-m-d H:i:s') !== $matches['timestamp']) {
            throw new \InvalidArgumentException('Некорректные дата или время');
        }
        $level = strtolower($matches['level']);
        if (!in_array($level, self::LEVELS, true)) {
            throw new \InvalidArgumentException('Неизвестный уровень лога');
        }
        if (strlen($matches['module_id']) > 255) {
            throw new \InvalidArgumentException('module_id: превышено 255 байт');
        }
        if (trim($matches['message']) === '') {
            throw new \InvalidArgumentException('Не заполнен текст лога');
        }
        if (strlen($matches['message']) > 60000) {
            throw new \InvalidArgumentException('message: превышено 60000 байт');
        }

        return [
            'timestamp' => $matches['timestamp'],
            'date' => $date,
            'level' => $level,
            'module_id' => $matches['module_id'],
            'message' => $matches['message'],
            // Этих данных в новом формате нет; поля существующего HL-блока обязательны.
            'ip' => 'не указан',
            'author' => 'не указан',
        ];
    }
}
