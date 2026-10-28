<?php

namespace Utils\IntegrationLog;

use Bitrix\Main\GroupTable;

final class Access
{
    public static function canView(): bool
    {
        global $USER;

        if (!$USER || !$USER->IsAuthorized()) {
            return false;
        }

        $groups = GroupTable::getList([
            'select' => ['ID'],
            'filter' => [
                '=STRING_ID' => 'aes',
                '=ACTIVE' => 'Y',
            ],
        ])->fetchAll();

        return (bool) array_intersect(
            array_map('intval', array_column($groups, 'ID')),
            array_map('intval', $USER->GetUserGroupArray())
        );
    }
}
