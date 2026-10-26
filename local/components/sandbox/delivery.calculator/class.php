<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Loader;
use Bitrix\Main\Error;
use Bitrix\Main\Engine\ActionFilter;
use SandBox\Delivery\Calculator;
use Bitrix\Main\Engine\Contract\Controllerable;

class DeliveryCalculatorComponent extends CBitrixComponent implements Controllerable
{
    public function executeComponent()
    {
        $this->includeComponentTemplate();
    }

    /**
     * AJAX-действие для расчёта стоимости доставки.
     * Вызывается через BX.ajax.runComponentAction().
     */
    public function calculateAction()
    {
        // TODO: Подумать над подключением модуля не в init.php, а на месте использования!
        // TODO: Вывод кастомной ошибки вместо системной!
        $weight = (float)($this->request->getPost('weight') ?? 0);
        $length = (float)($this->request->getPost('length') ?? 0);
        $width = (float)($this->request->getPost('width') ?? 0);
        $height = (float)($this->request->getPost('height') ?? 0);
        $distance = (float)($this->request->getPost('distance') ?? 0);

        $cost = $this->calculateResult($weight, $length, $width, $height, $distance);

        return [
            'cost' => $cost,
        ];
    }

    public function configureActions()
    {
        return [
            'calculate' => [
                'prefilters' => [],
            ],
        ];
    }

    private function calculateResult($weight, $length, $width, $height, $distance): float
    {
        $V = $length * $width * $height / 1000000;
        $res = $weight * 50 + $V * 0.5 + $distance * 10;
        return $res;
    }

}