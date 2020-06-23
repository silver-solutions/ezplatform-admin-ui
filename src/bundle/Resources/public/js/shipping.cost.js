import getFormDataFromObject from './helpers/form.data.helper.js';

(function(global, doc, eZ) {
    const addButton = doc.querySelector('.ez-shipping-management .ez-btn--add-shipping');
    const saveButton = doc.querySelector('.ez-shipping-management .ez-btn--save');
    const table = doc.querySelector('.ez-table--shipping-management');
    let shippingCostsList = [];
    let shippingMethods = [];
    const fetchShippingCosts = () => {
        const url = Routing.generate('siso_menu_admin_fetch_shipping_costs');

        fetch(url, { mode: 'same-origin', credentials: 'same-origin' })
            .then(eZ.helpers.request.getJsonFromResponse)
            .then(renderShippingCosts)
            .catch(eZ.helpers.notification.showErrorNotification);
    };
    const renderShippingCosts = (response) => {
        shippingCostsList = response.shippingCostsList;
        shippingMethods = response.shippingMethods;

        shippingCostsList.forEach(addRow);
    };
    const addRow = (shippingCosts) => {
        const container = doc.createElement('tbody');
        const template = table.dataset.rowTemplate;

        container.insertAdjacentHTML('beforeend', template);

        const row = container.querySelector('tr');
        const countrySelect = row.querySelector('.ez-table__country-select');
        const stateInput = row.querySelector('.ez-table__state-input');
        const zipInput = row.querySelector('.ez-table__zip-input');
        const shippingMethodsSelect = row.querySelector('.ez-table__shipping-method');
        const shippingCostInput = row.querySelector('.ez-table__shipping-cost');
        const valueInput = row.querySelector('.ez-table__value');
        const currencyInput = row.querySelector('.ez-table__currency');
        const shippingMethodsFragment = doc.createDocumentFragment();

        shippingMethods.forEach((shippingMethod) => {
            const container = doc.createElement('select');
            const option = `<option value="${shippingMethod.value}">${shippingMethod.label}</option>`;

            container.insertAdjacentHTML('beforeend', option);

            shippingMethodsFragment.append(container.querySelector('option'));
        });

        shippingMethodsSelect.append(shippingMethodsFragment);

        if (shippingCosts) {
            countrySelect.value = shippingCosts.country.value;
        }

        stateInput.value = shippingCosts ? shippingCosts.state : '';
        zipInput.value = shippingCosts ? shippingCosts.zip : '';

        if (shippingCosts) {
            shippingMethodsSelect.value = shippingCosts.shippingMethod.value;
        }

        shippingCostInput.value = shippingCosts ? shippingCosts.shippingCost : 0;
        valueInput.value = shippingCosts ? shippingCosts.valueOfGoods : 0;
        currencyInput.value = shippingCosts ? shippingCosts.currency : '';

        row.querySelector('.btn').addEventListener(
            'click',
            (event) => {
                event.currentTarget.closest('tr').remove();
            },
            false
        );

        table.querySelector('tbody').append(row);
    };
    const save = () => {
        const tableRows = [...table.querySelectorAll('tbody tr')];
        const shippingCostsList = tableRows.map((tableRow) => {
            const countrySelect = tableRow.querySelector('.ez-table__country-select');
            const stateInput = tableRow.querySelector('.ez-table__state-input');
            const zipInput = tableRow.querySelector('.ez-table__zip-input');
            const shippingMethodsSelect = tableRow.querySelector('.ez-table__shipping-method');
            const shippingCostInput = tableRow.querySelector('.ez-table__shipping-cost');
            const valueInput = tableRow.querySelector('.ez-table__value');
            const currencyInput = tableRow.querySelector('.ez-table__currency');
            const countryValue = countrySelect.value;
            const countryLabel = countrySelect.querySelector(`[value=${countryValue}]`).innerHTML;
            const shippingMethodValue = shippingMethodsSelect.value;
            const shippingMethodLabel = shippingMethods.find((shippingMethod) => shippingMethodValue === shippingMethod.value).label;

            return {
                country: {
                    value: countryValue,
                    label: countryLabel,
                },
                state: stateInput.value,
                zip: zipInput.value,
                shippingMethod: {
                    value: shippingMethodValue,
                    label: shippingMethodLabel,
                },
                shippingCost: shippingCostInput.value,
                valueOfGoods: valueInput.value,
                currency: currencyInput.value,
                shopId: {
                    value: 'MAIN',
                    label: 'MAIN',
                },
            };
        });

        const request = new Request(Routing.generate('siso_menu_admin_update_shipping_costs'), {
            method: 'POST',
            body: getFormDataFromObject({ shippingCostsList }),
            mode: 'same-origin',
            credentials: 'same-origin',
        });

        fetch(request)
            .then(eZ.helpers.request.getJsonFromResponse)
            .then((response) => {
                if (!response.success) {
                    const notSavedMessage = Translator.trans(
                        /*@Desc("Couldn't save shipping costs")*/ 'shipping_management.not_saved',
                        {},
                        'price_stock_ui'
                    );

                    eZ.helpers.notification.showErrorNotification(notSavedMessage);
                } else {
                    const savedMessage = Translator.trans(
                        /*@Desc("Shipping costs saved successfully")*/ 'shipping_management.saved',
                        {},
                        'price_stock_ui'
                    );

                    eZ.helpers.notification.showSuccessNotification(savedMessage);
                }
            })
            .catch(eZ.helpers.notification.showErrorNotification);
    };

    fetchShippingCosts();

    addButton.addEventListener('click', () => addRow(), false);
    saveButton.addEventListener('click', save, false);
})(window, window.document, window.eZ);
