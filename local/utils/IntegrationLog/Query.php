<?php
namespace Utils\IntegrationLog;

use Bitrix\Main\Application;
use Bitrix\Main\ORM\Fields\ExpressionField;
use Bitrix\Main\Type\DateTime;
use DateTimeImmutable;
use InvalidArgumentException;

final class Query
{
    public const FILTER_KEYS = ['level', 'ip', 'module', 'author', 'from', 'to', 'time_from', 'time_to'];

    public function readParameters(): array
    {
        $request = Application::getInstance()->getContext()->getRequest();
        $params = [];
        foreach (self::FILTER_KEYS as $key) {
            $value = $request->getQuery($key);
            $params[$key] = is_string($value) ? trim($value) : '';
        }
        return $params;
    }

    public function buildFilter(array $params): array
    {
        $filter = [];
        if ($params['level'] !== '') {
            if (!in_array($params['level'], Parser::LEVELS, true)) {
                throw new InvalidArgumentException('Неизвестный статус ошибки');
            }
            $filter['=UF_LEVEL'] = $params['level'];
        }
        foreach (['ip' => 'UF_SOURCE_IP', 'module' => 'UF_MODULE_ID', 'author' => 'UF_AUTHOR'] as $key => $field) {
            if ($params[$key] !== '') {
                $filter['=' . $field] = $params[$key];
            }
        }
        return array_merge($filter, $this->buildDateFilter($params), $this->buildTimeFilter($params));
    }

    private function buildDateFilter(array $params): array
    {
        $filter = [];
        foreach (['from', 'to'] as $key) {
            if ($params[$key] === '') {
                continue;
            }
            $date = DateTimeImmutable::createFromFormat('!Y-m-d', $params[$key]);
            if (!$date || $date->format('Y-m-d') !== $params[$key]) {
                throw new InvalidArgumentException('Некорректная дата: ожидается ГГГГ-ММ-ДД');
            }
            if ($key === 'to') {
                $date = $date->modify('+1 day');
            }
            $filter[($key === 'from' ? '>=' : '<') . 'UF_OCCURRED_AT'] = DateTime::createFromTimestamp($date->getTimestamp());
        }
        if ($params['from'] !== '' && $params['to'] !== '' && $params['from'] > $params['to']) {
            throw new InvalidArgumentException('Дата начала периода позже даты окончания');
        }
        return $filter;
    }

    private function buildTimeFilter(array $params): array
    {
        $filter = [];
        $times = [];
        foreach (['time_from', 'time_to'] as $key) {
            if ($params[$key] === '') {
                continue;
            }
            if (!preg_match('/^(?:[01][0-9]|2[0-3]):[0-5][0-9](?::[0-5][0-9])?$/D', $params[$key])) {
                throw new InvalidArgumentException('Некорректное время: ожидается ЧЧ:ММ или ЧЧ:ММ:СС');
            }
            // Без секунд верхняя граница включает всю минуту.
            $times[$key] = strlen($params[$key]) === 5
                ? $params[$key] . ($key === 'time_from' ? ':00' : ':59')
                : $params[$key];
            $filter[($key === 'time_from' ? '>=' : '<=') . 'LOG_TIME'] = $times[$key];
        }
        if (isset($times['time_from'], $times['time_to']) && $times['time_from'] > $times['time_to']) {
            throw new InvalidArgumentException('Время начала позже времени окончания. Выберите диапазон в пределах суток.');
        }
        return $filter;
    }

    public function buildRuntimeFields(array $params): array
    {
        if ($params['time_from'] === '' && $params['time_to'] === '') {
            return [];
        }

        // Время фильтруется отдельно для каждого дня выбранного периода.
        $helper = Application::getConnection()->getSqlHelper();
        $expression = $helper->getSubstrFunction($helper->getDateToCharFunction('%s'), 12, 8);
        return [
            new ExpressionField('LOG_TIME', $expression, ['UF_OCCURRED_AT'], ['data_type' => 'string']),
        ];
    }

}
