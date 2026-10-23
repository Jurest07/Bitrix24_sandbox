<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Loader;
use SandBox\Dashboard\DashboardData;

class DashboardComponent extends CBitrixComponent
{
    /**
     * @throws \Bitrix\Main\LoaderException
     */
    public function executeComponent(): void
    {
        Loader::includeModule('sandbox.dashboard');
        Loader::includeModule('crm');

        // Собираем данные
        $this->arResult['DEALS_BY_STAGE'] = DashboardData::getDealsByStage();
        $this->arResult['DEALS_SUM_BY_MONTH'] = DashboardData::getDealsSumByMonth();
        $this->arResult['AVG_AMOUNT'] = DashboardData::getAverageDealAmount();
        $this->arResult['CONVERSION'] = DashboardData::getConversionRate();
        $this->arResult['TOTAL_DEALS'] = array_sum($this->arResult['DEALS_BY_STAGE']);

        $this->includeComponentTemplate();
    }
}