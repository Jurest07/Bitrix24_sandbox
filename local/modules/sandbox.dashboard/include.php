<?php

use Bitrix\Main\Loader;

try {
    Loader::registerAutoLoadClasses('sandbox.dashboard', [
        'SandBox\\Dashboard\\DashboardData' => 'lib/DashboardData.php',
    ]);
} catch (\Bitrix\Main\LoaderException $e) {

}

