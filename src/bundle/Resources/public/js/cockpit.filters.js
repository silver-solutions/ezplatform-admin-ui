(function(global, doc, eZ) {
    const DEFAULT_SHOP_ID = 'MAIN';
    const token = doc.querySelector('meta[name="CSRF-Token"]').content;
    const siteaccess = doc.querySelector('meta[name="SiteAccess"]').content;
    const shopSelect = doc.querySelector('select[name="shop_id"]');
    const currencySelect = doc.querySelector('select[name="currency"]');
    const showShopIds = () => {
        const request = new Request('/api/ezp/v2/rest/shop-list', {
            method: 'GET',
            headers: {
                Accept: 'application/vnd.ez.api.ShopList+json',
                'Content-Type': 'application/vnd.ez.api.ShopList+json',
                'X-Siteaccess': siteaccess,
                'X-CSRF-Token': token,
            },
            mode: 'same-origin',
            credentials: 'same-origin',
        });

        fetch(request)
            .then(eZ.helpers.request.getJsonFromResponse)
            .then((response) => {
                const shopList = Object.values(response.ShopList.shopList).length
                    ? Object.values(response.ShopList.shopList)
                    : [DEFAULT_SHOP_ID];

                shopList.forEach((shopId) => {
                    const option = doc.createRange().createContextualFragment(`
                        <option value="${shopId}">${shopId}</option>
                    `);

                    shopSelect.append(option);
                });

                shopSelect.value = shopList[0];
                shopSelect.dispatchEvent(new Event('change'));
            })
            .catch(eZ.helpers.notification.showErrorNotification);
    };
    const showCurrencies = () => {
        const request = new Request('/api/ezp/v2/rest/currency-list', {
            method: 'GET',
            headers: {
                Accept: 'application/vnd.ez.api.CurrencyList+json',
                'Content-Type': 'application/vnd.ez.api.CurrencyList+json',
                'X-Siteaccess': siteaccess,
                'X-CSRF-Token': token,
            },
            mode: 'same-origin',
            credentials: 'same-origin',
        });

        fetch(request)
            .then(eZ.helpers.request.getJsonFromResponse)
            .then((response) => {
                const currencyList = Object.keys(response.CurrencyList.currencyList);

                currencyList.forEach((currency) => {
                    const option = doc.createRange().createContextualFragment(`
                        <option value="${currency}">${currency}</option>
                    `);

                    currencySelect.append(option);
                });

                currencySelect.value = currencyList[0];
                currencySelect.dispatchEvent(new Event('change'));
            })
            .catch(eZ.helpers.notification.showErrorNotification);
    };

    showShopIds();
    showCurrencies();
})(window, window.document, window.eZ);
