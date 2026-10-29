<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Application;
use Bitrix\Main\ORM\Fields\ExpressionField;
use Utils\IntegrationLog\Query;
use Utils\IntegrationLog\Parser;
use Utils\IntegrationLog\Storage;
use Utils\IntegrationLog\Access;

class IntegrationLogsComponent extends CBitrixComponent
{
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

        $params = (new Query())->readParameters();
        $this->arResult = [
            'FILTER' => $params,
            'CAN_EXPORT' => Access::canExport(),
            'EXPORT_URL' => '/local/pages/integration-logs-export.php?' . http_build_query($params),
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

    private function loadLogs(): void
    {
        $params = $this->arResult['FILTER'];
        $query = new Query();
        $filter = $query->buildFilter($params);
        $runtime = $query->buildRuntimeFields($params);
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
