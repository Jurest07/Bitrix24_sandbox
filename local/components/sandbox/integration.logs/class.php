<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Application;
use Bitrix\Main\ORM\Fields\ExpressionField;
use Bitrix\Main\Type\DateTime;
use Utils\IntegrationLog\Parser;
use Utils\IntegrationLog\Storage;
use Utils\IntegrationLog\Access;

class IntegrationLogsComponent extends CBitrixComponent
{
    private const FILTER_KEYS = ['level', 'ip', 'module', 'author', 'from', 'to', 'time_from', 'time_to'];

    public function onPrepareComponentParams($arParams): array
    {
        $arParams['PAGE_SIZE'] = max(1, min(200, (int)($arParams['PAGE_SIZE'] ?? 50)));
        $arParams['CACHE_TYPE'] = 'N';
        return $arParams;
    }

    public function executeComponent(): void
    {
        if (!Access::canView()) {
            http_response_code(403);
            ShowError('Доступ к журналу разрешён только участникам группы «АЭС»');
            return;
        }

        $this->initializeResult();
        try {
            $this->loadLogs();
        } catch (Throwable $e) {
            $this->arResult['ERROR'] = $e->getMessage();
        }
        $this->includeComponentTemplate();
    }

    private function readParameter(string $name): string
    {
        $request = Application::getInstance()->getContext()->getRequest();
        $value = $request->getQuery($name);
        return is_string($value) ? trim($value) : '';
    }

    private function initializeResult(): void
    {
        global $APPLICATION;

        $params = [];
        foreach (self::FILTER_KEYS as $key) {
            $params[$key] = $this->readParameter($key);
        }
        $this->arResult = [
            'FILTER' => $params,
            'LEVELS' => Parser::LEVELS,
            'ROWS' => [],
            'TOTAL' => 0,
            'PAGE' => 1,
            'PAGES' => 1,
            'ERROR' => '',
            'PATH' => $APPLICATION->GetCurPage(false),
            'TIMEZONE' => date_default_timezone_get(),
            'PREV_URL' => '',
            'NEXT_URL' => '',
        ];
    }

    private function buildFilter(array $params): array
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

    private function buildRuntimeFields(array $params): array
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

    private function loadLogs(): void
    {
        $params = $this->arResult['FILTER'];
        $filter = $this->buildFilter($params);
        $runtime = $this->buildRuntimeFields($params);
        $class = Storage::dataClass();

        // Отдельно считаем все подходящие записи для пагинации.
        $count = $class::getList([
            'select' => ['TOTAL'],
            'filter' => $filter,
            'runtime' => array_merge($runtime, [new ExpressionField('TOTAL', 'COUNT(*)')]),
        ])->fetch();
        $total = (int)$count['TOTAL'];
        $pageSize = $this->arParams['PAGE_SIZE'];
        $pages = max(1, (int)ceil($total / $pageSize));
        $page = min(max(1, (int)$this->readParameter('page')), $pages);

        $this->arResult['ROWS'] = $class::getList([
            'select' => ['ID', 'UF_OCCURRED_AT', 'UF_LEVEL', 'UF_SOURCE_IP', 'UF_MODULE_ID', 'UF_AUTHOR', 'UF_MESSAGE'],
            'filter' => $filter,
            'runtime' => $runtime,
            'order' => ['UF_OCCURRED_AT' => 'DESC', 'ID' => 'DESC'],
            'limit' => $pageSize,
            'offset' => ($page - 1) * $pageSize,
        ])->fetchAll();
        $this->arResult['TOTAL'] = $total;
        $this->arResult['PAGE'] = $page;
        $this->arResult['PAGES'] = $pages;
        $this->arResult['PREV_URL'] = $page > 1 ? $this->pageUrl($page - 1) : '';
        $this->arResult['NEXT_URL'] = $page < $pages ? $this->pageUrl($page + 1) : '';
    }

    private function pageUrl(int $page): string
    {
        return $this->arResult['PATH'] . '?' . http_build_query(
            array_merge($this->arResult['FILTER'], ['page' => $page])
        );
    }
}
