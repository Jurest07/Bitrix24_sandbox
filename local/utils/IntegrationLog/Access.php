<?php

namespace Utils\IntegrationLog;

use Bitrix\Main\GroupTable;

final class Access
{
    public static function canView(): bool
    {
        return self::inGroup('aes');
    }

    public static function canExport(): bool
    {
        return self::canView() && self::inGroup('aes_log_export');
    }

    private static function inGroup(string $code): bool
    {
        global $USER;

        if (!$USER || !$USER->IsAuthorized()) {
            return false;
        }

        $groups = GroupTable::getList([
            'select' => ['ID'],
            'filter' => [
                '=STRING_ID' => $code,
                '=ACTIVE' => 'Y',
            ],
        ])->fetchAll();

        return (bool) array_intersect(
            array_map('intval', array_column($groups, 'ID')),
            array_map('intval', $USER->GetUserGroupArray())
        );
    }
}
