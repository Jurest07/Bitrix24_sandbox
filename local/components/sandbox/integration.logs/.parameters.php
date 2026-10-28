<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}
$arComponentParameters = [
    'PARAMETERS' => [
        'PAGE_SIZE' => [
            'PARENT' => 'BASE',
            'NAME' => 'Записей на странице (1–200)',
            'TYPE' => 'STRING',
            'DEFAULT' => '50',
        ],
    ],
];
