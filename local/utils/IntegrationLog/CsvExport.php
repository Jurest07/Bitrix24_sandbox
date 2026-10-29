<?php
namespace Utils\IntegrationLog;

final class CsvExport
{
    /** @param resource $output */
    public function write($output, iterable $rows): void
    {
        if (fwrite($output, "\xEF\xBB\xBF") !== 3) {
            throw new \RuntimeException('Не удалось записать CSV');
        }
        $this->writeRow($output, ['ID', 'Дата и время', 'Уровень', 'IP-адрес', 'ID модуля', 'Автор', 'Текст лога']);
        foreach ($rows as $row) {
            $this->writeRow($output, [
                $row['ID'], $row['UF_OCCURRED_AT']->format('Y-m-d H:i:s'),
                $row['UF_LEVEL'], $row['UF_SOURCE_IP'], $row['UF_MODULE_ID'],
                $row['UF_AUTHOR'], $row['UF_MESSAGE'],
            ]);
        }
    }

    /** @param resource $output */
    private function writeRow($output, array $values): void
    {
        $values = array_map(static function ($value): string {
            $value = (string)$value;
            // Кавычки CSV сами по себе не препятствуют выполнению формул в Excel.
            if (preg_match('/^[\s\x00-\x20]*[=+@-]|^[\t\r\n]/u', $value)) {
                $value = "'" . $value;
            }
            return $value;
        }, $values);
        if (fputcsv($output, $values, ';', '"', '') === false) {
            throw new \RuntimeException('Не удалось записать CSV');
        }
    }

    public function rows(array $filter, array $runtime): \Generator
    {
        $class = Storage::dataClass();
        // Фиксируем верхнюю границу: новые записи попадут в следующую выгрузку.
        $latest = $class::getList(['select' => ['ID'], 'order' => ['ID' => 'DESC'], 'limit' => 1])->fetch();
        $before = (int)($latest['ID'] ?? 0) + 1;
        do {
            $batch = $class::getList([
                'select' => ['ID', 'UF_OCCURRED_AT', 'UF_LEVEL', 'UF_SOURCE_IP', 'UF_MODULE_ID', 'UF_AUTHOR', 'UF_MESSAGE'],
                'filter' => [$filter, ['<ID' => $before]],
                'runtime' => $runtime,
                'order' => ['ID' => 'DESC'],
                'limit' => 500,
            ])->fetchAll();
            foreach ($batch as $row) {
                $before = (int)$row['ID'];
                yield $row;
            }
        } while (count($batch) === 500);
    }
}
