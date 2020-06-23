const path = require('path');

module.exports = (Encore) => {
    Encore.addEntry('ezcommerce-admin-ui-control-center-css', [
        path.resolve(__dirname, '../public/vendor/c3/c3.min.css'),
        path.resolve(__dirname, '../public/scss/ezcommerce-admin-ui.scss'),
    ])
        .addEntry('ezcommerce-admin-ui-stock-and-price-css', [path.resolve(__dirname, '../public/scss/ezcommerce-admin-ui.scss')])
        .addEntry('ezcommerce-admin-ui-css', [
            path.resolve(__dirname, '../public/vendor/c3/c3.min.css'),
            path.resolve(__dirname, '../public/scss/ezcommerce.scss'),
        ]);

    Encore.addEntry('ezcommerce-admin-ui-control-center-js', [
        path.resolve(__dirname, '../public/js/search.filters.js'),
        path.resolve(__dirname, '../public/js/email.actions.js'),
        path.resolve(__dirname, '../public/js/econtent.info.js'),
        path.resolve(__dirname, '../public/js/erp.status.js'),
        path.resolve(__dirname, '../public/js/erp.show.log.js'),
        path.resolve(__dirname, '../public/js/erp.performance.chart.js'),
        path.resolve('./public/bundles/ezplatformadminui/js/scripts/admin.location.tab.js'),
    ])
        .addEntry('ezcommerce-admin-ui-stock-and-price-js', [
            path.resolve(__dirname, '../public/js/price.sku.search.js'),
            path.resolve(__dirname, '../public/js/stock.sku.search.js'),
            path.resolve(__dirname, '../public/js/shipping.cost.js'),
            path.resolve('./public/bundles/ezplatformadminui/js/scripts/admin.location.tab.js'),
        ])
        .addEntry('ezcommerce-admin-ui-cockpit-js', [
            path.resolve('./public/bundles/ezplatformadminui/js/scripts/admin.location.tab.js'),
            path.resolve(__dirname, '../public/js/cockpit.charts.js'),
            path.resolve(__dirname, '../public/js/cockpit.charts.filters.js'),
            path.resolve(__dirname, '../public/js/cockpit.filters.js'),
            path.resolve(__dirname, '../public/js/cockpit.status.js'),
            path.resolve(__dirname, '../public/js/cockpit.products.and.sales.js'),
            path.resolve(__dirname, '../public/js/cockpit.search.js'),
        ])
        .addEntry('ezcommerce-admin-ui-configuration-settings-js', [
            path.resolve('./public/bundles/ezplatformadminui/js/scripts/admin.location.tab.js'),
            path.resolve(__dirname, '../public/js/configuration.settings.group.toggle.js'),
            path.resolve(__dirname, '../public/js/configuration.settings.configuration.fields.js'),
        ])
        .addEntry('ezcommerce-admin-ui-order-management-js', [
            path.resolve(__dirname, '../public/js/order.management.export.orders.js'),
            path.resolve(__dirname, '../public/js/order.management.delete.order.js'),
            path.resolve(__dirname, '../public/js/order.management.transfer.order.js'),
            path.resolve(__dirname, '../public/js/order.management.filters.js'),
        ]);
};
