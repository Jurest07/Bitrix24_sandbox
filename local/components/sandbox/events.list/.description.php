<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
    die();

$arComponentDescription = [
    "NAME" => "Список мероприятий",
    "DESCRIPTION" => "Выводит список активных мероприятий с фильтрацией по разделу",
    "SORT" => 10,
    "CACHE_PATH" => "Y",
    "PATH" => [
        "ID" => "sandbox",
        "NAME" => "Sandbox",
        "CHILD" => [
            "ID" => "events",
            "NAME" => "Мероприятия",
        ],
    ],
];
