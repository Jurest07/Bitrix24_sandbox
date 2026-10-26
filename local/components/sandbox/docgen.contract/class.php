<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Loader;
use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Crm\Service\Container;

class DocgenContractComponent extends CBitrixComponent implements Controllerable
{
    public function executeComponent()
    {
        $this->arResult['DEAL_ID'] = (int)($this->arParams['DEAL_ID'] ?? 0);
        $this->includeComponentTemplate();
    }

    public function configureActions()
    {
        return [
            'generate' => [
                'prefilters' => [
                    new ActionFilter\Authentication(),
                    new ActionFilter\HttpMethod([ActionFilter\HttpMethod::METHOD_POST]),
                    new ActionFilter\Csrf(),
                ],
            ],
        ];
    }

    /**
     * @throws \Bitrix\Main\LoaderException
     */
    public function generateAction()
    {
        if (!Loader::includeModule('crm')) {
            throw new \RuntimeException('Модуль CRM не установлен');
        }

        $dealId = (int)($this->request->getPost('deal_id') ?? 0);
        if ($dealId <= 0) {
            throw new \RuntimeException('Не указан корректный ID сделки');
        }
        $itemPermissions = Container::getInstance()->getUserPermissions()->item();
        if (
            !$itemPermissions->canRead(\CCrmOwnerType::Deal, $dealId)
            || !$itemPermissions->canUpdate(\CCrmOwnerType::Deal, $dealId)
        ) {
            throw new \RuntimeException('Недостаточно прав для создания договора по этой сделке');
        }

        $deal = \CCrmDeal::GetByID($dealId);
        if (!$deal) {
            throw new \RuntimeException('Сделка не найдена');
        }

        $field = \CUserTypeEntity::GetList([], [
            'ENTITY_ID' => 'CRM_DEAL',
            'FIELD_NAME' => 'UF_CRM_CONTRACT_FILE',
        ])->Fetch();
        if (!$field || $field['USER_TYPE_ID'] !== 'file') {
            throw new \RuntimeException('В сделках не настроено файловое поле UF_CRM_CONTRACT_FILE');
        }

        $autoload = $_SERVER['DOCUMENT_ROOT'] . '/local/vendor/autoload.php';
        if (is_file($autoload)) {
            require_once $autoload;
        }
        if (!class_exists(\PhpOffice\PhpWord\TemplateProcessor::class)) {
            throw new \RuntimeException('Не установлена библиотека phpoffice/phpword');
        }

        $templatePath = __DIR__ . '/doc_temptales/contract_temptale.docx';
        if (!is_file($templatePath) || !is_readable($templatePath)) {
            throw new \RuntimeException('Не найден или недоступен шаблон contract_temptale.docx');
        }

        $clientName = 'Не указан';
        if (!empty($deal['COMPANY_ID'])) {
            $company = \CCrmCompany::GetByID((int)$deal['COMPANY_ID']);
            if ($company) {
                $clientName = $company['TITLE'];
            }
        } elseif (!empty($deal['CONTACT_ID'])) {
            $contact = \CCrmContact::GetByID((int)$deal['CONTACT_ID']);
            if ($contact) {
                $clientName = trim(implode(' ', array_filter([
                    $contact['LAST_NAME'] ?? '', $contact['NAME'] ?? '', $contact['SECOND_NAME'] ?? '',
                ])));
            }
        }

        $data = [
            'DEAL_ID' => $deal['ID'],
            'DEAL_TITLE' => $deal['TITLE'] ?? '',
            'DEAL_AMOUNT' => $deal['OPPORTUNITY'] ?? 0,
            'CLIENT_NAME' => $clientName,
            'COMPANY_NAME' => 'ООО «Моя Компания»',
            'CURRENT_DATE' => date('d.m.Y'),
        ];

        $outputPath = tempnam(sys_get_temp_dir(), 'contract_');
        if ($outputPath === false) {
            throw new \RuntimeException('Не удалось создать временный файл договора');
        }

        try {
            $this->generate($templatePath, $data, $outputPath);
            $fileId = $this->attachToDeal($dealId, $outputPath, $field['MULTIPLE'] === 'Y');
        } finally {
            if (is_file($outputPath)) {
                unlink($outputPath);
            }
        }

        return [
            'success' => true,
            'fileId' => $fileId,
            'message' => 'Договор создан и прикреплён к сделке #' . $dealId,
        ];
    }

    private function generate(string $templatePath, array $data, string $outputPath): void
    {
        $escapingEnabled = \PhpOffice\PhpWord\Settings::isOutputEscapingEnabled();
        \PhpOffice\PhpWord\Settings::setOutputEscapingEnabled(true);
        try {
            $template = new \PhpOffice\PhpWord\TemplateProcessor($templatePath);
            foreach ($data as $key => $value) {
                $template->setValue($key, (string)$value);
            }
            $template->saveAs($outputPath);
            if (!is_file($outputPath) || filesize($outputPath) === 0) {
                throw new \RuntimeException('Не удалось сохранить документ');
            }
        } finally {
            \PhpOffice\PhpWord\Settings::setOutputEscapingEnabled($escapingEnabled);
        }
    }

    private function attachToDeal(int $dealId, string $filePath, bool $multiple): int
    {
        global $USER_FIELD_MANAGER;
        $fieldName = 'UF_CRM_CONTRACT_FILE';
        $userFields = $USER_FIELD_MANAGER->GetUserFields('CRM_DEAL', $dealId);
        $oldIds = array_filter(array_map('intval', (array) ($userFields[$fieldName]['VALUE'] ?? [])));
        $file = \CFile::MakeFileArray($filePath);
        if (!$file) {
            throw new \RuntimeException('Не удалось подготовить файл договора');
        }
        $file['name'] = 'contract_' . $dealId . '_' . date('Ymd_His') . '.docx';
        $file['type'] = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';

        $fields = ['UF_CRM_CONTRACT_FILE' => $multiple ? [$file] : $file];
        $deal = new \CCrmDeal();
        if (!$deal->Update($dealId, $fields)) {
            throw new \RuntimeException('Не удалось прикрепить договор: ' . $deal->LAST_ERROR);
        }

        $userFields = $USER_FIELD_MANAGER->GetUserFields('CRM_DEAL', $dealId);
        $newIds = array_values(array_filter(array_map('intval', (array) ($userFields[$fieldName]['VALUE'] ?? []))));
        if (count($newIds) !== 1 || in_array($newIds[0], $oldIds, true)) {
            throw new \RuntimeException('Не удалось подтвердить сохранение нового договора в сделке');
        }

        foreach ($oldIds as $oldId) {
            \CFile::Delete($oldId);
        }

        return $newIds[0];
    }
}
