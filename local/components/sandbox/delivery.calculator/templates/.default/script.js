class CustomCalc {
    constructor(parameters) {
        this.parameters = parameters;
        this.button = BX(parameters.idbutton);
        this.result = BX(parameters.idresult);
        this.init();
    }

    init() {
        if (!this.button || !this.result) return;

        BX.bind(this.button, 'click', BX.delegate(this.sendForm, this));
    }

    sendForm(event) {
        event.preventDefault();

        const data = {};
        Object.entries(this.parameters.fields).forEach(function([name, id]) {
            data[name] = parseFloat(BX(id).value) || 0;
        });

        BX.ajax.runComponentAction(this.parameters.componentName, this.parameters.action, {
            mode: 'class',
            data: data,
        }).then((response) => {
            this.result.textContent = 'Стоимость доставки: ' + response.data.cost + ' руб.';
            this.result.classList.add('visible');
        }).catch(function(error) {
            console.error('Ошибка:', error);
        });
    }
}
