import getFormDataFromObject from './helpers/form.data.helper.js';

(function(global, doc, eZ) {
    const skuWrapper = doc.querySelector('.ez-sku-search--price');

    if (!skuWrapper) {
        return;
    }

    const searchInput = skuWrapper.querySelector('.ez-sku-search__input');
    const searchButton = skuWrapper.querySelector('.ez-btn--search');
    const currencySelect = skuWrapper.querySelector('.ez-table-header__price-select');
    const searchResults = skuWrapper.querySelector('.ez-sku-search__results');
    const tableHeader = skuWrapper.querySelector('.ez-table-header__headline');
    const table = doc.querySelector('.ez-table--price-management');
    const tableBody = table.querySelector('tbody');
    const addPriceButton = skuWrapper.querySelector('.ez-btn--add-price');
    const saveButton = skuWrapper.querySelector('.ez-btn--save');
    const enterKeyCode = 13;
    let skuData = {};
    const handleKeyUp = (event) => {
        const keyCode = event.charCode || event.keyCode || 0;

        if (keyCode === enterKeyCode) {
            search();
        }
    };
    const search = (skuCode) => {
        const currency = currencySelect.value || 'EUR';
        const sku = skuCode || searchInput.value;
        const request = new Request(Routing.generate('siso_menu_admin_fetch_prices', { shopId: 'MAIN', currency }), {
            method: 'POST',
            body: getFormDataFromObject({ sku }),
            mode: 'same-origin',
            credentials: 'same-origin',
        });

        fetch(request)
            .then(eZ.helpers.request.getJsonFromResponse)
            .then(renderPriceTable)
            .catch(eZ.helpers.notification.showErrorNotification);
    };
    const renderPriceTable = (response) => {
        if (response.result.message !== undefined) {
            const notFoundMessage = Translator.trans(/*@Desc("Product not found")*/ 'product.not_found', {}, 'price_stock_ui');

            eZ.helpers.notification.showWarningNotification(notFoundMessage);

            if (searchResults) {
                searchResults.classList.add('ez-sku-search__results--hidden');
            }

            return;
        }

        skuData = response.result;

        const currencySelectFragment = doc.createDocumentFragment();
        const tableRowFragment = doc.createDocumentFragment();
        const tableHeaderText = Translator.trans(
            /*@Desc("Prices for %contentName%, List price %price%")*/ 'price.table.header',
            {
                contentName: response.result.name,
                price: `${response.result.baseprice * response.result.currencyList[response.result.currency]} ${response.result.currency}`,
            },
            'price_stock_ui'
        );

        Object.keys(response.result.currencyList).forEach((currency) => {
            const container = doc.createElement('select');
            const option = `<option value="${currency}">${currency}</option>`;

            container.insertAdjacentHTML('beforeend', option);

            currencySelectFragment.append(container.querySelector('option'));
        });

        currencySelect.innerHTML = '';
        currencySelect.append(currencySelectFragment);
        currencySelect.value = response.result.currency;
        tableHeader.innerHTML = tableHeaderText;

        response.result.prices['-'].forEach((price) => {
            const container = doc.createElement('tbody');
            const template = table.dataset.rowTemplate;
            const sku = skuWrapper.dataset.sku || searchInput.value;
            const renderTemplate = template.replace('{{ sku }}', sku);

            container.insertAdjacentHTML('beforeend', renderTemplate);

            const row = container.querySelector('tr');
            const customerGroupSelect = row.querySelector('.ez-table__customer-group-select');
            const customGroupFragment = createCustomGroupsFragment();

            customerGroupSelect.append(customGroupFragment);
            customerGroupSelect.value = price.customerGroup.groupId;

            row.querySelector('.ez-table__base-price').value = price.basePrice;
            row.querySelector('.ez-table__offer-price').value = price.offerPrice;
            row.querySelector('.btn').addEventListener(
                'click',
                (event) => {
                    event.currentTarget.closest('tr').remove();
                },
                false
            );

            tableRowFragment.append(row);
        });

        tableBody.innerHTML = '';
        tableBody.append(tableRowFragment);

        if (searchResults) {
            searchResults.classList.remove('ez-sku-search__results--hidden');
        }
    };
    const addPriceRow = () => {
        const container = doc.createElement('tbody');
        const template = table.dataset.rowTemplate;
        const sku = skuWrapper.dataset.sku || searchInput.value;
        const renderTemplate = template.replace('{{ sku }}', sku);
        const customGroupFragment = createCustomGroupsFragment();

        container.insertAdjacentHTML('beforeend', renderTemplate);

        const row = container.querySelector('tr');

        row.querySelector('.ez-table__customer-group-select').append(customGroupFragment);
        row.querySelector('.btn').addEventListener(
            'click',
            (event) => {
                event.currentTarget.closest('tr').remove();
            },
            false
        );

        tableBody.append(row);
    };
    const createCustomGroupsFragment = () => {
        const customGroupFragment = doc.createDocumentFragment();

        skuData.customerGroups.forEach((customerGroup) => {
            const container = doc.createElement('select');
            const option = `<option value="${customerGroup.groupId}">${customerGroup.label}</option>`;

            container.insertAdjacentHTML('beforeend', option);

            customGroupFragment.append(container.querySelector('option'));
        });

        return customGroupFragment;
    };
    const save = () => {
        const tableRows = [...tableBody.querySelectorAll('tr')];
        const currency = currencySelect.value;
        const prices = tableRows.map((tableRow) => {
            const groupId = tableRow.querySelector('.ez-table__customer-group-select').value;
            const groupLabel = skuData.customerGroups.find((group) => groupId === group.groupId).label;

            return {
                currency,
                shopId: 'main',
                sku: skuData.sku,
                variantCode: null,
                basePrice: parseFloat(tableRow.querySelector('.ez-table__base-price').value),
                offerPrice: parseFloat(tableRow.querySelector('.ez-table__offer-price').value),
                groupId: groupId,
                customerGroup: {
                    groupId: groupId,
                    label: groupLabel,
                },
            };
        });

        skuData.currency = currency;
        skuData.prices['-'] = prices;

        const request = new Request(Routing.generate('siso_menu_admin_update_prices'), {
            method: 'POST',
            body: getFormDataFromObject({ prices: skuData }),
            mode: 'same-origin',
            credentials: 'same-origin',
        });

        fetch(request)
            .then(eZ.helpers.request.getJsonFromResponse)
            .then((response) => {
                if (response.message !== '') {
                    const notSavedMessage = Translator.trans(/*@Desc("Couldn't save the prices")*/ 'price.not_saved', {}, 'price_stock_ui');

                    eZ.helpers.notification.showErrorNotification(notSavedMessage);
                } else {
                    const savedMessage = Translator.trans(/*@Desc("Prices saved successfully")*/ 'price.saved', {}, 'price_stock_ui');

                    eZ.helpers.notification.showSuccessNotification(savedMessage);
                }
            })
            .catch(eZ.helpers.notification.showErrorNotification);
    };

    if (searchInput) {
        searchInput.addEventListener('keyup', handleKeyUp, false);
    }

    if (searchButton) {
        searchButton.addEventListener('click', () => search(), false);
    }

    if (skuWrapper.dataset.sku) {
        search(skuWrapper.dataset.sku);
    }

    currencySelect.addEventListener('change', () => search(skuWrapper.dataset.sku), false);
    addPriceButton.addEventListener('click', addPriceRow, false);
    saveButton.addEventListener('click', save, false);
})(window, window.document, window.eZ);
