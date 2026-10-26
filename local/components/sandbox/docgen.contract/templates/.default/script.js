(function() {
    var fieldName = 'UF_CRM_CONTRACT_FILE';

    function getEditor(dealId) {
        var editor = BX.Crm?.EntityEditor?.getDefault();
        return editor && editor.getEntityTypeName() === 'DEAL'
            && Number(editor.getEntityId()) === dealId ? editor : null;
    }

    function findSection(controls) {
        for (var i = 0; i < controls.length; i++) {
            if (controls[i].getType() === 'section') return controls[i];
            var section = findSection(controls[i].getChildren());
            if (section) return section;
        }
        return null;
    }

    function ensureContractField(editor) {
        if (!editor || editor.getEntityTypeName() !== 'DEAL') return;
        if (editor.getControlById(fieldName)) return;

        var element = editor.getAvailableSchemeElementByName(fieldName);
        var section = findSection(editor.getControls());
        if (!element || !section) return;

        var field = editor.createControl(element.getType(), element.getName(), {
            schemeElement: element,
            model: section.getModel(),
            parent: section,
            mode: BX.UI.EntityEditorMode.view,
        });
        if (field) {
            section.addChild(field, { enableSaving: false, layout: { forceDisplay: true } });
        }
    }

    if (!BX.SandboxContractFieldInitialized) {
        BX.SandboxContractFieldInitialized = true;
        BX.addCustomEvent('BX.Crm.EntityEditor:onInit', ensureContractField);
    }

    BX.ready(function() {
        var button = document.getElementById('docgen_generate_button');
        var resultDiv = document.getElementById('docgen_result');
        if (!button || !resultDiv || button.dataset.initialized === 'Y') return;
        button.dataset.initialized = 'Y';
        var dealId = Number(button.dataset.dealId);
        ensureContractField(getEditor(dealId));

        button.addEventListener('click', function(event) {
            event.preventDefault();
            if (button.disabled) return;
            if (!Number.isInteger(dealId) || dealId <= 0) {
                resultDiv.className = 'error';
                resultDiv.textContent = 'Не указан корректный ID сделки';
                return;
            }
            var editor = getEditor(dealId);
            if (editor && (editor.isChanged() || editor.isRequestRunning())) {
                resultDiv.className = 'error';
                resultDiv.textContent = 'Сначала сохраните изменения сделки и дождитесь завершения сохранения';
                return;
            }

            button.disabled = true;
            resultDiv.className = '';
            resultDiv.textContent = 'Генерация...';

            BX.ajax.runComponentAction('sandbox:docgen.contract', 'generate', {
                mode: 'class',
                data: { deal_id: dealId },
            }).then(function(response) {
                if (response.data?.success !== true) {
                    throw new Error(response.data?.message || 'Не удалось создать договор');
                }
                resultDiv.className = 'success';
                resultDiv.textContent = '✅ ' + response.data.message;
                var currentEditor = getEditor(dealId);
                if (currentEditor && !currentEditor.isChanged() && !currentEditor.isRequestRunning()) {
                    ensureContractField(currentEditor);
                    currentEditor.reload();
                } else if (currentEditor) {
                    resultDiv.textContent += '. Сохраните изменения сделки и обновите карточку для просмотра файла';
                }
            }).catch(function(error) {
                resultDiv.className = 'error';
                resultDiv.textContent = '❌ Ошибка: ' + (error.errors?.[0]?.message || error.message || 'Неизвестная ошибка');
            }).finally(function() {
                button.disabled = false;
            });
        });
    });
})();
