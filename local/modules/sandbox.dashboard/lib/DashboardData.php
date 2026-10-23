<?php

namespace SandBox\Dashboard;

use Bitrix\Crm\DealTable;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ORM\Fields\ExpressionField;
use Bitrix\Main\Entity\Query\Join;
use Bitrix\Crm\Category\DealCategory;
use Bitrix\Crm\StatusTable;

class DashboardData
{
    /**
     * Получить количество сделок по стадиям (воронка продаж).
     *
     * @return array [стадия => количество]
     * @throws ArgumentException
     */
    public static function getDealsByStage(): array
    {
        $categories = DealCategory::getAll(true);
        $categoryNames = array_column($categories, 'NAME', 'ID');

        $entityIDs = [];
        foreach ($categories as $category) {
            $categoryId = (int) $category['ID'];
            $entityIDs[] = $categoryId === 0
                ? 'DEAL_STAGE'
                : 'DEAL_STAGE_' . $categoryId;
        }

        $stages = StatusTable::getList([
            'select' => ['ENTITY_ID', 'STATUS_ID', 'NAME'],
            'filter' => ['@ENTITY_ID' => $entityIDs],
        ]);
        $stagesNames = [];
        foreach ($stages as $stage) {
            $stagesNames[$stage['STATUS_ID']] = $stage['NAME'];
        }

        $res = [];
        $result = DealTable::getList([
            'select' => ['CATEGORY_ID','STAGE_ID', 'CNT'],
            'runtime' => [
                new ExpressionField('CNT', 'COUNT(*)')
            ],
            'group' => ['CATEGORY_ID', 'STAGE_ID'],
            'order' => ['CNT' => 'DESC'],
        ]);
        while ($row = $result->fetch()) {
            $key = $categoryNames[$row['CATEGORY_ID']] . ':  ' . $stagesNames[$row['STAGE_ID']];
            $res[$key] = $row['CNT'];
        }
        return $res;
    }

    /**
     * Получить сумму сделок по месяцам.
     *
     * @return array [месяц => сумма]
     */
    public static function getDealsSumByMonth(): array
    {
        $res = [];
        $result = DealTable::getList([
            'select' => ['MONTH', 'TOTAL'],
            'runtime' => [
                new ExpressionField(
                    'MONTH',
                    'DATE_FORMAT(%s, "%%Y-%%m")',
                    ['DATE_CREATE']
                ),
                new ExpressionField('TOTAL', 'SUM(%s)', ['OPPORTUNITY']),
            ],
            'group' => ['MONTH'],
            'order' => ['MONTH' => 'ASC'],
        ]);
        while ($row = $result->fetch()) {
            $res[$row['MONTH']] = $row['TOTAL'];
        }
        return $res;
    }

    /**
     * Получить средний чек.
     *
     * @return float
     */
    public static function getAverageDealAmount(): float
    {
        $result = DealTable::getList([
            'select' => ['AVG_AMOUNT'],
            'runtime' => [
                new ExpressionField('AVG_AMOUNT', 'AVG(%s)', ['OPPORTUNITY']),
            ],
        ]);

        $row = $result->fetch();
        $avgAmount = $row['AVG_AMOUNT'] ?? 0;
        return $avgAmount;
    }

    /**
     * Получить конверсию (отношение успешных сделок к общему числу).
     *
     * @return float Процент
     */
    public static function getConversionRate(): float
    {
        $all = DealTable::getList([
            'select' => ['CNT'],
            'runtime' => [
                new ExpressionField('CNT', 'COUNT(*)'),
            ],
        ]) -> fetch();
        if (!$all || $all['CNT'] == 0) {
            return 0.0;
        }
        $allCount = (float)$all['CNT'];
        $won = DealTable::getList([
            'select' => ['CNT'],
            'runtime' => [
                new ExpressionField('CNT', 'COUNT(*)'),
            ],
            'filter' => ['=STAGE_ID' => 'WON']
        ]) -> fetch();
        $wonCount = (float)$won['CNT'] ?? 0;
        $result = round($wonCount / $allCount * 100, 2);
        return $result;
    }
}