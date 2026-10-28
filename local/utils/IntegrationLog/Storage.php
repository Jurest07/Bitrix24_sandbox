<?php
namespace Utils\IntegrationLog;

use Bitrix\Highloadblock\HighloadBlockTable;
use Bitrix\Main\Loader;

final class Storage
{
    public const NAME = 'IntegrationLog';
    public const TABLE = 'integration_log';

    public static function dataClass(): string
    {
        if (!Loader::includeModule('highloadblock')) {
            throw new \RuntimeException('Не установлен модуль highloadblock');
        }
        $block = HighloadBlockTable::getList(['filter' => ['=NAME' => self::NAME]])->fetch();
        if (!$block || $block['TABLE_NAME'] !== self::TABLE) {
            throw new \RuntimeException('Сначала примените миграцию Version20261026150000');
        }
        return HighloadBlockTable::compileEntity($block)->getDataClass();
    }
}
