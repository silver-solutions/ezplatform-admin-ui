import getFormDataFromObject from './helpers/form.data.helper.js';

(function(global, doc, eZ) {
    const skuWrapper = doc.querySelector('.ez-sku-search--stock');

    if (!skuWrapper) {
        return;
    }

    const searchInput = skuWrapper.querySelector('.ez-sku-search__input');
    const searchButton = skuWrapper.querySelector('.ez-btn--search');
    const searchResults = skuWrapper.querySelector('.ez-sku-search__results');
    const onStockInput = skuWrapper.querySelector('.ez-stock-update__input--on-stock');
    const stockTextInput = skuWrapper.querySelector('.ez-stock-update__input--stock-text');
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
        const sku = skuCode || searchInput.value;
        const request = new Request(Routing.generate('siso_menu_admin_fetch_stock', { shopId: 'MAIN' }), {
            method: 'POST',
            body: getFormDataFromObject({ sku }),
            mode: 'same-origin',
            credentials: 'same-origin',
        });

        fetch(request)
            .then(eZ.helpers.request.getJsonFromResponse)
            .then(setSkuData)
            .catch(eZ.helpers.notification.showErrorNotification);
    };
    const setSkuData = (response) => {
        if (response.result && response.result.message !== undefined) {
            const notFoundMessage = Translator.trans(/*@Desc("Product not found")*/ 'product.not_found', {}, 'price_stock_ui');

            eZ.helpers.notification.showWarningNotification(notFoundMessage);

            if (searchResults) {
                searchResults.classList.add('ez-sku-search__results--hidden');
            }

            return;
        }

        skuData = response;

        const stackValues = response.stock['-'];

        onStockInput.value = stackValues ? stackValues.stock : '';
        stockTextInput.value = stackValues ? stackValues.stockSign : '';

        if (searchResults) {
            searchResults.classList.remove('ez-sku-search__results--hidden');
        }
    };
    const save = () => {
        skuData.stock['-'].stock = onStockInput.value;
        skuData.stock['-'].stockSign = stockTextInput.value;

        const request = new Request(Routing.generate('siso_menu_admin_update_stock'), {
            method: 'POST',
            body: getFormDataFromObject({ stock: skuData }),
            mode: 'same-origin',
            credentials: 'same-origin',
        });

        fetch(request)
            .then(eZ.helpers.request.getJsonFromResponse)
            .then((response) => {
                if (response.message !== undefined) {
                    const notSavedMessage = Translator.trans(/*@Desc("Couldn't save stock")*/ 'stock.not_saved', {}, 'price_stock_ui');

                    eZ.helpers.notification.showErrorNotification(notSavedMessage);
                } else {
                    const savedMessage = Translator.trans(/*@Desc("Stock saved successfully")*/ 'stock.saved', {}, 'price_stock_ui');

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

    saveButton.addEventListener('click', save, false);
})(window, window.document, window.eZ);
