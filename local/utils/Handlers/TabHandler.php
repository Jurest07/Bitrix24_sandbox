<?php

namespace Utils\Handlers;

use Bitrix\Main\Event;
use Bitrix\Main\EventResult;

class TabHandler
{
    /**
     * Обработчик события onEntityDetailsTabsInitialized.
     * Добавляет вкладки в карточку сделки.
     *
     * @param \Bitrix\Main\Event $event
     * @return \Bitrix\Main\EventResult
     */
    public static function handlerOnEntityDetailsTabsInitialized(Event $event): EventResult
    {
        $tabs = (array) $event->getParameter('tabs');
        $entityTypeID = (int) $event->getParameter('entityTypeID');
        $entityID = (int) $event->getParameter('entityID');

        // Работаем только со сделками
        if ($entityTypeID !== \CCrmOwnerType::Deal || $entityID <= 0) {
            return new EventResult(EventResult::SUCCESS, ['tabs' => $tabs]);
        }

        $tabIds = array_column($tabs, 'id');
        if (!in_array('delivery_calculator', $tabIds, true)) {
            self::addCalculatorTab($tabs);
        }
        if (!in_array('docgen_documents', $tabIds, true)) {
            self::addDocGenTab($tabs, $entityID);
        }
        // Сюда можно добавлять вкладки других модулей

        return new EventResult(EventResult::SUCCESS, ['tabs' => $tabs]);
    }

    private static function addDocGenTab(&$tabs, $entityID)
    {
        $tabs[] = [
            'id' => 'docgen_documents',
            'name' => 'Договор',
            'html' => self::renderComponent('sandbox:docgen.contract', ['DEAL_ID' => $entityID]),
        ];
    }

    private static function addCalculatorTab(&$tabs)
    {
        $tabs[] = [
            'id' => 'delivery_calculator',
            'name' => 'Калькулятор',
            'html' => self::renderComponent('sandbox:delivery.calculator', []),
        ];
    }

    private static function renderComponent(string $name, array $parameters): string
    {
        ob_start();
        try {
            $GLOBALS['APPLICATION']->IncludeComponent($name, '.default', $parameters);
            return (string) ob_get_contents();
        } finally {
            ob_end_clean();
        }
    }
}
