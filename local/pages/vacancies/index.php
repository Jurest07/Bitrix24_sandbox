<?php
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");

global $APPLICATION;

$APPLICATION->SetTitle("Вакансии");

try {
    $APPLICATION->IncludeComponent(
        "sandbox:vacancies.list",
        ".default",
        [
            "IBLOCK_CODE" => "vacancies",
        ],
        false
    );
} catch (\Throwable $e) {
    echo '<pre style="background:#fee;padding:20px;border:2px solid red;">';
    echo "❌ Ошибка: " . $e->getMessage() . "\n";
    echo "Файл: " . $e->getFile() . ":" . $e->getLine() . "\n\n";
    echo "Trace:\n" . $e->getTraceAsString();
    echo '</pre>';
}

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php");
?>