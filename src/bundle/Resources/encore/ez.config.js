const path = require('path');

module.exports = (Encore) => {
    Encore.addEntry('ezcommerce-admin-ui-control-center-js', [
        path.resolve(__dirname, '../public/js/search.filters.js'),
        path.resolve(__dirname, '../public/js/email.actions.js'),
        path.resolve(__dirname, '../public/js/econtent.info.js'),
        path.resolve(__dirname, '../public/js/erp.status.js'),
        path.resolve(__dirname, '../public/js/erp.show.log.js'),
        path.resolve(__dirname, '../public/js/erp.performance.chart.js'),
        path.resolve('./public/bundles/ezplatformadminui/js/scripts/admin.location.tab.js'),
    ])
        .addEntry('ezcommerce-admin-ui-control-center-css', [
            path.resolve(__dirname, '../public/css/c3.min.css'),
            path.resolve(__dirname, '../public/scss/ezcommerce-admin-ui.scss'),
        ])
        .addEntry('ezcommerce-admin-ui-stock-and-price-js', [
            path.resolve(__dirname, '../public/js/price.sku.search.js'),
            path.resolve(__dirname, '../public/js/stock.sku.search.js'),
            path.resolve(__dirname, '../public/js/shipping.cost.js'),
            path.resolve('./public/bundles/ezplatformadminui/js/scripts/admin.location.tab.js'),
        ])
        .addEntry('ezcommerce-admin-ui-stock-and-price-css', [path.resolve(__dirname, '../public/scss/ezcommerce-admin-ui.scss')]);
};
